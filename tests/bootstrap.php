<?php

require dirname(__DIR__) . '/vendor/autoload.php';

Dotenv\Dotenv::createImmutable(
    dirname(__DIR__),
    ['.env.test.local', '.env.test']
)->load();
