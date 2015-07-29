<?php

namespace Mongofill\Tests\Integration\Standalone;

use Mongo;
use MongoClient;

class MongoTest extends TestCase
{
    public function testConstruct()
    {
        $mongo = new Mongo();
        $this->assertTrue($mongo instanceof MongoClient);
    }
}
