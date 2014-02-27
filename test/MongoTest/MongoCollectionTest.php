<?php

class MongoCollectionTest extends BaseTest
{
    function test_insert()
    {
        $coll = $this->getTestDB()->selectCollection('testInsert');
        $coll->insert([
            '_id' => new MongoId('000000000000000000000042'),
            'foo' => 'bar' ]);
        $result = iterator_to_array($coll->find());
        $this->assertCount(1, $result);
        $this->assertEquals([
            '_id' => '000000000000000000000042',
            'foo' => 'bar',
        ], $result[0]);
    }

    function test_drop()
    {
        $coll = $this->getTestDB()->selectCollection('testDrop');
        $coll->insert([
                '_id' => new MongoId('000000000000000000000042'),
                'foo' => 'bar' ]);
        $this->assertEquals(1, $coll->count());
        $coll->drop();
        $this->assertEquals(0, $coll->count());
    }

    function test_count()
    {
        $coll = $this->getTestDB()->selectCollection('testCount');
        $coll->drop();
        $this->assertEquals(0, $coll->count());
        $coll->insert([
                '_id' => new MongoId('000000000000000000000042'),
                'foo' => 'bar' ]);
        $this->assertEquals(1, $coll->count());
     }

    function test_getName()
    {
        $coll = $this->getTestDB()->selectCollection('testGetName');
        $this->assertEquals('testGetName', $coll->getName());
     }

}
