<?php

declare(strict_types=1);

namespace SeederGenerator;

use SeederGenerator\DatabaseModel\Column;
use SeederGenerator\DatabaseModel\Table;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\Parameter;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Printer;
use Nette\PhpGenerator\PromotedParameter;
use PDO;

//TODO: move all generate* methods to a separate builder
class Generator
{
    private PDO $connection;

    private string $dbName;

    public function __construct(string $dbHost, string $dbPort, string $dbName, string $dbUser, string $dbPass)
    {
        $this->setConnection([
            'db_host' => $dbHost,
            'db_port' => (int) $dbPort,
            'db_name' => $dbName,
            'db_user' => $dbUser,
            'db_pass' => $dbPass,
        ]);

        $this->dbName = $dbName;
    }

    /**
     * @param string[] $tables
     */
    public function generate(array $tables, string $seederPath, string $seederNamespace): void
    {
        if ($tables === ['*']) {
            $tables = $this->connection->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        }

        $databaseName = $this->dbName;

        foreach ($tables as $tableName) {
            $table = $this->fetchTableDescription($tableName, $databaseName);
            $fixtureClass = $this->generateFixtureClass($seederNamespace, $table);
            $this->saveFixtureClass($seederPath, $table->className() . '.php', $fixtureClass);
        }
    }

    public function generateFixtureClass(string $namespaceName, Table $table): PhpFile
    {
        $columns = $table->columns();

        $file = new PhpFile();
        $file->addComment(<<<COMMENT
        Autogenerated by SeederGenerator
        
        DO NOT EDIT DIRECTLY
        COMMENT);
        $file->setStrictTypes();

        $namespace = $file->addNamespace(new PhpNamespace($namespaceName));
        $namespace
            ->addClass($table->className())
            ->addImplement(Fixture::class)
            ->setMethods([
                $this->generateConstructor($columns),
                $this->generateMakeMethod($columns, $namespaceName),
                ...$this->generateDependencyMethods($columns),
                $this->generateTableNameMethod($table->name()),
                $this->generateFieldsMethod($columns),
                $this->generateValuesMethod($columns),
                $this->generateMetaMethod($table),
                ...$this->generateGetters($columns),
            ])
            ->addProperty('dependencies')
            ->addComment(sprintf('@var \%s[]', Fixture::class))
            ->setPublic()
            ->setType('array')
            ->setValue([])
        ;

        return $file;
    }

    protected function saveFixtureClass(string $fixturePath, string $fileName, PhpFile $generatedClass): void
    {
        if (!is_dir($fixturePath) && !mkdir($fixturePath, recursive: true) && !is_dir($fixturePath)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $fixturePath));
        }

        file_put_contents(sprintf('%s/%s', $fixturePath, $fileName), (new Printer())->printFile($generatedClass));
    }

    protected function generateConstructor(array $columns): Method
    {
        $parameters = array_map(
            static fn(Column $column) => (new PromotedParameter($column->propertyName()))
                ->setProtected()
                ->setReadOnly()
                ->setType($column->phpType())
                ->setNullable($column->isNullable()),
            $columns
        );

        return (new Method('__construct'))
            ->setPublic()
            ->setParameters($parameters);
    }

    /**
     * @param Column[] $columns
     */
    protected function generateMakeMethod(array $columns, string $namespaceName): Method
    {
        $parameters = array_map(
            static function(Column $column) use ($namespaceName): Parameter {
                $type = $column->phpType();
                if ($column->foreignClassName()) {
                    $type = sprintf('%s|%s', $type, $column->foreignClassName($namespaceName));
                }

                return (new Parameter($column->propertyName()))
                    ->setType($type)
                    ->setNullable(true)
                    ->setDefaultValue(null);
            }, $columns
        );

        return (new Method('make'))
            ->setStatic()
            ->setPublic()
            ->setReturnType('self')
            ->setParameters($parameters)
            ->addBody($this->generateMakeBody($columns))
        ;
    }

    /**
     * @param Column[] $columns
     */
    protected function generateMakeBody(array $columns): string
    {
        $body = <<<CODE
        \$valueResolver = new \SeederGenerator\ValueResolver(self::meta());
        
        
        CODE;

        $body .= $this->generateMakeBodyCreateDefaultFixture($columns);

        $body .= PHP_EOL . '$fixture = new self(' . PHP_EOL;
        foreach ($columns as $column) {
            $body .= sprintf("\t%s: \$valueResolver->resolve($%s, '%s'),\n", $column->propertyName(), $column->propertyName(), $column->dbField());
        }
        $body .= ');' . PHP_EOL . PHP_EOL;

        $body .= $this->generateMakeBodyApplyFixture($columns);

        $body .= PHP_EOL . 'return $fixture;';

        return $body;
    }

    /**
     * @param Column[] $columns
     * @return Method[]
     */
    protected function generateDependencyMethods(array $columns): array
    {
        $parameter = (new Parameter('fixture'))
            ->setType(Fixture::class);

        $withMethod = (new Method('with'))
            ->setPublic()
            ->setReturnType('self')
            ->setParameters([$parameter])
            ->addBody(<<<CODE
            \$this->dependencies[] = \$fixture;
            return \$this;
            CODE);

        $dependenciesMethod = (new Method('dependencies'))
            ->setPublic()
            ->addComment(sprintf('@return \%s[]', Fixture::class))
            ->setReturnType('array')
            ->addBody(<<<CODE
            return \$this->dependencies;
            CODE);

        return [$withMethod, $dependenciesMethod];
    }

    /**
     * @param Column[] $columns
     */
    protected function generateMakeBodyCreateDefaultFixture(array $columns): string
    {
        $columns = array_filter($columns, static fn(Column $column) => $column->dbForeignKey() !== null);
        $body = '';
        foreach ($columns as $column) {
            $parameterName = $column->propertyName();
            $foreignClassName = $column->foreignClassName();
            $body .= <<<CODE
            if (null === $$parameterName) {
                $$parameterName = $foreignClassName::make();
            }
            
            CODE;
        }

        return $body;
    }

    /**
     * @param Column[] $columns
     */
    protected function generateMakeBodyApplyFixture(array $columns): string
    {
        $columns = array_filter($columns, static fn(Column $column) => $column->dbForeignKey() !== null);
        $body = '';

        foreach ($columns as $column) {
            $parameterName = $column->propertyName();
            $body .= <<<CODE
            if ($$parameterName instanceof \SeederGenerator\Fixture) {
                \$fixture->with($$parameterName);
            }
            
            CODE;
        }

        return $body;
    }

    protected function generateTableNameMethod(string $tableName): Method
    {
        return (new Method('tableName'))
            ->setPublic()
            ->setReturnType('string')
            ->addBody(<<<CODE
            return '$tableName';
            CODE);
    }

    /**
     * @param Column[] $columns
     */
    protected function generateFieldsMethod(array $columns): Method
    {
        $fields = '[' . implode(', ', array_map(fn(Column $column) => '\'' . $column->dbField() . '\'', $columns)) . ']';

        return (new Method('fields'))
            ->setPublic()
            ->setReturnType('array')
            ->addBody(<<<CODE
            return $fields;
            CODE);
    }

    /**
     * @param Column[] $columns
     */
    protected function generateValuesMethod(array $columns): Method
    {
        $fields = '[' . implode(', ', array_map(fn(Column $column) => sprintf('\'%s\' => $toString($this->%s)', $column->dbField(), $column->propertyName()), $columns)) . ']';

        return (new Method('values'))
            ->setPublic()
            ->setReturnType('array')
            ->addBody(<<<CODE
            \$toString = static fn(\$value) => (\$value instanceof \DateTimeImmutable) ? \$value->format('Y-m-d H:i:s') : \$value;
            
            return $fields;
            CODE);
    }

    protected function generateMetaMethod(Table $table): Method
    {
        $body = '';
        foreach ($table->columns() as $column) {
            $body .= sprintf(
                '$columns[] = new \SeederGenerator\DatabaseModel\Column(\'%s\', \'%s\', \'%s\', %s, \'%s\', \'%s\');',
                $column->dbField(),
                $column->dbType(),
                $column->dbNull(),
                $column->dbDefault() === null ? 'null' : '\'' . $column->dbDefault() . '\'',
                $column->dbExtra(),
                $column->dbForeignKey(),
            );
            $body .= PHP_EOL;
        }
        $body .= PHP_EOL;

        $body .= sprintf('return \SeederGenerator\DatabaseModel\Table::make(\'%s\', $columns);', $table->name());
        $body .= PHP_EOL;

        return (new Method('meta'))
            ->setStatic()
            ->setPublic()
            ->setReturnType(Table::class)
            ->addBody($body);
    }

    /**
     * @param Column[] $columns
     * @return Method[]
     */
    protected function generateGetters(array $columns): array
    {
        return array_map(fn(Column $column) => $this->generateGetter($column), $columns);
    }

    protected function generateGetter(Column $column): Method
    {
        $name = $column->propertyName();

        return (new Method($name))
            ->setReturnType($column->phpType())
            ->setPublic()
            ->setReturnNullable($column->isNullable())
            ->addBody(<<<CODE
                return \$this->$name;
            CODE)
        ;
    }

    protected function setConnection(array $args): void
    {
        $this->connection = new PDO(sprintf('mysql:host=%s;port=%s;dbname=%s;user=%s;password=%s', $args['db_host'], $args['db_port'], $args['db_name'], $args['db_user'], $args['db_pass']));
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function fetchTableDescription(string $tableName, string $databaseName): Table
    {
        /**
         * [
         *  [
         *      Field => id
         *      Type => int
         *      Null => NO
         *      Key => PRI
         *      Default =>
         *      Extra => auto_increment
         * ]
         */
        $columns = $this->connection->query('DESCRIBE ' . $tableName)->fetchAll(PDO::FETCH_ASSOC);

        $references = $this->getReferenced($tableName, $databaseName);

        /**
         * [
         *  [
         *      Field => id
         *      Type => int
         *      Null => NO
         *      Key => PRI
         *      Default =>
         *      Extra => auto_increment
         *      FK => addresses|null
         * ]
         */
        $columns = array_map(static function (array $column) use($references):array {
            $column['FK'] = $references[$column['Field']] ?? null;
            return $column;
        }, $columns);


        return Table::make($tableName, Column::makeList($columns));
    }

    /**
     * @return array<string, string>
     */
    protected function getReferenced(string $tableName, string $databaseName): array
    {
        /**
         * [
         *  [
         *      COLUMN_NAME = "address_id"
         *      REFERENCED_TABLE_NAME = "addresses"
         *  ],
         * ]
         */
        $references = $this->connection->query(<<<SQL
            SELECT COLUMN_NAME, REFERENCED_TABLE_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = '$databaseName' AND TABLE_NAME = '$tableName' AND REFERENCED_TABLE_NAME IS NOT NULL ;        
        SQL)->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($references as $reference) {
            $result[$reference['COLUMN_NAME']] = $reference['REFERENCED_TABLE_NAME'];
        }

        return $result;
    }
}
