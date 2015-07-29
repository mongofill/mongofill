<?php

namespace Mongofill\Tests\Integration\Standalone;

use MongoRegex;

class MongoRegexTest extends TestCase
{
    public function testConstruct()
    {
        $regex = new MongoRegex('/foo/i');
        $this->assertSame('foo', $regex->regex);
        $this->assertSame('i', $regex->flags);
    }

    /**
     * @expectedException MongoException
     */
    public function testConstructInvalidRegex()
    {
        new MongoRegex('0123456789abcdef01234567');
    }
}
