<?php

class MongoIdTest extends PHPUnit_Framework_TestCase
{
    function testGenerateId()
    {
        $id = new MongoId();
        $this->assertInternalType('string', $id->id);
        $this->assertRegExp('/[0-9a-f]{24}/', $id->id);
        $this->assertEquals(24, strlen($id->id));
    }

    function testCustomId()
    {
        $id = new MongoId('0123456789abcdef01234567');
        $this->assertEquals('0123456789abcdef01234567', $id->id);
    }
}
 