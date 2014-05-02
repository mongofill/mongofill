<?php

namespace Mongofill\Tests;

use MongoClient;

class MongoClientTest extends TestCase
{
    public function testServerOptions()
    {
        $m = new MongoClient('foo', ['port' => 123, 'connect' => false]);
        $this->assertEquals('mongodb://foo:123', $m->server);
    }

    public function testServerOptionsDefault()
    {
        $m = new MongoClient('mongodb://localhost:27017', []);
        $this->assertInstanceOf('Mongofill\Protocol', $m->_getProtocol());
    }

    public function testGetProtocolAutoConnect()
    {
        $m = new MongoClient('mongodb://localhost:27017', ['connect' => false]);
        $this->assertInstanceOf('Mongofill\Protocol', $m->_getProtocol());
    }

    public function testIpv4Address()
    {
        $m = new MongoClient('mongodb://127.0.0.1:27017', []);
        $this->assertInstanceOf('Mongofill\Protocol', $m->_getProtocol());
    }

    public function testKillCursor()
    {
        $data = [
            ['A'],
            ['B'],
            ['C'],
            ['D'],
        ];
        $col = $this->getTestDB()->selectCollection(__FUNCTION__);

        $col->batchInsert($data);

        $cursor = $col->find();
        $cursor->batchSize(2);
        $cursor->limit(4);

        $cursor->next();
        $current = $cursor->current();
        $this->assertSame('A', $current[0]);
        
        $info = $cursor->info();

        $this->getTestClient()->killCursor($info['server'], $info['id']);

        $cursor->next();
        $current = $cursor->current();
        $this->assertSame('B', $current[0]);

        $cursor->next();
        $this->assertNull($cursor->current());
    }
}
