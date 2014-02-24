<?php

class MongoCursorTest extends BaseTest
{
    public function testFindFewDocuments()
    {
        $this->findNDocuments(5);
    }

    public function testFindManyDocuments()
    {
        $this->findNDocuments(500);
    }

    private function findNDocuments($n)
    {
        $coll = $this->getTestDB()->selectCollection('testFindFewDocuments');
        $documents = [];

        // prepare documents to insert
        for($i = 1; $i <= $n; $i++) {
            $documents[] = [
                '_id' => str_pad($i, 24, '0', STR_PAD_LEFT),
                'foo' => $i,
            ];
        }

        // insert them
        $coll->batchInsert($documents);

        // load them back and check result
        $result = iterator_to_array($coll->find());
        $this->assertEquals($documents, $result);
    }
}