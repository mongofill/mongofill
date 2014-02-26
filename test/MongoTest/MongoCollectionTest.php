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

    function testDateInsert()
    {
        $coll = $this->getTestDB()->selectCollection('testDateInsert');
        $coll->insert([
                '_id' => new MongoId('000000000000000000000042'),
                'foo' => new MongoDate(12345678, 10)]);
        $result = iterator_to_array($coll->find());
        $this->assertCount(1, $result);
        $this->assertEquals([
                '_id' => '000000000000000000000042',
                'foo' => new MongoDate(12345678,10),
            ], $result[0]);
    }
}
