<?php

namespace Mongofill\Tests\Integration\Standalone;

use MongoDBRef;

class MongoDBRefTest extends TestCase
{
    public function testDBRef()
    {
        $mongo = $this->getTestClient();
        $db = $this->getTestDB();

        $coll1 = $mongo->selectCollection(self::TEST_DB, 'dbref');
        $coll1->drop();
        $doc = ['_id' => 123, 'x' => 'foo'];
        $coll1->insert($doc);

        $coll2 = $mongo->selectCollection('test2', 'dbref2');
        $coll2->drop();
        $doc = ['_id' => 456, 'x' => 'bar'];
        $coll2->insert($doc);

        $result = MongoDBRef::get($db, MongoDBRef::create('dbref', 123));
        $this->assertEquals('foo', $result['x']);

        $result = MongoDBRef::get($db, MongoDBRef::create('dbref2', 456, 'test2'));
        $this->assertEquals('bar', $result['x']);
    }

    public function testDBRefError()
    {
        $db = $this->getTestDB();
        $this->assertNull(MongoDBRef::get($db, null));
        $this->assertNull(MongoDBRef::get($db, []));
        $this->assertNull(MongoDBRef::get($db, ['$ref' => 'dbref']));
        $this->assertNull(MongoDBRef::get($db, ['$id' => 123]));
    }
}
