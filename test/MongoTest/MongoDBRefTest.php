<?php

class MongoDBRefTest extends BaseTest
{
    function testDBRef()
    {
        $mongo = $this->getTestClient();
        $db = $this->getTestDB();
        
        $coll1 = $mongo->selectCollection(TEST_DB, 'dbref');
        $coll1->drop();
        $coll1->insert($doc = array('_id' => 123, 'x' => 'foo'));

        $coll2 = $mongo->selectCollection('test2', 'dbref2');
        $coll2->drop();
        $coll2->insert($doc = array('_id' => 456, 'x' => 'bar'));

        $result = MongoDBRef::get($db, MongoDBRef::create('dbref', 123));
        $this->assertEquals('foo', $result['x']);

        $result = MongoDBRef::get($db, MongoDBRef::create('dbref2', 456, 'test2'));
        $this->assertEquals('bar', $result['x']);
    }

    function testDBRefError()
    {
        $db = $this->getTestDB();
        $this->assertNull(MongoDBRef::get($db, null));
        $this->assertNull(MongoDBRef::get($db, array()));
        $this->assertNull(MongoDBRef::get($db, array('$ref' => 'dbref')));
        $this->assertNull(MongoDBRef::get($db, array('$id' => 123)));
    }
}
