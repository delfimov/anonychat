<?php

namespace AnonyChat\Tools;

class StringNormilizer
{

    public static function name($userName)
    {
        return preg_replace("/[^a-zA-ZА-Яа-яёЁ0-9-_\.]/ui", "_", $userName);
    }

    public static function room($string)
    {
        return preg_replace("/[^a-zA-ZА-Яа-яёЁ0-9-_\.\/]/ui", "_", $string);
    }

    public static function type($string)
    {
        return preg_replace("/[^a-z]/u", "", $string);
    }

    public static function hexcolor($string)
    {
        $string = preg_replace("/[^a-fA-F0-9]/u", "", $string);
        if (strlen($string) > 6) {
            $string = substr($string, 0, 6);
        }
        return '#' . $string;
    }

    public static function text($string, $maxLength = 1024)
    {
        $string = htmlspecialchars($string);
        $string = mb_substr($string, 0, $maxLength);
        return $string;
    }
}
