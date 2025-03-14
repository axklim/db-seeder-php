#!/usr/bin/env php
<?php

use SeederGenerator\Main;

require __DIR__ . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(__DIR__)->load();

(new Main())->main([
    'db_host' => $_ENV['DATABASE_HOST'],
    'db_port' => $_ENV['DATABASE_PORT'],
    'db_name' => $_ENV['DATABASE_NAME'],
    'db_user' => $_ENV['DATABASE_USERNAME'],
    'db_pass' => $_ENV['DATABASE_PASSWORD'],
    'application_root' => __DIR__,
]);
