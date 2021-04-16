<?php


namespace App\Tests\Utils;


use App\Utils\StringUtils;
use PHPUnit\Framework\TestCase;

class StringUtilsTest extends TestCase
{
    public function testIsNullOrEmpty()
    {
        self::assertTrue(StringUtils::nullOrEmpty(null));
        self::assertTrue(StringUtils::nullOrEmpty(''));
        self::assertTrue(StringUtils::nullOrEmpty(' '));
        self::assertFalse(StringUtils::nullOrEmpty('test'));
    }
}

