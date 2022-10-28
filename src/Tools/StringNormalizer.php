<?php

declare(strict_types=1);

namespace AnonyChat\Tools;

class StringNormalizer
{

    /**
     * @param string $userName
     * @return array|string|string[]|null
     */
    public static function name(string $userName)
    {
        return preg_replace("/[^a-zA-ZА-Яа-яёЁ0-9-_\.]/ui", "_", $userName);
    }

    /**
     * @param string $string
     * @return array|string|string[]|null
     */
    public static function room(string $string)
    {
        return preg_replace("/[^a-zA-ZА-Яа-яёЁ0-9-_\.\/]/ui", "_", $string);
    }

    /**
     * @param string $string
     * @return array|string|string[]|null
     */
    public static function type(string $string)
    {
        return preg_replace("/[^a-z]/u", "", $string);
    }

    public static function hexcolor(string $string): string
    {
        $string = preg_replace("/[^a-fA-F0-9]/u", "", $string);
        if (is_string($string)) {
            if (strlen($string) > 6) {
                $string = substr($string, 0, 6);
            }
            return '#' . $string;
        } else {
            return '';
        }
    }

    public static function text(string $string, int $maxLength = 1024): string
    {
        $string = htmlspecialchars($string);
        $string = mb_substr($string, 0, $maxLength);
        return $string;
    }
}
