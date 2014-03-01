<?php

class MongoCollectionTest extends BaseTest
{
    function testInsert()
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

    function testRemove()
    {
        $coll = $this->getTestDB()->selectCollection('testDelete');
        $coll->insert([
            '_id' => new MongoId('000000000000000000000001'),
            'foo' => 'bar'
        ]);

        $coll->insert([
            '_id' => new MongoId('000000000000000000000002'),
            'foo' => 'qux'
        ]);

        $this->assertCount(2, $coll->find());

        $coll->remove(['foo' => 'qux']);
        $result = iterator_to_array($coll->find());
        $this->assertCount(1, $result);
        $this->assertSame('bar', $result[0]['foo']);

        $coll->insert([
            '_id' => new MongoId('000000000000000000000003'),
            'foo' => 'qux'
        ]);

        $coll->insert([
            '_id' => new MongoId('000000000000000000000004'),
            'foo' => 'qux'
        ]);
        
        $this->assertCount(3, $coll->find());

        $coll->remove(['foo' => 'qux']);
        $result = iterator_to_array($coll->find());
        $this->assertCount(1, $result);
        $this->assertSame('bar', $result[0]['foo']);
    }

    function testDrop()
    {
        $coll = $this->getTestDB()->selectCollection('testDrop');
        $coll->insert([
                '_id' => new MongoId('000000000000000000000042'),
                'foo' => 'bar' ]);
        $this->assertEquals(1, $coll->count());
        $coll->drop();
        $this->assertEquals(0, $coll->count());
    }

    function testCount()
    {
        $coll = $this->getTestDB()->selectCollection('testCount');
        $coll->drop();
        $this->assertEquals(0, $coll->count());
        $coll->insert([
                '_id' => new MongoId('000000000000000000000042'),
                'foo' => 'bar' ]);
        $this->assertEquals(1, $coll->count());
     }

    function testGetName()
    {
        $coll = $this->getTestDB()->selectCollection('testGetName');
        $this->assertEquals('testGetName', $coll->getName());
     }

    function testUpdate()
    {
        $coll = $this->getTestDB()->selectCollection('testUpdate');
        $coll->insert(['foo'=>'bar']);
        $result = iterator_to_array($coll->find());
        $this->assertCount(1, $result);
        $this->assertEquals('bar', $result[0]['foo']);
        $result[0]['foo'] = 'notbar';
        $coll->update(['_id'=>$result[0]['_id']], ['$set'=>['foo'=>'notbar']]);
        $result = iterator_to_array($coll->find(['_id'=>$result[0]['_id']]));
        $this->assertCount(1, $result);
        $this->assertEquals('notbar', $result[0]['foo']);
     }

    function testSave()
    {
        $coll = $this->getTestDB()->selectCollection('testUpdate');
        $coll->insert(['foo'=>'bar']);
        $result = iterator_to_array($coll->find());
        $this->assertCount(1, $result);
        $this->assertEquals('bar', $result[0]['foo']);
        $result[0]['foo'] = 'notbar';
        $coll->save($result[0]);
        $result = iterator_to_array($coll->find(['_id'=>$result[0]['_id']]));
        $this->assertCount(1, $result);
        $this->assertEquals('notbar', $result[0]['foo']);
    }

}
