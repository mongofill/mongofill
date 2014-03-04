<?php

class MongoDBTest extends BaseTest
{
    function testListCollections()
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

    function testListCollectionsWithSystem()
    {
        $data = ['foo' => 'bar'];
        
        $db = $this->getTestDB();
        
        $coll = $db->selectCollection('testDB');
        $coll->insert($data);

        $collections = $db->listCollections(true);
        $this->assertCount(2, $collections);
        $this->assertInstanceOf('MongoCollection', $collections[0]);
        $this->assertInstanceOf('MongoCollection', $collections[1]);
        $this->assertSame('system.indexes', $collections[0]->getName());
        $this->assertSame('testDB', $collections[1]->getName());
    }
}
