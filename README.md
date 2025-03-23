```php
(new Generator('host', 'port', 'name', 'username', 'password'))
    ->generate(['*'], __DIR__ . '/generated/seeds', 'DbSeeder');
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