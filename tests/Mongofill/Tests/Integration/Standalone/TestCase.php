<?php

namespace Mongofill\Tests\Integration\Standalone;

use PHPUnit_Framework_TestCase;
use MongoClient;

abstract class TestCase extends PHPUnit_Framework_TestCase
{
    const TEST_DB = 'mongofill-test';
    const DEFAULT_STANDALONE_HOST = 'localhost';
    const DEFAULT_STANDALONE_PORT = 27017;

    protected static $host;
    protected static $port;
    protected static $server;
    protected static $conn_str;

    /**
     * @var MongoClient
     */
    private $testClient;

    static public function setUpBeforeClass()
    {
        static::$host = (getenv('MONGODB_STANDALONE_HOST') ?: static::DEFAULT_STANDALONE_HOST);
        static::$port = (getenv('MONGODB_STANDALONE_PORT') ?: static::DEFAULT_STANDALONE_PORT);
        static::$server = static::$host . ':' . static::$port;
        static::$conn_str = 'mongodb://' . static::$server;
    }

    protected function setUp()
    {
        parent::setUp();
        $this->getTestDB()->drop();
    }

    protected function tearDown()
    {
        $this->testClient = null;
        parent::tearDown();
    }

    /**
     * @return \MongoClient
     */
    protected function getTestClient()
    {
        if (!$this->testClient) {
            $this->testClient = new MongoClient(static::$conn_str);
        }
        return $this->testClient;
    }

    /**
     * @return \MongoDB
     */
    public function getTestDB()
    {
        return $this->getTestClient()->selectDB(static::TEST_DB);
    }
}
