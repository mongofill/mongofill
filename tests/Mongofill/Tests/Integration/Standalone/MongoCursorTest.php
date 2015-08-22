<?php

namespace Mongofill\Tests\Integration\Standalone;

class MongoCursorTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->coll = $this->getTestDB()->selectCollection('MongoCursorTest');
    }

    public function tearDown()
    {
        $this->coll->drop();
        parent::tearDown();
    }

    public function createNDocuments($n)
    {
        $documents = [];
        for($i = 1; $i <= $n; $i++) {
            $documents[] = [
                '_id' => str_pad($i, 24, '0', STR_PAD_LEFT),
                'foo' => $i,
            ];
        }

        // insert them
        $this->coll->batchInsert($documents);
    }

    public function testFind()
    {
        $this->createNDocuments(5);

        $result = iterator_to_array($this->coll->find(['foo' => 2]));
        $this->assertCount(1, $result);
        $this->assertSame(2, end($result)['foo']);
    }

    public function testFindWithSort()
    {
        $this->createNDocuments(5);

        $result = iterator_to_array($this->coll->find()->sort(['foo' => -1]));
        $this->assertCount(5, $result);
        $this->assertSame(1, end($result)['foo']);
    }

    /**
     * @group mongo24x
     * @group mongo26x
     */
    public function testFindWithExplainMongo24x26x()
    {
        $this->createNDocuments(5);
        $cursor = $this->coll->find(['foo' => 2]);

        $explain = $cursor->explain();
        $this->assertArrayHasKey('n', $explain);
    }

    /**
     * @group mongo30x
     */
    public function testFindWithExplainMongo30x()
    {
        $this->createNDocuments(5);
        $cursor = $this->coll->find(['foo' => 2]);

        $explain = $cursor->explain();
        $this->assertArrayHasKey('queryPlanner', $explain);
    }

    public function testFindWithFields()
    {
        $this->createNDocuments(5);

        $result = iterator_to_array(
            $this->coll->find(['foo' => 2])->fields(['_id' => 1])
        );

        $first = end($result);
        $this->assertCount(1, $result);
        $this->assertFalse(isset($first['foo']));
    }

    public function testFindWithHintString()
    {
        $this->createNDocuments(5);
        $this->coll->ensureIndex(['foo' => 1]);

        $result = iterator_to_array(
            $this->coll->find(['foo' => 2])->hint('foo_1')
        );

        $this->assertCount(1, $result);
        $this->assertSame(2, end($result)['foo']);
    }

    public function testFindWithHintArray()
    {
        $this->createNDocuments(5);
        $this->coll->ensureIndex(['foo' => 1]);

        $result = iterator_to_array(
            $this->coll->find(['foo' => 2])->hint(['foo' => 1])
        );

        $this->assertCount(1, $result);
        $this->assertSame(2, end($result)['foo']);
    }
    public function testFindFewDocuments()
    {
        $this->createNDocuments(5);
        $this->assertCount(5, $this->coll->find());
    }

    public function testFindFewDocumentsWithLimit()
    {
        $this->createNDocuments(200);
        $this->assertCount(120, $this->coll->find()->limit(120));
    }

    public function testFindFewDocumentsWithSkip()
    {
        $this->createNDocuments(150);

        $result = iterator_to_array($this->coll->find()->skip(110));
        $this->assertCount(40, $result);
        $this->assertSame(111, reset($result)['foo']);
    }

    public function testFindFewDocumentsWithSkipAndLimit()
    {
        $this->createNDocuments(230);

        $result = iterator_to_array($this->coll->find()->skip(110)->limit(110));
        $this->assertCount(110, $result);
        $this->assertSame(111, reset($result)['foo']);
    }

    public function testFindFewDocumentsWithSkipAndLimitAndBatchSize()
    {
        $this->createNDocuments(230);

        $result = iterator_to_array($this->coll->find()->skip(110)->limit(110)->batchSize(10));
        $this->assertCount(110, $result);
        $this->assertSame(111, reset($result)['foo']);
    }

    public function testFindFewDocumentsWithBatchSizeNegative()
    {
        $this->createNDocuments(230);
        $this->assertCount(10, $this->coll->find()->batchSize(-10));
    }

    public function testFindFewDocumentsWithLimitNegative()
    {
        $this->createNDocuments(230);
        $this->assertCount(10, $this->coll->find()->limit(-10));
    }

    public function testFindManyDocuments()
    {
        $this->createNDocuments(500);
        $this->assertCount(500, $this->coll->find());

        $i=0;
        foreach ($this->coll->find() as $key => $record) {
            $this->assertSame($key, $record['_id']);
            $this->assertSame(++$i, $record['foo']);
        }
    }

    public function testCount()
    {
        $this->createNDocuments(500);
        $this->assertSame(500, $this->coll->find()->limit(10)->count());
    }

    public function testReset()
    {
        $this->createNDocuments(500);

        $result = $this->coll->find();
        $this->assertSame(10, $result->limit(10)->count(true));

        $result->reset();
        $this->assertSame(20, $result->limit(20)->count(true));

    }

    public function testGetNext()
    {
        $this->createNDocuments(10);

        $result = $this->coll->find();

        $record = $result->getNext();
        $this->assertSame(1, $record['foo']);

        $record = $result->getNext();
        $this->assertSame(2, $record['foo']);

        $result->rewind();
        $record = $result->getNext();
        $this->assertSame(2, $record['foo']);
    }

    public function testHasNext()
    {
        $this->createNDocuments(1);
        $result = $this->coll->find();

        $this->assertTrue($result->hasNext());

        $result->next();
        $this->assertFalse($result->hasNext());
    }

    public function testCountWithSkip()
    {
        $this->createNDocuments(500);
        $this->assertSame(500, $this->coll->find()->limit(10)->skip(10)->count());
    }

    public function testCountFoundOnly()
    {
        $this->createNDocuments(55);
        $this->assertSame(43, $this->coll->find(['foo' => ['$gt' => 2]])->skip(10)->count(true));
    }

    public function testCountNotFoundOnly()
    {
        $this->createNDocuments(55);
        $this->assertSame(53, $this->coll->find(['foo' => ['$gt' => 2]])->skip(10)->count(false));
    }

    public function testCountNotFoundOnlyWithSort()
    {
        $this->createNDocuments(55);
        $this->assertSame(53, $this->coll
            ->find(['foo' => ['$gt' => 2]])
            ->sort(['foo' => 1])
            ->skip(10)->count(false)
        );
    }

    public function testCountFoundOnlyUnderGetMore()
    {
        $this->createNDocuments(55);
        $this->assertSame(55, $this->coll->find()->count(true));
    }

    public function testCountFoundOnlyOverGetMore()
    {
        $this->createNDocuments(500);
        $this->assertSame(500, $this->coll->find()->count(true));
    }

    public function testInfo()
    {
        $info = $this->coll->find()->info();
        $this->assertSame(self::TEST_DB . '.MongoCursorTest', $info['ns']);
        $this->assertCount(0, $info['query']);
        $this->assertCount(0, $info['fields']);
        $this->assertSame(0, $info['limit']);
        $this->assertSame(0, $info['skip']);
        $this->assertFalse($info['started_iterating']);
    }

    public function testInfoFilled()
    {
        $query = ['foo' => 'bar'];
        $fields = ['foo' => 1];
        $info = $this->coll
            ->find($query, $fields)
            ->skip(1)
            ->limit(2)
            ->info();

        $this->assertSame(self::TEST_DB . '.MongoCursorTest', $info['ns']);
        $this->assertSame($query, $info['query']);
        $this->assertSame($fields, $info['fields']);
        $this->assertSame(2, $info['limit']);
        $this->assertSame(1, $info['skip']);
        $this->assertFalse($info['started_iterating']);
    }

    public function testInfoInitiated()
    {
        $cursor = $this->coll->find();
        $cursor->getnext();

        $info = $cursor->info();

        $this->assertTrue($info['started_iterating']);
    }

    public function testComplexCursorBehavior()
    {
        $data = ['name' => 'test'];

        $collecion = $this->getTestDB()->selectCollection('MongoCursorTest');
        $collecion->insert($data);

        $cursor = $collecion->find()->limit(1);

        $this->assertSame(1, $cursor->count());
        $this->assertNull($cursor->current());
        $this->assertTrue($cursor->hasNext());
        $this->assertTrue((bool) $cursor->getNext());
        $this->assertTrue((bool) $cursor->current());
        $this->assertFalse($cursor->hasNext());
    }

    public function testRewind()
    {
        $data = ['name' => 'test'];

        $collecion = $this->getTestDB()->selectCollection('MongoCursorTest');
        $collecion->insert($data);

        $cursor = $collecion->find()->limit(1);

        $this->assertNull($cursor->current());
        $this->assertTrue($cursor->hasNext());

        $cursor->next();
        $this->assertTrue((bool) $cursor->current());
        $this->assertFalse($cursor->hasNext());

        $cursor->rewind();
        $this->assertTrue((bool) $cursor->current());
        $this->assertFalse($cursor->hasNext());
    }

    public function testEmptyResult()
    {

        $collecion = $this->getTestDB()->selectCollection('MongoCursorTest');

        $cursor = $collecion->find()->limit(1);

foreach ($cursor as $key => $value) {
    var_dump($value);
}

        $this->assertNull($cursor->current());
        $this->assertFalse($cursor->hasNext());
    }
}
