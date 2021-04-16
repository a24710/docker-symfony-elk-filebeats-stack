<?php


namespace App\Tests\Utils;


use App\Utils\DateTimeUtils;
use PHPUnit\Framework\TestCase;

class DateTimeUtilsTest extends TestCase
{
    public function testIsWeekendDay()
    {
        $saturday = \DateTime::createFromFormat('Y:m:d', '2021:04:03'); //is saturday
        $friday = \DateTime::createFromFormat('Y:m:d', '2021:04:02'); //is friday

        self::assertFalse(DateTimeUtils::isWeekendDay(null));
        self::assertTrue(DateTimeUtils::isWeekendDay($saturday));
        self::assertFalse(DateTimeUtils::isWeekendDay($friday));
    }
}