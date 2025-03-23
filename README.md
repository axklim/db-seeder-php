```bash
composer require --dev axklim/db-seeder-php
```

composer.json:
```json
    "autoload-dev": {
        "psr-4": {
            "DbSeeder\\": "var/generated/",
            ...
        },
```

```php
(new SeederGenerator\Generator('host', 'port', 'name', 'username', 'password'))
    ->generate(['*'], __DIR__ . '/var/generated/', 'DbSeeder');

$fixtureManager = new SeederGenerator\FixtureManager('host', 'port', 'name', 'username', 'password');

$order = DbSeeder\Order::make();

$fixtureManager->save($order);
```

### Run tests:

```bash
./run.php # generate fixtures files
./tools/phpunit.phar
```

### Deploy

```shell
git tag v0.0.N
git push origin v0.0.N
```

License
-------

This package is available under the [MIT license](Resources/meta/LICENSE)