<?php

class MongoCursorTest extends BaseTest
{
    public function setUp()
    {
        $this->coll = $this->getTestDB()->selectCollection('testFindFewDocuments');
    }

    public function tearDown()
    {
        $this->coll->drop();
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
    
    public function testFindWithExplain()
    {
        $this->createNDocuments(5);
        $cursor = $this->coll->find(['foo' => 2]);

        $explain = $cursor->explain();
        $this->assertArrayHasKey('server', $explain);
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

   public function testCountWithSkip()
    {
        $this->createNDocuments(500);
        $this->assertSame(500, $this->coll->find()->limit(10)->skip(10)->count());
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
}