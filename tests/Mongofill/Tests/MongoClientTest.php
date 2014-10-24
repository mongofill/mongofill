<?php

namespace Mongofill\Tests;

use MongoClient;

class MongoClientTest extends TestCase
{
    /**
     * @expectedException MongoConnectionException
     */
    public function testInvalidURI()
    {
        $m = new MongoClient('foo', ['connect' => false]);
    }

    /**
     * @expectedException MongoConnectionException
     */
    public function testMissingTrailingSlash()
    {
        $m = new MongoClient('mongodb://foo?wtimeoutms=500', ['connect' => false]);
    }

    public function testServerSimple()
    {
        $m = new MongoClient('mongodb://foo', ['connect' => false]);
        $this->assertEquals('mongodb://foo:'.MongoClient::DEFAULT_PORT, $m->server);
    }

    /**
     * @expectedException Exception
     */
    public function testMultipleServersNotSupported()
    {
        $m = new MongoClient('mongodb://foo,bar:123,zee', ['connect' => false]);
    }

    public function testServerAuthenticationDefaultDatabase()
    {
        $m = new MongoClient('mongodb://user:pass@foo:123', ['connect' => false]);
        $this->assertEquals('user', $m->_getAuthenticationUsername());
        $this->assertEquals('pass', $m->_getAuthenticationPassword());
        $this->assertEquals(MongoClient::DEFAULT_DATABASE, $m->_getAuthenticationDatabase());
    }

    public function testServerAuthenticationWithDatabase()
    {
        $m = new MongoClient('mongodb://user:pass@foo:123/mydb', ['connect' => false]);
        $this->assertEquals('user', $m->_getAuthenticationUsername());
        $this->assertEquals('pass', $m->_getAuthenticationPassword());
        $this->assertEquals('mydb', $m->_getAuthenticationDatabase());
    }

    public function testServerOptionsInURI()
    {
        $m = new MongoClient('mongodb://foo/?connectTimeoutMS=500', ['connect' => false]);
        $this->assertEquals('500', $m->_getOption('connectTimeoutMS'));
    }

    public function testServerOptions()
    {
        $m = new MongoClient('mongodb://foo', ['port' => 123, 'connect' => false]);
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
