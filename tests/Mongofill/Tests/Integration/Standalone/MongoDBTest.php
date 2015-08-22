<?php

namespace Mongofill\Tests\Integration\Standalone;

use MongoCode;

class MongoDBTest extends TestCase
{
    public function testListCollections()
    {
        $data = ['foo' => 'bar'];

        $db = $this->getTestDB();

        $coll = $db->selectCollection('testDB');
        $coll->insert($data);

        $collections = $db->listCollections();
        $this->assertCount(1, $collections);
        $this->assertInstanceOf('MongoCollection', $collections[0]);
        $this->assertSame('testDB', $collections[0]->getName());
    }

    public function testListCollectionsWithSystem()
    {
        $data = ['foo' => 'bar'];

        $db = $this->getTestDB();

        $coll = $db->selectCollection('testDB');
        $coll->insert($data);

        $collections = $db->listCollections(true);
        foreach ($collections as $collection) {
            $names[] = $collection->getName();
        }

        sort($names);

        $this->assertCount(2, $collections);
        $this->assertInstanceOf('MongoCollection', $collections[0]);
        $this->assertInstanceOf('MongoCollection', $collections[1]);
        $this->assertSame('system.indexes', $names[0]);
        $this->assertSame('testDB', $names[1]);
    }

    public function testGetCollectionNames()
    {
        $data = ['foo' => 'bar'];

        $db = $this->getTestDB();

        $coll = $db->selectCollection('testDB');
        $coll->insert($data);

        $collections = $db->getCollectionNames();
        $this->assertCount(1, $collections);
        $this->assertSame('testDB', $collections[0]);
    }

    public function testGetCollectionNamesWithSystem()
    {
        $data = ['foo' => 'bar'];

        $db = $this->getTestDB();

        $coll = $db->selectCollection('testDB');
        $coll->insert($data);

        $collections = $db->getCollectionNames(true);
        $this->assertCount(2, $collections);
        $this->assertTrue(in_array('testDB', $collections));
        $this->assertTrue(in_array('system.indexes', $collections));
    }

    public function testCreateCollection()
    {
        $db = $this->getTestDB();
        $db->createCollection('foo');

        $collections = $db->listCollections();
        $this->assertCount(1, $collections);
        $this->assertInstanceOf('MongoCollection', $collections[0]);
        $this->assertSame('foo', $collections[0]->getName());
    }

    public function testDropCollection()
    {
        $db = $this->getTestDB();
        $db->createCollection('foo');
        $db->dropCollection('foo');

        $collections = $db->listCollections();
        $this->assertCount(0, $collections);
    }

    public function testCommand()
    {
        $admin = $this->getTestClient()->selectDB('admin');

        $cmd = $admin->command(['buildinfo' => true]);
        $this->assertArrayHasKey('version', $cmd);
    }

    /**
     * @expectedException MongoCursorTimeoutException
     */
    public function testCommandWithTimeout()
    {
        $admin = $this->getTestClient()->selectDB('admin');


        $cmd = $admin->command(
            ['$eval' => new \MongoCode('function (y) { while(i < 1000000000) { i++;} }', ['x' => 2])],
            ['timeout' => 1]
        );
    }

    public function testExecute()
    {
        $db = $this->getTestDB();

        $func =
           "function (greeting, name) { ".
               "return greeting+', '+name+', says '+greeter;".
           "}";
        $scope = array("greeter" => "Fred");

        $code = new MongoCode($func, $scope);

        $response = $db->execute($code, array("Goodbye", "Joe"));
        $this->assertSame('Goodbye, Joe, says Fred', $response['retval']);
    }

    public function testAuthenticate()
    {
        $db = $this->getTestDB();
        $response = $db->authenticate('foo', 'bar');
        $this->assertSame(18, $response['code']);
    }
}
