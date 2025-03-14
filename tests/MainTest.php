<?php

namespace SeederGenerator\Test;

use SeederGenerator\Main;
use Nette\PhpGenerator\Printer;
use PHPUnit\Framework\TestCase;

class MainTest extends TestCase
{
    /**
     * @covers \SeederGenerator\Main::generateFixtureClass
     */
    public function test_success(): void
    {
        $main = new Main();
        $main->setConnection([
            'db_host' => $_ENV['DATABASE_HOST'],
            'db_port' => $_ENV['DATABASE_PORT'],
            'db_name' => $_ENV['DATABASE_NAME'],
            'db_user' => $_ENV['DATABASE_USERNAME'],
            'db_pass' => $_ENV['DATABASE_PASSWORD'],
        ]);


        $table = $main->fetchTableDescription('orders', $_ENV['DATABASE_NAME']);
        $generatedFile = $main->generateFixtureClass('DbSeeder\Fixture', $table);
        $rawFile = (new Printer())->printFile($generatedFile);

        $this->assertSame($this->expectedRawFile(), $rawFile);
    }

    private function expectedRawFile(): string
    {
       return file_get_contents(__DIR__ . '/fixtures/order.txt');
    }

}
