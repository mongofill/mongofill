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

    public function testQuery()
    {
        $conn = $this->getProtocol();
        $res = $conn->opQuery('mongofill.instest', [], 0, 2, Protocol::QF_SLAVE_OK);
        var_dump($res);
        // ....
    }
}
 