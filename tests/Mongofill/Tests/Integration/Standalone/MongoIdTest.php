<?php

namespace Mongofill\Tests\Integration\Standalone;

use MongoId;

class MongoIdTest extends TestCase
{
    public function testGenerateId()
    {
        $id = new MongoId();
        $this->assertInternalType('string', $id->__toString());
        $this->assertRegExp('/[0-9a-f]{24}/', (string)$id);
        $this->assertEquals(24, strlen($id));
    }

    public function testCustomId()
    {
        $id = new MongoId('0123456789abcdef01234567');
        $this->assertEquals('0123456789abcdef01234567', $id);
    }

    public function testDisassemble()
    {
        $id = new MongoId('5307236762e2167d348b456b');
        $this->assertEquals(1392976743, $id->getTimestamp());
        $this->assertEquals(13437, $id->getPid());
        $this->assertEquals(9127275, $id->getInc());
    }

    public function testAssemble()
    {
        $id1 = MongoId::__set_state([
            'timestamp' => 1234567890,
            'hostname'  => 'hostname',
            'pid'       => '314',
            'inc'       => '5555',
        ]);

        // disassemble to check assembled values
        $id2 = new MongoId((string) $id1);

        $this->assertEquals(1234567890, $id2->getTimestamp());
        $this->assertEquals('0897ac', substr($id2, 8, 6));
        $this->assertEquals(314, $id2->getPid());
        $this->assertEquals(5555, $id2->getInc());
    }

    /**
     * The pid cannnot be injected so if the run process is under Int16
     * this tests dont have sense. In Linux the max value is 32768, but
     * on OSX is 99998
     */
    public function testPidGenerationInt16InOSX()
    {
        $original = new MongoId();
        $build = new MongoId((string) $original);

        $this->assertEquals($original, $build);
    }
}
