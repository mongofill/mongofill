<?php

class MongoClientTest extends BaseTest
{
    function testServerOptions()
    {
        $m = new MongoClient('foo', [ 'port' => 123, 'connect' => false ]);
        $this->assertEquals('mongodb://foo:123', $m->server);
    }
}
