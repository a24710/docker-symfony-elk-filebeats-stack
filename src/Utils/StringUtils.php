<?php


namespace App\Utils;


class StringUtils
{
    public static function nullOrEmpty(?string $str): bool
    {
        return ($str === null || (strlen(trim($str)) === 0));
    }
}

