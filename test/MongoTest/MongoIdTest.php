<?php

class MongoIdTest extends PHPUnit_Framework_TestCase
{
    function testGenerateId()
    {
        $id = new MongoId();
        $this->assertInternalType('string', $id->__toString());
        $this->assertRegExp('/[0-9a-f]{24}/', (string)$id);
        $this->assertEquals(24, strlen($id));
    }

    function testCustomId()
    {
        $id = new MongoId('0123456789abcdef01234567');
        $this->assertEquals('0123456789abcdef01234567', $id);
    }

    function testDisassemble()
    {
        $id = new MongoId('5307236762e2167d348b456b');
        $this->assertEquals(1392976743, $id->getTimestamp());
        $this->assertEquals(13437, $id->getPid());
        $this->assertEquals(9127275, $id->getInc());
    }

    function testAssemble()
    {
        $id1 = MongoId::__set_state([
            'timestamp' => 1234567890,
            'hostname'  => 'hostname',
            'pid'       => '314',
            'inc'       => '5555',
        ]);

        // disassemble to check assembled values
        $id2 = new MongoId($id1);

        $this->assertEquals(1234567890, $id2->getTimestamp());
        $this->assertEquals('0897ac', substr($id2, 8, 6));
        $this->assertEquals(314, $id2->getPid());
        $this->assertEquals(5555, $id2->getInc());
    }
}
 