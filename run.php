#!/usr/bin/env php
<?php

use SeederGenerator\Generator;

require __DIR__ . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(__DIR__)->load();

(new Generator(
    $_ENV['DATABASE_HOST'],
    $_ENV['DATABASE_PORT'],
    $_ENV['DATABASE_NAME'],
    $_ENV['DATABASE_USERNAME'],
    $_ENV['DATABASE_PASSWORD']
))->generate(['*'], __DIR__ . '/generated/seeds', 'DbSeeder');
