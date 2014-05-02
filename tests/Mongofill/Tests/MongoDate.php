<?php

namespace Mongofill\Tests;

use MongoId;
use MongoDate;

class MongoDateTest extends TestCase
{

    public function testDateInsert()
    {
        $coll = $this->getTestDB()->selectCollection('testDateInsert');
        $coll->insert(
            [
                '_id' => new MongoId('000000000000000000000042'),
                'foo' => new MongoDate(12345678, 10)]
        );
        $result = iterator_to_array($coll->find());
        $this->assertCount(1, $result);
        $this->assertEquals(
            [
                '_id' => '000000000000000000000042',
                'foo' => new MongoDate(12345678, 10),
            ], $result[0]
        );
    }

}
