<?php

declare(strict_types=1);

namespace SeederGenerator\Test;
use DbSeeder\Address;
use PHPUnit\Framework\TestCase;
use SeederGenerator\Fixture;
use SeederGenerator\FixtureManager;
use SeederGenerator\Generator;
use SeederGenerator\ValueResolver;

class FeatureTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->connection = new \PDO(sprintf(
            'mysql:host=%s;port=%s;dbname=%s;user=%s;password=%s',
            $_ENV['DATABASE_HOST'],
            $_ENV['DATABASE_PORT'],
            $_ENV['DATABASE_NAME'],
            $_ENV['DATABASE_USERNAME'],
            $_ENV['DATABASE_PASSWORD'],
        ));

        $this->fixtureManager = new FixtureManager(
            $_ENV['DATABASE_HOST'],
            $_ENV['DATABASE_PORT'],
            $_ENV['DATABASE_NAME'],
            $_ENV['DATABASE_USERNAME'],
            $_ENV['DATABASE_PASSWORD'],
        );
    }


    public function test_with_clean(): void
    {
        $this->generateFixtures('../../seeder.config.php');
        $this->fixtureManager->save($address = Address::make());

        $this->assertRowExists($address);

        $this->fixtureManager->clean();

        $this->assertRowNotExists($address);
    }

    /**
     * Testing multiple run w/o cleaning DB
     *
     * Ensures the counter is saved to a file and resumes from the last recorded value, allowing multiple runs without
     * clearing the database. Prevents PDOException: SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate
     * entry '2000001' for key 'addresses.PRIMARY'
     */
    public function test_without_clean(): void
    {
        $this->generateFixtures('../../seeder.config.with_state_option.php');

        $this->fixtureManager->save($address = Address::make());

        $this->assertRowExists($address);

        ValueResolver::$globalIncrement = 2_000_000;

        $this->fixtureManager->save($address = Address::make());

        $this->assertRowExists($address);

        $counter = file_get_contents(__DIR__ . '/../seeder.state.tmp');
        $this->assertGreaterThan(2_000_001, (int) $counter);
    }

    private function generateFixtures(string $configFilePath): void
    {
        $generator = new Generator(
            $_ENV['DATABASE_HOST'],
            $_ENV['DATABASE_PORT'],
            $_ENV['DATABASE_NAME'],
            $_ENV['DATABASE_USERNAME'],
            $_ENV['DATABASE_PASSWORD']
        );

        $generator->generate(['*'], __DIR__ . '/../generated/seeds', 'DbSeeder', $configFilePath);
    }

    private function assertRowExists(Fixture $fixture)
    {
        $rows = $this->connection->query(sprintf('select * from addresses where id = %s', $fixture->id()))->fetchAll(\PDO::FETCH_ASSOC);
        $this->assertCount(1, $rows);
    }

    private function assertRowNotExists(Fixture $fixture)
    {
        $rows = $this->connection->query(sprintf('select * from addresses where id = %s', $fixture->id()))->fetchAll(\PDO::FETCH_ASSOC);
        $this->assertCount(0, $rows);
    }
}
