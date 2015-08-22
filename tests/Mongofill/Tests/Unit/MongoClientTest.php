<?php

namespace Mongofill\Tests\Unit;

use MongoClient;

class MongoClientTest extends TestCase
{
    /**
     * @dataProvider parseHostStringDataProvider
     */
    public function testParseHostString($host, $expected)
    {
        $client = new MongoClient();
        $reflectedClient = new \ReflectionClass($client); // Testing a private method, use reflection
        $method = $reflectedClient->getMethod('parseHostString');
        $method->setAccessible(true);
        $output = $method->invoke ($client, $host);
        $this->assertEquals(array('host' => $expected[0], 'port' => $expected[1], 'hash' => $expected[2]), $output);
    }

    public function parseHostStringDataProvider()
    {
        return array(
            array('localhost:27017', array('localhost', 27017, 'localhost:27017')), // vanilla
            array('localhost', array('localhost', 27017, 'localhost:27017')), // no port specified
            array('example.com:27017', array('example.com', 27017, 'example.com:27017')), // fully qualified
            array('my.mongodb.example.com:27017', array('my.mongodb.example.com', 27017, 'my.mongodb.example.com:27017')), // fully qualified, multiple domain parts
            array('localhost:65535', array('localhost', 65535, 'localhost:65535')), // highest port possible
            array('localhost:1', array('localhost', 1, 'localhost:1' )), // lowest port possible. privileged but some people do weird things
        );
    }


    /**
     * @dataProvider parseHostStringInvalidDataProvider
     * @expectedException \MongoConnectionException
     */
    public function testParseHostStringInvalid($host)
    {

        $client = new MongoClient();
        $reflectedClient = new \ReflectionClass ($client); // Testing a private method, use reflection
        $method = $reflectedClient->getMethod ('parseHostString');
        $method->setAccessible(true);
        $output = $method->invoke ($client, $host);
    }

    public function parseHostStringInvalidDataProvider()
    {
        return array(
            array('http://www.example.com:27017'), // protocol shoudln't be in there
            array('localhost:foo'), // alpha chars where port should be
            array('localhost:foo:27017'), // alpha chars where port should be plus another port
            array('localhost:0'), // this is not a usable port
            array('localhost:65536'), // one larger than max port
            array('localhost:999999'), // way larger than max port
        );
    }
}
