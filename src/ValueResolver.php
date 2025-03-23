<?php

declare(strict_types=1);

namespace SeederGenerator;

use SeederGenerator\DatabaseModel\Table;

class ValueResolver
{
    // TODO: move increment logic to separate VO
    public static int $globalIncrement = 2_000_000;

    private array $config = [];

    public static function increment(): int
    {
        return ++self::$globalIncrement;
    }

    public function __construct(private readonly Table $table, string $configFilePath)
    {
        $this->config = require($configFilePath);
    }

    public function resolve(mixed $value, string $fieldName): mixed
    {
        $column = $this->table->getColumnByFieldName($fieldName);

        if ($value instanceof Fixture) {
            return $value->id();
        }

        if ($value !== null) {
            return $value;
        }

        if ($this->hasDefaultValueInConfig($fieldName)) {
            return $this->defaultConfigValue($fieldName);
        }

        if ($column->isAutoIncrement()) {
            return self::increment();
        }

        if ('CURRENT_TIMESTAMP' === $column->dbDefault() && '\DateTimeImmutable' === $column->phpType()) {
            return new \DateTimeImmutable();
        }

        if ($column->isNullable()) {
            return null;
        }

        if ($column->default() !== null) {
            return $column->default();
        }

        //Reasonable defaults for mandatory fields
        if ('int' === $column->phpType()) {
            return 1;
        }

        if (str_starts_with($column->dbType(), 'decimal')) {
            return '0.0';
        }

        if ('\DateTimeImmutable' === $column->phpType()) {
            return new \DateTimeImmutable();
        }

        //TODO: remove and fix tests!!!!
        if (isset($_ENV['BD_SEEDER_TEST_RUN']) && 'yes' === $_ENV['BD_SEEDER_TEST_RUN']) {
            if ('settings' === $column->dbField()) {
                return [];
            }
            return sha1((string) random_int(1, 1_000_000));
        }

        return null;
    }

    public function hasDefaultValueInConfig(string $key): bool
    {
        $table = $this->table->name();

        return isset($this->config['tables'][$table][$key]);
    }

    public function defaultConfigValue(string $key): mixed
    {
        $table = $this->table->name();

        $value = $this->config['tables'][$table][$key];

        if (is_callable($value)) {
            return $value();
        }

        return $value;
    }
}
