<?php

use Mongofill\Protocol;

class ProtocolTest extends PHPUnit_Framework_TestCase
{
    private function getProtocol()
    {
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_connect($socket, 'localhost', 27017);

        $proto = new Protocol($socket);
        
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

        $res = $conn->opQuery('mongofill.instest', [], 0, 0, Protocol::QF_SLAVE_OK);
        // var_dump($res);
        while ($res['result']) {
            $res = $conn->opGetMore('mongofill.instest', 10, $res['cursorId']);
            //var_dump($res);
        }
    }

    public function testDelete()
    {
        $conn = $this->getProtocol();
        $conn->opDelete('mongofill.instest', [], []);
    }
}
 