<?php

use Mongofill\Protocol;

class ProtocolTest extends PHPUnit_Framework_TestCase
{
    private function getProtocol()
    {
        $proto = new Protocol(fsockopen('localhost', 27017));
        return $proto;
    }

    public function testInsertPasses()
    {
        $conn = $this->getProtocol();
        $conn->opInsert('mongofill.instest', [ [ 'foo' => 'bar' ] ], false);
    }
}
 