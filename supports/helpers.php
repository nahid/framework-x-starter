<?php


if (!function_exists('base_path')) {
    function base_path($path = '')
    {
        return __DIR__ . '/../' . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}

if (!function_exists('env')) {
    function env(string $name, mixed $default = null): mixed
    {
        return $_ENV[$name] ?? $default;
    }
}