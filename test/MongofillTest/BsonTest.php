<?php

use Mongofill\Bson;

class BsonTest extends \PHPUnit_Framework_TestCase
{
    public function testSingleString()
    {
        $input  = [ 'hello' => 'world' ];
        $expect = "\x16\x00\x00\x00\x02hello\x00\x06\x00\x00\x00world\x00\x00";
        $this->assertEquals($expect,  Bson::encode($input));
    }

    public function testMoreComplexMixed()
    {
        $input  = [ 'BSON' => [
                "awesome",
                5.05,
                new MongoInt32(1986),
            ]
        ];
        $expect = "\x31\x00\x00\x00\x03BSON\x00\x26\x00\x00\x00\x020\x00\x08"
                 ."\x00\x00\x00awesome\x00\x011\x00\x33\x33\x33\x33\x33\x33\x14"
                 ."\x40\x102\x00\xc2\x07\x00\x00\x00\x00";
        $this->assertEquals($expect,  Bson::encode($input));
    }
}
