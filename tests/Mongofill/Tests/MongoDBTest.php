<?php

namespace Mongofill\Tests;

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

    public function testCreateCollection()
    {
        $db = $this->getTestDB();
        $db->createCollection('foo');

        $collections = $db->listCollections();
        $this->assertCount(1, $collections);
        $this->assertInstanceOf('MongoCollection', $collections[0]);
        $this->assertSame('foo', $collections[0]->getName());
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

    public function testCommand()
    {
        $admin = $this->getTestClient()->selectDB('admin');

        $cmd = $admin->command(['buildinfo' => true]);
        $this->assertArrayHasKey('version', $cmd);
    }
}
