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

    public function testFindFewDocuments()
    {
        $this->createNDocuments(5);
        $this->assertCount(5, $this->coll->find());
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