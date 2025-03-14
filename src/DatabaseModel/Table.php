<?php

declare(strict_types=1);

namespace SeederGenerator\DatabaseModel;

use ICanBoogie\Inflector;
use RuntimeException;

class Table
{
    private Inflector $inflector;

    /**
     * @param Column[] $columns
     */
    public function __construct(
        private readonly string $name,
        private readonly array $columns,
    ) {
        $this->inflector = Inflector::get();
    }

    public function className(): string
    {
        return $this->inflector->singularize(
            $this->inflector->camelize($this->name, Inflector::UPCASE_FIRST_LETTER)
        );
    }

    public function getColumnByFieldName(string $fieldName): Column
    {
        foreach ($this->columns as $column) {
            if ($column->dbField() === $fieldName) {
                return $column;
            }
        }

        throw new RuntimeException('Unknown column: ' . $fieldName);
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return Column[]
     */
    public function columns(): array
    {
        return $this->columns;
    }

    /**
     * @param Column[] $columns
     */
    public static function make(string $tableName, array $columns): Table
    {
        return new self($tableName, $columns);
    }
}
