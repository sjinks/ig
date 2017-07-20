<?php

namespace WildWolf\Test;

use WildWolf\Utils;

class UtilsTest extends \PHPUnit\Framework\TestCase
{
    public function testSafeFOpen()
    {
        $result = Utils::safe_fopen(__FILE__, 'x');
        $this->assertFalse($result);

        $result = Utils::safe_fopen(__FILE__, 'r');
        $this->assertTrue(is_resource($result));
    }

    public static function splitFilenameDataProvider()
    {
        return [
            ['file.ext',  ['file',  '.ext']],
            ['f.ile.ext', ['f.ile', '.ext']],
            ['file',      ['file',  '']],
        ];
    }

    /**
     * @dataProvider splitFilenameDataProvider
     */
    public function testSplitFilename($name, $expected)
    {
        $actual = Utils::splitFilename($name);
        $this->assertEquals($expected, $actual);
    }
}
