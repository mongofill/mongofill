<?php

class MongoDBTest extends BaseTest
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
        $this->assertCount(2, $collections);
        $this->assertInstanceOf('MongoCollection', $collections[0]);
        $this->assertInstanceOf('MongoCollection', $collections[1]);
        $this->assertSame('system.indexes', $collections[0]->getName());
        $this->assertSame('testDB', $collections[1]->getName());
    }

    public function testCommand()
    {
        $admin = $this->getTestClient()->selectDB('admin');

        $cmd = $admin->command(['buildinfo' => true]);
        $this->assertArrayHasKey('version', $cmd);
    }
}
