<?php

use Mongofill\Bson;

class BsonTest extends \PHPUnit_Framework_TestCase
{
    public function testEncodeSingleString()
    {
        $input  = [ 'hello' => 'world' ];
        $expect = "\x16\x00\x00\x00\x02hello\x00\x06\x00\x00\x00world\x00\x00";
        $this->assertEquals($expect,  Bson::encode($input));
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
        $expect = "\x31\x00\x00\x00\x03BSON\x00\x26\x00\x00\x00\x020\x00\x08"
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
        $mcIn = new MongoCode($code);
        $bson = Bson::encode([ 'code' => $mcIn ]);
        $mcOut = Bson::decode($bson)['code'];
        $this->assertEquals($code, $mcOut->__toString());
        $this->assertEmpty($mcOut->getScope());
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

    public function testEncodeDecodeMongoRegex()
    {
        $regex  = "/foo/iu";
        $input = new MongoRegex($regex);
        $bson = Bson::encode([ 'regex' => $input ]);
        $out = Bson::decode($bson)['regex'];
 
        $this->assertEquals($input, $out);
    }

}
