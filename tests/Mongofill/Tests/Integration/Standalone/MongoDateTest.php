<?php

namespace Mongofill\Tests\Integration\Standalone;

use MongoId;
use MongoDate;

class MongoDateTest extends TestCase
{

    public function testDateInsert()
    {
        $coll = $this->getTestDB()->selectCollection('testDateInsert');
        $doc = [
            '_id' => new MongoId('000000000000000000000042'),
            'foo' => new MongoDate(12345678, 10)
         ];
        $coll->insert($doc);
        $result = iterator_to_array($coll->find());
        $this->assertCount(1, $result);
        $result = reset($result);
        $this->assertArrayHasKey('_id', $result);
        $this->assertArrayHasKey('foo', $result);
        $this->assertEquals(
            [
                '_id' => '000000000000000000000042',
                'foo' => new MongoDate(12345678, 10),
            ], $result
        );
    }
}
