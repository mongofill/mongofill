<?php

namespace Mongofill\Tests\Integration\Standalone\Mongofill;

use Mongofill\Tests\Integration\Standalone\TestCase;
use Mongofill\Bson;
use MongoInt32;
use MongoBinData;
use MongoDate;
use MongoTimestamp;
use MongoRegex;
use MongoCode;
use MongoMaxKey;
use MongoMinKey;

class BsonTest extends TestCase
{
    public function testEncodeSingleString()
    {
        $input  = [ 'hello' => 'world' ];
        $expect = "\x16\x00\x00\x00\x02hello\x00\x06\x00\x00\x00world\x00\x00";
        $this->assertEquals($expect,  Bson::encode($input));
    }

    public function testEncodeDecodeBooleanString()
    {
        $input  = [ 'hello' => false ];
        $this->assertEquals($input, Bson::decode(Bson::encode($input)));

        $input  = [ 'hello' => true ];
        $this->assertEquals($input, Bson::decode(Bson::encode($input)));
    }

    public function testEncodeDecodeInt32()
    {
        $input  = [ 'hello' => new MongoInt32(-1) ];
        $this->assertEquals(['hello' => -1], Bson::decode(Bson::encode($input)));
    }

    public function testEncodeMoreComplexMixed()
    {
        $input  = [
            'BSON' => [
                "awesome",
                5.05,
                new MongoInt32(1986),
            ]
        ];
        $expect = "\x31\x00\x00\x00\x04BSON\x00\x26\x00\x00\x00\x020\x00\x08"
                 ."\x00\x00\x00awesome\x00\x011\x00\x33\x33\x33\x33\x33\x33\x14"
                 ."\x40\x102\x00\xc2\x07\x00\x00\x00\x00";
        $this->assertEquals($expect,  Bson::encode($input));
    }

    public function testDecodeSingleString()
    {
        $input = "\x16\x00\x00\x00\x02hello\x00\x06\x00\x00\x00world\x00\x00";
        $expect  = [ 'hello' => 'world' ];
        $this->assertEquals($expect, Bson::decode($input));
    }

    public function testDecodeMoreComplexMixed()
    {
        $input = "\x31\x00\x00\x00\x04BSON\x00\x26\x00\x00\x00\x020\x00\x08"
                ."\x00\x00\x00awesome\x00\x011\x00\x33\x33\x33\x33\x33\x33\x14"
                ."\x40\x102\x00\xc2\x07\x00\x00\x00\x00";
        $expect  = [
            'BSON' => [
                "awesome",
                5.05,
                1986,
            ]
        ];
        $this->assertEquals($expect, Bson::decode($input));
    }

    public function testEncodeDecodeCode()
    {
        $code  = "var foo = 'bar'";
        $mcIn = new MongoCode($code, ['a' => 'b']);
        $bson = Bson::encode([ 'code' => $mcIn ]);
        $expected = "310000000f636f646500260000001000000076617220666f6f203d20276261722700".
            "0e0000000261000200000062000000";
        $this->assertEquals($expected, bin2hex($bson));
        $mcOut = Bson::decode($bson)['code'];
        $this->assertEquals($code, $mcOut->__toString());
        $this->assertEquals($mcOut->getScope(), ['a' => 'b']);
    }

    public function testEncodeDecodeCodeWithScope()
    {
        $code  = "var foo = 'bar'";
        $scope = [ 'baz' => 'oof' ];
        $mcIn = new MongoCode($code, $scope);
        $bson = Bson::encode([ 'code' => $mcIn ]);
        $mcOut = Bson::decode($bson)['code'];
        $this->assertEquals($code, $mcOut->__toString());
        $this->assertEquals($scope, $mcOut->getScope());
    }

    public function testDecodeSample1()
    {
        $input = "23000000075f69640053075e7384adaad580d11bc512666f6f00000000000800000000";
        $doc = Bson::decode(hex2bin($input));
        $this->assertEquals('53075e7384adaad580d11bc5', $doc['_id']);
        $this->assertEquals(34359738368, $doc['foo']);
    }

    public function testEncodeDecodeNull()
    {
        $bson = Bson::encode([ 'null' => null ]);
        $out = Bson::decode($bson)['null'];
        $this->assertNull($out);
    }

    public function testEncodeDecodeBinData()
    {
        // subtype 0
        $data  = "somebinarydata\0\0\0";
        $input = new MongoBinData($data, 0);
        $bson = Bson::encode([ 'bin' => $input ]);
        $out = Bson::decode($bson)['bin'];
        $this->assertEquals($data, $out->bin);

        // subtype 2
        $input = new MongoBinData($data, 2);
        $bson = Bson::encode([ 'bin' => $input ]);
        $out = Bson::decode($bson)['bin'];
        $this->assertEquals($data, $out->bin);
    }

    public function testEncodeTimestamp()
    {
        $input = new MongoTimestamp(123, 456);
        $bson = Bson::encode([ 'ts' => $input ]);
        $out = Bson::decode($bson)['ts'];
        $this->assertEquals($input, $out);
        $this->assertEquals('1100000011747300c80100007b00000000', bin2hex($bson));
    }

    public function testEncodeDate()
    {
        $timestamp = strtotime('2014-03-01 14:30:33 UTC', 123120);
        $input = new MongoDate($timestamp);
        $bson = bson_encode([ 'date' => $input ]);
        $out = bson_decode($bson)['date'];
        $this->assertEquals($input, $out);
        $this->assertEquals('1300000009646174650028bb0d7e4401000000', bin2hex($bson));

        $input = new MongoDate(-$timestamp, 123120);
        $bson = bson_encode([ 'date' => $input ]);
        $out = bson_decode($bson)['date'];
        $this->assertEquals($input, $out);
        $this->assertEquals('130000000964617465005345f281bbfeffff00', bin2hex($bson));
    }

    public function testEncodeDecodeMongoRegex()
    {
        $regex  = "/foo/iu";
        $input = new MongoRegex($regex);
        $bson = Bson::encode([ 'regex' => $input ]);
        $out = Bson::decode($bson)['regex'];

        $this->assertEquals($input, $out);
    }

    public function testEncodeDecodeArray()
    {
        $input = [['foo', 'bar']];
        $expect = "#\000\000\000\0040\000\033\000\000\000\0020\000\004\000\000\000foo\000\0021\000\004\000\000\000bar\000\000\000";
        $this->assertEquals($expect,  Bson::encode($input));
    }

    public function testEncodeDecodeDictionary()
    {
        $input = [['foo' => 1, 'bar' => 2]];
        $expect = "'\000\000\000\0030\000\037\000\000\000\022foo\000\001\000\000\000\000\000\000\000\022bar\000\002\000\000\000\000\000\000\000\000\000";
        $this->assertEquals($expect,  Bson::encode($input));
    }

    public function testEncodeDecodeObject()
    {
        $input = [(object) ['foo' => 1, 'bar' => 2]];
        $expect = "'\000\000\000\0030\000\037\000\000\000\022foo\000\001\000\000\000\000\000\000\000\022bar\000\002\000\000\000\000\000\000\000\000\000";
        $this->assertEquals($expect,  Bson::encode($input));
    }

    public function testEncodeDecodeObjectEmpty()
    {
        $input = [(object) []];
        $expect = "\r\000\000\000\0030\000\005\000\000\000\000\000";
        $this->assertEquals($expect,  Bson::encode($input));
    }

    public function testEncodeMinKeyMaxKey()
    {
        $input = [ 'min' => new MongoMinKey(), 'max' => new MongoMaxKey() ];
        $bson = Bson::encode($input);
        $out = Bson::decode($bson);
        $this->assertEquals($input, $out);
        $this->assertEquals('0f000000ff6d696e007f6d61780000', bin2hex($bson));
    }

    public function testIsDocument()
    {
        $this->assertFalse(Bson::isDocument(['foo']));
        $this->assertTrue(Bson::isDocument(['foo' => 1]));
        $this->assertTrue(Bson::isDocument(['foo', 'bar' => 1]));
    }
}
