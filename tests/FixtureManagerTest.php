<?php

declare(strict_types=1);

namespace SeederGenerator\Test;

use DbSeeder\Address;
use DbSeeder\Order;
use SeederGenerator\FixtureManager;
use PDO;
use PHPUnit\Framework\TestCase;

class FixtureManagerTest extends TestCase
{
    private FixtureManager $fixtureManager;
    private PDO $connection;

    public function setUp(): void
    {
        parent::setUp();

        $this->connection = new PDO(sprintf(
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

    public function tearDown(): void
    {
        $this->fixtureManager->clean();
    }

    /**
     * @covers \SeederGenerator\FixtureManager::save
     */
    public function test_save(): void
    {
        $address = Address::make(country: 'Germany', city: 'Berlin', postalCode: '123456789', street: 'Some platz');

        $this->fixtureManager->save($address);

        $rows = $this->connection->query(sprintf('select * from addresses where id = %s', $address->id()))->fetchAll(PDO::FETCH_ASSOC);
        $this->assertCount(1, $rows);
    }

    public function test_save_without_params(): void
    {
        $address = Address::make();

        $this->fixtureManager->save($address);

        $rows = $this->connection->query(sprintf('select * from addresses where id = %s', $address->id()))->fetchAll(PDO::FETCH_ASSOC);
        $this->assertCount(1, $rows);
    }

    public function test_save_nested_fixtures(): void
    {
        $fixture = Order::make();

        $this->fixtureManager->save($fixture);

        $rows = $this->connection->query(sprintf('select * from orders where id = %s', $fixture->id()))->fetchAll(PDO::FETCH_ASSOC);
        $this->assertCount(1, $rows);
    }
}
