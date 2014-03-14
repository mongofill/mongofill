<?php

class MongoRegexTest extends PHPUnit_Framework_TestCase
{
    function testConstruct()
    {
        $regex = new MongoRegex('/foo/i');
        $this->assertSame('foo', $regex->regex);
        $this->assertSame('i', $regex->flags);
    }

    /**
     * @expectedException MongoException
     */
    function testConstructInvalidRegex()
    {
        new MongoRegex('0123456789abcdef01234567');
    }
}
 