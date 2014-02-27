<?php

class MongoClientTest extends BaseTest
{
    public function testServerOptions()
    {
        $m = new MongoClient('foo', [ 'port' => 123, 'connect' => false ]);
        $this->assertEquals('mongodb://foo:123', $m->server);
    }

    public function testListDBs()
    {
        $this->assertTrue(method_exists('MongoClient', 'listDBs'), 'Method listDBs is not implemented');

        $this->getTestDB()->{__METHOD__}->insert([ 'foo' => 'bar' ]);
        $dbs = $this->getTestClient()->listDBs();

        $this->assertInternalType('array', $dbs);
        $this->assertArrayHasKey('totalSize', $dbs);
        $this->assertInternalType('float', $dbs['totalSize']);
        $this->assertGreaterThan(0, $dbs['totalSize']);
        $this->assertArrayHasKey('ok', $dbs);
        $this->assertInternalType('float', $dbs['ok']);
        $this->assertEquals(1, $dbs['ok']);
        $this->assertArrayHasKey('databases', $dbs);

        $testDb = array_reduce($dbs['databases'], function($result, $item) {
            if (isset($item['name']) && $item['name'] == TEST_DB) $result = $item;
            return $result;
        }, null);

        $this->assertNotNull($testDb);
        $this->assertInternalType('float', $testDb['sizeOnDisk']);
        $this->assertGreaterThan(0, $testDb['sizeOnDisk']);
        $this->assertFalse($testDb['empty']);
    }

}
