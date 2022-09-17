<?php

return [
    'driver' => env('DB_DRIVER', 'mysql'),
    'host' => env('DB_HOST', 'localhost'),
    'port' => env('DB_PORT', 3306),
    'name' => env('DB_NAME', 'database'),
    'username' => env('DB_USERNAME', 'root'),
    'password' => env('DB_PASSWORD', ''),
];