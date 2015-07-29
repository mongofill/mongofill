<?php

namespace Mongofill\Tests\Integration\Standalone;

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
    public function testInvalidProtocol()
    {
        $m = new MongoClient('foo://localhost', ['connect' => false]);
    }

    /**
     * @expectedException MongoConnectionException
     */
    public function testMissingTrailingSlash()
    {
        $m = new MongoClient('mongodb://foo?wtimeoutms=500', ['connect' => false]);
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
        $this->assertEquals(123, $m->_getOption('port'));
    }

    public function testServerOptionsDefault()
    {
        $m = new MongoClient(static::$conn_str, []);
        $this->assertInstanceOf('Mongofill\Protocol', $m->_getReadProtocol(['does not' => 'matter']));
        $this->assertInstanceOf('Mongofill\Protocol', $m->_getWriteProtocol());
    }

    public function testGetProtocolAutoConnect()
    {
        $m = new MongoClient(static::$conn_str, ['connect' => false]);
        $this->assertInstanceOf('Mongofill\Protocol', $m->_getWriteProtocol());
    }

    public function testIpv4Address()
    {
        $m = new MongoClient(static::$conn_str, []);
        $this->assertInstanceOf('Mongofill\Protocol', $m->_getWriteProtocol());
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

    public function testListDBs()
    {
        $coll = $this->getTestDB()->selectCollection(__FUNCTION__);
        $data = [
            'foo' => 'bar',
            'boolean' => false
        ];

        $coll->insert($data);

        $result_dbs = $this->getTestClient()->listDBs()['databases'];

        $db_names = array();

        foreach ($result_dbs as $db_info) {
            $db_names[] = $db_info['name'];
        }

        $this->assertGreaterThan(0, count($db_names));
        $this->assertTrue(in_array(self::TEST_DB, $db_names));
    }

    public function testGetHosts()
    {
        $m = new MongoClient(static::$conn_str);
        $hosts = $m->getHosts();
        $this->assertCount(1, $hosts);
        $this->assertArrayHasKey(static::$server, $hosts);
        $this->assertCount(7, $hosts[static::$server]);

        $host = $hosts[static::$server];
        $this->assertEquals(static::$host, $host['host']);
        $this->assertEquals(static::$port, $host['port']);
        $this->assertEquals(static::$server, $host['hash']);
        $this->assertEquals(1, $host['health']);
        $this->assertGreaterThan(0, $host['lastPing']);
    }
}
