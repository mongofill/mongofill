<?php

namespace Mongofill\Tests;

use Mongo;
use MongoClient;

class MongoTest extends TestCase
{
    public function testConstruct()
    {
        $mongo = new Mongo();
        $this->assertTrue($mongo instanceOf MongoClient);
    }
}
