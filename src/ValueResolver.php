<?php

declare(strict_types=1);

namespace SeederGenerator;

use SeederGenerator\DatabaseModel\Table;

class ValueResolver
{
    // TODO: move increment logic to separate VO
    public static int $globalIncrement = 2_000_000;

    public static function increment(): int
    {
        return ++self::$globalIncrement;
    }

    public function __construct(private readonly Table $table)
    {
    }

    public function resolve(mixed $value, string $fieldName): mixed
    {
        $column = $this->table->getColumnByFieldName($fieldName);

        if ($value instanceof Fixture) {
            return $value->id();
        }

        if ('CURRENT_TIMESTAMP' === $column->dbDefault() && '\DateTimeImmutable' === $column->phpType()) {
            return new \DateTimeImmutable();
        }

        if ($value !== null) {
            return $value;
        }

        if ($this->hasDefaultValueInConfig($fieldName)) {
            // TODO: Default value from configuration
            return null;
        }

        if ($column->isAutoIncrement()) {
            return self::increment();
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

        if ('\DateTimeImmutable' === $column->phpType()) {
            return new \DateTimeImmutable();
        }

        //TODO: move to config
        if ('state' === $column->dbField()) {
            return 'new';
        }

        if ('workflow_name' === $column->dbField()) {
            return 'order_v2';
        }

        if ('currency_code' === $column->dbField()) {
            return 'EUR';
        }

        if ('creation_source' === $column->dbField()) {
            return 'checkout';
        }

        if ('uuid' === $column->dbField()) {
            return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));
        }

        if ('name' === $column->dbField()) {
            return 'John';
        }

        if ('api_key' === $column->dbField()) {
            return sha1((string) random_int(1, 1_000_000));
        }

        if ('company_uuid' === $column->dbField()) {
            return '93373b0c-4b9e-4316-8668-62d8975b88bf';
        }

        if ('settings' === $column->dbField()) {
            return [];
        }

        if ('country' === $column->dbField()) {
            return 'DE';
        }

        if ('city' === $column->dbField()) {
            return 'Berlin';
        }

        if ('postal_code' === $column->dbField()) {
            return '12345678';
        }

        if ('street' === $column->dbField()) {
            return 'Test Platz';
        }

        if ('email' === $column->dbField()) {
            return 'test@example.com';
        }

        if ('legal_form' === $column->dbField()) {
            return 'other';
        }

        if ('debtor_data_hash' === $column->dbField()) {
            return '93373b0c-4b9e-4316-8668-62d8975b88bf';
        }

        if ('channel' === $column->dbField() ) {
            return 'direct';
        }

        if (str_starts_with($column->dbType(), 'decimal')) {
            return '0.0';
        }

        return null;
    }

    public function hasDefaultValueInConfig(string $key): bool
    {
        return false;
    }
}
