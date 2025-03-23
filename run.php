#!/usr/bin/env php
<?php

use SeederGenerator\Main;

require __DIR__ . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(__DIR__)->load();

(new Main())->main(
    $_ENV['DATABASE_HOST'],
    $_ENV['DATABASE_PORT'],
    $_ENV['DATABASE_NAME'],
    $_ENV['DATABASE_USERNAME'],
    $_ENV['DATABASE_PASSWORD'],
     __DIR__ . '/generated/seeds',
);
