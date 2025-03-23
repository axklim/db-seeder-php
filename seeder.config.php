<?php

return [
    'tables' => [
        'addresses' => [
            'postal_code' => fn () => (string) random_int(100000, 999999),
            'city' => 'London',
            'country' => 'England',
            'street' => 'Baker Street',
        ],
    ]
];
