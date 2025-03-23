<?php

declare(strict_types=1);

namespace SeederGenerator\Test;

use DbSeeder\Address;
use DbSeeder\Order;
use PHPUnit\Framework\TestCase;

class GeneratedFixtureTest extends TestCase
{
    public function test_should_make_fixture_with_defaults(): void
    {
        $order = Order::make(
            uuid: '4eea64d4-8384-4324-a975-0aced0e6b7d0',
        );

        $this->assertSame('4eea64d4-8384-4324-a975-0aced0e6b7d0', $order->uuid());
    }

    public function test_should_create_nested_fixture(): void
    {
        $order = Order::make(
            uuid: '4eea64d4-8384-4324-a975-0aced0e6b7d0',
            deliveryAddressId: Address::make(city: 'New York'),
        );

        $this->assertSame('4eea64d4-8384-4324-a975-0aced0e6b7d0', $order->uuid());
    }
}
