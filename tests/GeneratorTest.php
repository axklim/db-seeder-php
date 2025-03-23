<?php

namespace SeederGenerator\Test;

use SeederGenerator\Generator;
use Nette\PhpGenerator\Printer;
use PHPUnit\Framework\TestCase;

class GeneratorTest extends TestCase
{
    /**
     * @covers \SeederGenerator\Generator::generateFixtureClass
     */
    public function test_success(): void
    {
        $generator = new Generator(
            $_ENV['DATABASE_HOST'],
            $_ENV['DATABASE_PORT'],
            $_ENV['DATABASE_NAME'],
            $_ENV['DATABASE_USERNAME'],
            $_ENV['DATABASE_PASSWORD'],
        );


        $table = $generator->fetchTableDescription('orders', $_ENV['DATABASE_NAME']);
        $generatedFile = $generator->generateFixtureClass('DbSeeder', $table, '/path/to/seeder.config.php');
        $rawFile = (new Printer())->printFile($generatedFile);

        $this->assertSame($this->expectedRawFile(), $rawFile);
    }

    private function expectedRawFile(): string
    {
       return file_get_contents(__DIR__ . '/fixtures/order.txt');
    }

}
