<?php

declare(strict_types=1);

namespace SeederGenerator;

use PDO;

class FixtureManager
{
    public array $insertLog = [];

    private PDO $connection;

    public function __construct(
        string $dbHost, string $dbPort, string $dbName, string $dbUser, string $dbPass
    ) {
        $this->setConnection([
            'db_host' => $dbHost,
            'db_port' => $dbPort,
            'db_name' => $dbName,
            'db_user' => $dbUser,
            'db_pass' => $dbPass,
        ]);
    }

    public function save(Fixture $fixture): void
    {
        $fixtures = array_reverse($this->collectNestedFixtures($fixture));

        foreach ($fixtures as $f) {
            $this->saveFixture($f);
        }

        $this->saveFixture($fixture);
    }

    private function saveFixture(Fixture $fixture): void
    {
        $fields = implode(',', $fixture->fields());

        $placeholders = implode(',', array_map(static fn (string $field) => ':' . $field, $fixture->fields()));

        $stmt = $this->connection->prepare(<<<SQL
            INSERT INTO {$fixture->tableName()} ($fields)
            VALUES ($placeholders)
        SQL);

        $values = [];
        foreach ($fixture->values() as $key => $value) {
            $values[$key] = is_array($value) ? json_encode($value, flags: JSON_THROW_ON_ERROR) : $value;
        }

        $stmt->execute([...$values]);

        $this->insertLog[] = [$fixture->tableName(), $fixture->id()];
    }

    public function collectNestedFixtures(Fixture $fixture): array
    {
        $acc = [];
        $dependencies = $fixture->dependencies();

        foreach ($dependencies as $dependency) {
            $acc[] = $dependency;
            $acc = [...$acc, ...$this->collectNestedFixtures($dependency)];
        }

        return $acc;
    }

    public function clean(): void
    {
        $this->insertLog = array_reverse($this->insertLog);

        foreach ($this->insertLog as $key => $log) {
            $stmt = $this->connection->prepare('DELETE FROM ' . $log[0] . ' WHERE id = :id');
            $stmt->execute(['id' => $log[1]]);
            unset($this->insertLog[$key]);
        }
    }

    public function setConnection(array $args): void
    {
        $this->connection = new PDO(sprintf('mysql:host=%s;port=%s;dbname=%s;user=%s;password=%s', $args['db_host'], $args['db_port'], $args['db_name'], $args['db_user'], $args['db_pass']));
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
}
