<?php

namespace Mongofill\Tests\Integration\Standalone;

use MongoId;
use MongoCollection;

class MongoCollectionTest extends TestCase
{
    public function testInsert()
    {
        $coll = $this->getTestDB()->selectCollection(__FUNCTION__);

        $data = [
            'foo' => 'bar',
            'boolean' => false
        ];

        $result = $coll->insert($data);
        $this->assertSame(1, (int) $result['ok']);

        $this->assertCount(1, $coll->find());
        $this->assertEquals($data, $coll->findOne());
    }

    /**
     * @expectedException MongoCursorException
     */
    public function testInsertError()
    {
        $coll = $this->getTestDB()->selectCollection(__FUNCTION__);

        $data = [
            'foo' => 'bar',
            'boolean' => false
        ];

        $coll->insert($data);
        $coll->insert($data);
    }

    /**
     * @expectedException MongoCursorTimeoutException
     */
    public function testInsertTimeout()
    {
        $coll = $this->getTestDB()->selectCollection(__FUNCTION__);

        $data = [
            'foo' => 'bar',
            'boolean' => false
        ];

        $coll->insert($data, ['timeout' => 1]);
    }

    public function testInsertDuplicateW0()
    {
        $coll = $this->getTestDB()->selectCollection(__FUNCTION__);

        $data = [
            'foo' => 'bar',
            'boolean' => false
        ];

        $this->assertTrue($coll->insert($data, ['w' => 0]));
        $this->assertTrue($coll->insert($data, ['w' => 0]));
    }

    /**
     * @expectedException MongoCursorException
     */
    public function testInsertDuplicateJTrue()
    {
        $coll = $this->getTestDB()->selectCollection(__FUNCTION__);

        $data = [
            'foo' => 'bar',
            'boolean' => false
        ];

        $this->assertTrue($coll->insert($data, ['w' => 0]));
        $coll->insert($data, ['j' => true, 'w' => 0]);
    }

    /**
     * @dataProvider provideNonUtf8Data
     * @expectedException MongoException
     * @expectedExceptionMessage non-utf8 string
     * @expectedExceptionCode MongoException::NON_UTF_STRING
     */
    public function testInsertNonUtf8($data)
    {
        $coll = $this->getTestDB()->selectCollection(__FUNCTION__);

        $coll->insert($data);
    }

    public function provideNonUtf8Data()
    {
        return [
            [['_id' => "\xFE\xF0"]],
            [['x' => "\xFE\xF0"]],
            [(object) ['x' => "\xFE\xF0"]],
            [['x' => new \MongoCode('return y;', ['y' => "\xFE\xF0"])]],
        ];
    }

    public function testBatchInsert()
    {
        $coll = $this->getTestDB()->selectCollection(__FUNCTION__);

        $data = [
            ['foo' => 'bar'],
            ['foo' => 'qux']
        ];

        $coll->batchInsert($data);

        $this->assertCount(2, $coll->find());
        $this->assertEquals($data[0], $coll->findOne());
        $this->assertInstanceOf('MongoId', $data[0]['_id']);
        $this->assertInstanceOf('MongoId', $data[1]['_id']);
    }

    public function testBatchInsertWithKeys()
    {
        $coll = $this->getTestDB()->selectCollection(__FUNCTION__);

        $data = [
            'foo' => ['foo' => 'bar'],
            'bar' => ['foo' => 'qux']
        ];

        $coll->batchInsert($data);

        $this->assertCount(2, $coll->find());
        $this->assertEquals($data['foo'], $coll->findOne());
        $this->assertInstanceOf('MongoId', $data['foo']['_id']);
        $this->assertInstanceOf('MongoId', $data['bar']['_id']);
    }

    public function testInsertWithId()
    {
        $coll = $this->getTestDB()->selectCollection(__FUNCTION__);

        $data = [
            '_id' => new MongoId('000000000000000000000042'),
            'foo' => 'bar'
        ];

        $coll->insert($data);
        $this->assertCount(1, $coll->find());
        $this->assertEquals($data, $coll->findOne());
    }

    public function testRemove()
    {
        $coll = $this->getTestDB()->selectCollection(__FUNCTION__);

        $data = [
            '_id' => new MongoId('000000000000000000000001'),
            'foo' => 'bar'
        ];
        $coll->insert($data);

        $data = [
            '_id' => new MongoId('000000000000000000000002'),
            'foo' => 'qux'
        ];
        $coll->insert($data);

        $this->assertCount(2, $coll->find());

        $coll->remove(['foo' => 'qux']);
        $result = iterator_to_array($coll->find());
        $this->assertCount(1, $result);
        $this->assertSame('bar', current($result)['foo']);

        $data = [
            '_id' => new MongoId('000000000000000000000003'),
            'foo' => 'qux'
        ];
        $coll->insert($data);

        $data = [
            '_id' => new MongoId('000000000000000000000004'),
            'foo' => 'qux'
        ];
        $coll->insert($data);

        $this->assertCount(3, $coll->find());

        $result = $coll->remove(['foo' => 'qux']);
        $this->assertSame(1, (int) $result['ok']);

        $result = iterator_to_array($coll->find());
        $this->assertCount(1, $result);
        $this->assertSame('bar', current($result)['foo']);
    }

    public function testRemoveW0()
    {
        $coll = $this->getTestDB()->selectCollection(__FUNCTION__);
        $result = $coll->remove(['foo' => 'qux'], ['w' => 0]);
        $this->assertTrue($result);
    }

    /**
     * @expectedException MongoException
     * @expectedExceptionMessage non-utf8 string
     * @expectedExceptionCode MongoException::NON_UTF_STRING
     */
    public function testRemoveNonUtf8Criteria()
    {
        $coll = $this->getTestDB()->selectCollection(__FUNCTION__);

        $coll->remove(['foo' => "\xFE\xF0"]);
    }

    public function testDrop()
    {
        $coll = $this->getTestDB()->selectCollection(__FUNCTION__);

        $data = [
            '_id' => new MongoId('000000000000000000000042'),
            'foo' => 'bar'
        ];

        $coll->insert($data);

        $this->assertEquals(1, $coll->count());
        $coll->drop();
        $this->assertEquals(0, $coll->count());
    }

    public function testCount()
    {
        $coll = $this->getTestDB()->selectCollection(__FUNCTION__);
        $coll->drop();
        $this->assertEquals(0, $coll->count());

        $data = [
            '_id' => new MongoId('000000000000000000000042'),
            'foo' => 'bar'
        ];

        $coll->insert($data);

        $this->assertEquals(1, $coll->count());
     }

    public function testGetName()
    {
        $coll = $this->getTestDB()->selectCollection(__FUNCTION__);
        $this->assertEquals(__FUNCTION__, $coll->getName());
    }

    public function testUpdate()
    {
        $coll = $this->getTestDB()->selectCollection(__FUNCTION__);

        $data = ['foo' => 'bar'];
        $coll->insert($data);

        $data = ['foo' => 'bar'];
        $coll->insert($data);

        $this->assertSame(2, $coll->find(['foo' => 'bar'])->count());

        $result = $coll->update(['foo' =>  'bar'], [
            '$set' => ['foo' => 'notbar']
        ], ['multiple' => true]);

        $this->assertSame(1, (int) $result['ok']);
        $this->assertSame(2, $coll->find(['foo' => 'notbar'])->count());
    }

    public function testUpdateMulti()
    {
        $coll = $this->getTestDB()->selectCollection(__FUNCTION__);

        $data = ['foo' => 'bar'];
        $coll->insert($data);

        $data = ['foo' => 'bar'];
        $coll->insert($data);

        $this->assertSame(2, $coll->find(['foo' => 'bar'])->count());

        $coll->update(['foo' =>  'bar'], [
            '$set' => ['foo' => 'notbar']
        ]);

        $this->assertSame(1, $coll->find(['foo' => 'bar'])->count());
        $this->assertSame(1, $coll->find(['foo' => 'notbar'])->count());
    }

    /**
     * @expectedException MongoCursorException
     */
    public function testUpdateException()
    {
        $coll = $this->getTestDB()->selectCollection(__FUNCTION__);
        $coll->update(['foo' =>  'bar'], [
            '$setaaa' => ['foo' => 'notbar']
        ], ['multiple' => true]);
    }

    public function testUpdateExceptionW0()
    {
        $coll = $this->getTestDB()->selectCollection(__FUNCTION__);
        $result = $coll->update(['foo' =>  'bar'], [
            '$set' => ['foo' => 'notbar']
        ], ['w' => 0]);
        $this->assertTrue($result);

        $result = $coll->update(['foo' =>  'bar'], [
            '$setaaa' => ['foo' => 'notbar']
        ], ['w' => 0]);
    }

    /**
     * @expectedException MongoException
     * @expectedExceptionMessage non-utf8 string
     * @expectedExceptionCode MongoException::NON_UTF_STRING
     */
    public function testUpdateNonUtf8Criteria()
    {
        $coll = $this->getTestDB()->selectCollection(__FUNCTION__);

        $doc = ['_id' => 1, 'foo' => 'bar'];
        $coll->insert($doc);

        $coll->update(['foo' => "\xFE\xF0"], ['$set' => ['foo' => 'bar']]);
    }

    /**
     * @expectedException MongoException
     * @expectedExceptionMessage non-utf8 string
     * @expectedExceptionCode MongoException::NON_UTF_STRING
     */
    public function testUpdateNonUtf8Document()
    {
        $coll = $this->getTestDB()->selectCollection(__FUNCTION__);

        $doc = ['_id' => 1, 'foo' => 'bar'];
        $coll->insert($doc);

        $coll->update(['_id' => 1], ['$set' => ['foo' => "\xFE\xF0"]]);
    }

    public function testSave()
    {
        $data = ['foo'=>'bar'];

        $coll = $this->getTestDB()->selectCollection(__FUNCTION__);
        $coll->insert($data);
        $result = iterator_to_array($coll->find());
        $this->assertCount(1, $result);

        $record = current($result);
        $this->assertEquals('bar', $record['foo']);
        $record['foo'] = 'notbar';
        $coll->save($record);


        $result = iterator_to_array($coll->find(['_id'=> $record['_id']]));
        $this->assertCount(1, $result);
        $this->assertEquals('notbar', $record['foo']);
    }

    public function testSaveUpdateWithId()
    {
        $data = ['_id' => new MongoId(), 'foo' => 'bar'];

        $coll = $this->getTestDB()->selectCollection(__FUNCTION__);
        $coll->save($data);

        $result = iterator_to_array($coll->find(['_id'=> $data['_id']]));
        $this->assertCount(1, $result);
        $this->assertEquals('bar', $data['foo']);
    }

    public function testEnsureIndex()
    {
        $data = ['foo'=>'bar'];

        $coll = $this->getTestDB()->selectCollection(__FUNCTION__);
        $coll->insert($data);

        $index = ['foo' => -1, 'bar' => 1];

        $coll = $this->getTestDB()->selectCollection(__FUNCTION__);
        $coll->deleteIndexes();

        $indexes = $coll->getIndexInfo();

        $this->assertCount(1, $indexes);
    }

    public function testEnsureDeleteIndex()
    {
        $data = ['foo'=>'bar'];

        $coll = $this->getTestDB()->selectCollection(__FUNCTION__);
        $coll->insert($data);

        $index = ['foo' => -1, 'bar' => 1];

        $coll = $this->getTestDB()->selectCollection(__FUNCTION__);
        $this->assertTrue($coll->ensureIndex($index));
        $this->assertCount(2, $coll->getIndexInfo());

        $coll->deleteIndex($index);
        $this->assertCount(1, $coll->getIndexInfo());
    }

    public function testToIndexString()
    {
        $expected = 'foo_-1_bar_1';
        $index = ['foo' => -1, 'bar' => 1];

        $result = MongoCollectionWrapper::toIndexString($index);
        $this->assertSame($expected, $result);
    }

    public function testToIndexStringText()
    {
        $expected = 'qux_text_baz_text';
        $index = [
            'foo' => -1,
            'bar' => 1,
            'weights' => [
                'qux' => 30,
                'baz' => 10
            ]
        ];

        $result = MongoCollectionWrapper::toIndexString($index);
        $this->assertSame($expected, $result);
    }

    public function testFindAndModify()
    {
        $coll = $this->getTestDB()->selectCollection(__FUNCTION__);
        $record = $coll->findAndModify(
            ['name'=>'bar'],
            ['$inc' => ['value' => 1]],
            null,
            ['new' => false, 'upsert' => true]
        );

        $this->assertSame([], $record);

        $result = iterator_to_array($coll->find());
        $this->assertCount(1, $result);

        $record = current($result);

        $this->assertEquals('bar', $record['name']);
        $this->assertEquals(1, $record['value']);

        $record = $coll->findAndModify(
            ['name'=>'bar'],
            ['$inc' => ['value' => 1]]
        );

        $this->assertEquals(1, $record['value']);

        $record = $coll->findOne(['name'=>'bar']);

        $this->assertEquals('bar', $record['name']);
        $this->assertEquals(2, $record['value']);
    }

    public function testAggregate()
    {
        $coll = $this->getTestDB()->selectCollection(__FUNCTION__);
        $this->createDataForAggregateTest($coll);

        $ops = [
            ['$project' => ['author' => 1, 'tags' => 1]],
            ['$unwind' => '$tags'],
            ['$group' => [
                '_id' => ['tags' => '$tags'],
                'authors' => ['$addToSet' => '$author']
            ]],
        ];

        $results = $coll->aggregate($ops);
        $this->assertSame(1.0, $results['ok']);
        $this->assertCount(2, $results['result']);
        $this->assertSame('good', $results['result'][0]['_id']['tags']);
        $this->assertSame('fun', $results['result'][1]['_id']['tags']);
    }

    public function testAggregateSplit()
    {
        $coll = $this->getTestDB()->selectCollection(__FUNCTION__);
        $this->createDataForAggregateTest($coll);

        $results = $coll->aggregate(
            ['$project' => ['author' => 1, 'tags' => 1]],
            ['$unwind' => '$tags'],
            ['$group' => [
                '_id' => ['tags' => '$tags'],
                'authors' => ['$addToSet' => '$author']
            ]]
        );

        $this->assertSame(1.0, $results['ok']);
        $this->assertCount(2, $results['result']);
        $this->assertSame('good', $results['result'][0]['_id']['tags']);
        $this->assertSame('fun', $results['result'][1]['_id']['tags']);
    }

    private function createDataForAggregateTest(\MongoCollection $coll)
    {
        $data = [
            'title' => 'this is my title',
            'author' => 'bob',
            'posted' => new \MongoDate(),
            'pageViews' => 5,
            'tags' => ['fun', 'good', 'fun'],
        ];

        $coll->insert($data);
    }

    public function testDistinct()
    {
        $docs = [
            ['stuff' => 'bar', 'zip-code' => 10010],
            ['stuff' => 'foo', 'zip-code' => 99701],
            ['stuff' => 'bar', 'zip-code' => 10010]
        ];

        $coll = $this->getTestDB()->selectCollection(__FUNCTION__);
        $coll->batchInsert($docs);

        $results = $coll->distinct('zip-code');
        $this->assertCount(2, $results);
        $this->assertTrue(in_array(10010, $results));
        $this->assertTrue(in_array(99701, $results));
    }

    public function testDistinctWithQuery()
    {
        $docs = [
            ['stuff' => 'bar', 'zip-code' => 10010],
            ['stuff' => 'foo', 'zip-code' => 99701],
            ['stuff' => 'bar', 'zip-code' => 10010]
        ];

        $coll = $this->getTestDB()->selectCollection(__FUNCTION__);
        $coll->batchInsert($docs);

        $results = $coll->distinct('zip-code', ['stuff' => 'foo']);
        $this->assertCount(1, $results);
        $this->assertTrue(in_array(99701, $results));
    }


    public function testGroup()
    {
        $docs = [
            ['category' => 'fruit', 'name' => 'apple'],
            ['category' => 'fruit', 'name' => 'peach'],
            ['category' => 'fruit', 'name' => 'banana'],
            ['category' => 'veggie', 'name' => 'corn'],
            ['category' => 'veggie', 'name' => 'broccoli']
        ];

        $keys = ['category' => 1];
        $initial = ['items' => []];
        $reduce = 'function (obj, prev) { prev.items.push(obj.name); }';


        $coll = $this->getTestDB()->selectCollection(__FUNCTION__);
        $coll->batchInsert($docs);
        $result = $coll->group($keys, $initial, $reduce);
        $this->assertCount(2, $result['retval']);
        $this->assertEquals(1, $result['ok']);
    }

    public function testSortConstantsExist()
    {
        $this->assertSame(1,  MongoCollection::ASCENDING);
        $this->assertSame(-1, MongoCollection::DESCENDING);
    }
}

class MongoCollectionWrapper extends MongoCollection
{
    public static function toIndexString($keys)
    {
        return parent::toIndexString($keys);
    }
}
