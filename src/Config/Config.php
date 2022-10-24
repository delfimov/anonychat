<?php

namespace AnonyChat\Config;

class Config
{
    const CONFIG = [
        'local_cert' => __DIR__ . '/fullchain.pem',
        'local_pk' => __DIR__ . '/privkey.pem',
        'port' => '9888',
        'server' => 'localhost',
    ];

    public static function get($name, $default = null)
    {
        return self::CONFIG[$name] ?? $default;
    }
}
