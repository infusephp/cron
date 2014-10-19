<?php

/* This configuration is used to run the tests */

return  [
  'site' => [
    'salt' => 'replacewithrandomstring',
  ],
  'modules' => [
    'middleware' => [
      'auth'
    ]
  ],
  'database' => [
    'type' => 'mysql',
    'user' => 'root',
    'password' => '',
    'host' => '127.0.0.1',
    'name' => 'mydb',
  ],
  'sessions' => [
    'enabled' => true,
    'adapter' => 'database',
    'lifetime' => 86400
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
      'week' => '*'
    ],
    [
      'module' => 'test',
      'command' => 'test2'
    ]
  ]
];
