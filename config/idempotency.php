<?php

return [
    'default' => 'cache',
    'lock_wait' => [
        'timeout' => 10,
        'strategy' => 'wait', // wait|exception
    ],
    'request' => [
        'idempotent_methods' => ['POST', 'PATCH'],
        'store' => 'cache',
        'header' => [
            'idempotency_key' => 'Idempotency-Key',
            'idempotency_relay' => 'Idempotency-Relay',
        ]
    ],
    'stores' => [
        'cache' => [
            'driver' => 'default',
            'ttl' => 86400,
        ],
        'database' => [
            'driver' => 'default',
            'ttl' => 86400,
            'table_name' => 'idempotency',
        ]
    ]
];