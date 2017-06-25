<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */

use JAQB\Services\ConnectionManager;
use Pulsar\Driver\DatabaseDriver;
use Pulsar\Services\ErrorStack;
use Pulsar\Services\ModelDriver;

/* This configuration is used to run the tests */

return  [
    'services' => [
        'database' => ConnectionManager::class,
        'errors' => ErrorStack::class,
        'model_driver' => ModelDriver::class,
    ],
    'models' => [
        'driver' => DatabaseDriver::class,
    ],
    'database' => [
        'test' => [
            'type' => 'mysql',
            'user' => 'root',
            'password' => '',
            'host' => '127.0.0.1',
            'name' => 'mydb',
        ]
    ],
    'sessions' => [
        'enabled' => true,
        'adapter' => 'database',
        'lifetime' => 86400,
    ],
    'cron' => [
        [
            'module' => 'test',
            'command' => 'test',
            'expires' => 60,
            'successUrl' => 'http://webhook.example.com',
            'minute' => 0,
            'hour' => 0,
            'day' => '*',
            'month' => '*',
            'week' => '*',
        ],
        [
            'module' => 'test',
            'command' => 'test2',
        ],
    ],
];
