<?php

namespace Mongofill\Tests;

use MongoClient;

class MongoClientTest extends TestCase
{
    function testServerOptions()
    {
        $m = new MongoClient('foo', ['port' => 123, 'connect' => false]);
        $this->assertEquals('mongodb://foo:123', $m->server);
    }

    function testServerOptionsDefault()
    {
        $m = new MongoClient('mongodb://localhost:27017', []);
        $this->assertInstanceOf('Mongofill\Protocol', $m->_getProtocol());
    }

    function testGetProtocolAutoConnect()
    {
        $m = new MongoClient('mongodb://localhost:27017', ['connect' => false]);
        $this->assertInstanceOf('Mongofill\Protocol', $m->_getProtocol());
    }

    function testKillCursor()
    {
        $data = [
            [ 'A' ],
            [ 'B' ],
            [ 'C' ],
            [ 'D' ],
        ];
        $col = $this->getTestDB()->selectCollection(__FUNCTION__);

        $col->batchInsert($data);

        $cur = $col->find();
        $cur->batchSize(2);
        $cur->limit(4);

        $cur->next();
        $cur->current();
        $this->assertNotEquals(0, $cur->_getCursorId());

        $this->getTestClient()->killCursor('foo', $cur->_getCursorId());

        $cur->next();
        $this->assertNull($cur->current());
    }
}
