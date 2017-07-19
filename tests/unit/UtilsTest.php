<?php

namespace WildWolf;

class UtilsTest extends \PHPUnit\Framework\TestCase
{
    public function testSafeFOpen()
    {
        $result = Utils::safe_fopen(__FILE__, 'x');
        $this->assertFalse($result);
    }
}
