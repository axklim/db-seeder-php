<?php

declare(strict_types=1);

namespace SeederGenerator\DatabaseModel;

use ICanBoogie\Inflector;

class Column
{
    private Inflector $inflector;

    public function __construct(
        private readonly string $dbField,
        private readonly string $dbType,
        private readonly string $dbNull,
        private readonly ?string $dbDefault,
        private readonly string $dbExtra,
        private readonly ?string $dbForeignKey,
    ) {
        $this->inflector = Inflector::get();
    }

    public function propertyName(): string
    {
        return $this->inflector->camelize($this->dbField, Inflector::DOWNCASE_FIRST_LETTER);
    }

    public function foreignClassName(?string $namespace = null): ?string
    {
        if ($this->dbForeignKey === null) {
            return null;
        }

        $fixtureClassName = $this->inflector->singularize($this->inflector->camelize($this->dbForeignKey, Inflector::UPCASE_FIRST_LETTER));
        return $namespace ? sprintf('\%s\%s', $namespace, $fixtureClassName) : $fixtureClassName;
    }

    public function phpType(): string
    {
        $type = $this->dbType;

        return match (true) {
            in_array($type, ['int', 'smallint', 'int unsigned', 'smallint unsigned'], true) => 'int',
            str_starts_with($type, 'tinyint') => 'int',
            str_starts_with($type, 'varchar') => 'string',
            str_starts_with($type, 'char') => 'string',
            str_starts_with($type, 'decimal') => 'string',
            'datetime' === $type => '\DateTimeImmutable',
            'json' === $type => 'array',
            default => 'mixed',
        };
    }

    public function default(): mixed
    {
        if ($this->dbDefault() === null) {
            return null;
        }

        return match ($this->phpType()) {
            'int' => (int) $this->dbDefault,
            'string' => (string) $this->dbDefault,
            'array' => json_decode($this->dbDefault, true, flags: JSON_THROW_ON_ERROR),
            default => $this->dbDefault,
        };
    }

    public function isNullable(): bool
    {
        return 'YES' === $this->dbNull;
    }

    public function isAutoIncrement(): bool
    {
        return 'auto_increment' === $this->dbExtra;
    }

    /**
     * @return Column[]
     */
    public static function makeList(array $columns): array
    {
        return array_map(static fn($column) => new self(
            dbField: (string) $column['Field'],
            dbType: (string) $column['Type'],
            dbNull: (string) $column['Null'],
            dbDefault: $column['Default'],
            dbExtra: (string) $column['Extra'],
            dbForeignKey: $column['FK'],
        ), $columns);
    }

    public function dbField(): string
    {
        return $this->dbField;
    }

    public function dbType(): string
    {
        return $this->dbType;
    }

    public function dbNull(): string
    {
        return $this->dbNull;
    }

    public function dbDefault(): ?string
    {
        return $this->dbDefault;
    }

    public function dbExtra(): string
    {
        return $this->dbExtra;
    }

    public function dbForeignKey(): ?string
    {
        return $this->dbForeignKey;
    }
}
