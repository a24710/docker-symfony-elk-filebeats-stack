<?php


namespace App\Utils;


class DateTimeUtils
{
    public static function isWeekendDay(?\DateTime $dateTime): bool
    {
        $result = false;

        if ($dateTime instanceof \DateTime){
            $dayOfWeek = $dateTime->format('N');
            $result = ($dayOfWeek === '6' || $dayOfWeek === '7');
        }

        return $result;
    }
}