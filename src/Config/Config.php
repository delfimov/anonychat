<?php

namespace AnonyChat\Config;

class Config
{
    const CONFIG = [
        'local_cert' => 'fullchain.pem',
        'local_pk' => 'privkey.pem',
        'port' => '9888',
        'server' => 'localhost',
    ];

    public static function get($name, $default = null)
    {
        return self::CONFIG[$name] ?? $default;
    }
}
