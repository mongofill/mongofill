<?php

class MongoClientTest extends PHPUnit_Framework_TestCase
{
    function testServerOptions()
    {
        $m = new MongoClient('foo', [ 'port' => 123, 'connect' => false ]);
        $this->assertEquals('mongodb://foo:123', $m->server);
    }
}
 