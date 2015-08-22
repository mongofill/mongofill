<?php

namespace Mongofill\Tests\Integration\ReplSet;

use PHPUnit_Framework_TestCase;
use MongoClient;

abstract class TestCase extends PHPUnit_Framework_TestCase
{
    const TEST_DB = 'mongofill-test';
    const REPLSET_NAME = 'mongofill';

    const DEFAULT_PRIMARY_HOST = 'localhost';
    const DEFAULT_SECONDARY_HOST = 'localhost';
    const DEFAULT_SECONDARY_TAGGED_HOST = 'localhost';
    const DEFAULT_HIDDEN_HOST = 'localhost';
    const DEFAULT_ARBITER_HOST = 'localhost';

    const DEFAULT_PRIMARY_PORT = 27020;
    const DEFAULT_SECONDARY_PORT = 27023;
    const DEFAULT_SECONDARY_TAGGED_PORT = 27026;
    const DEFAULT_HIDDEN_PORT = 27029;
    const DEFAULT_ARBITER_PORT = 27032;

    static protected $arbiter_host;
    static protected $hidden_host;
    static protected $secondary_tagged_host;
    static protected $secondary_host;
    static protected $primary_host;

    static protected $arbiter_port;
    static protected $hidden_port;
    static protected $secondary_tagged_port;
    static protected $secondary_port;
    static protected $primary_port;

    static protected $arbiter_server;
    static protected $hidden_server;
    static protected $secondary_tagged_server;
    static protected $secondary_server;
    static protected $primary_server;

    static protected $arbiter_conn_str;
    static protected $hidden_conn_str;
    static protected $secondary_tagged_conn_str;
    static protected $secondary_conn_str;
    static protected $primary_conn_str;

    static protected $replset_conn_str;

    /**
     * @var MongoClient
     */
    private $testClients = [];

    static public function setUpBeforeClass()
    {
        static::$arbiter_host =  (getenv('MONGODB_REPLSET_ARBITER_HOST') ?: static::DEFAULT_ARBITER_HOST);
        static::$hidden_host = (getenv('MONGODB_REPLSET_HIDDEN_HOST') ?: static::DEFAULT_HIDDEN_HOST);
        static::$secondary_tagged_host = (getenv('MONGODB_REPLSET_SECONDARY_TAGGED_HOST') ?: static::DEFAULT_SECONDARY_TAGGED_HOST);
        static::$secondary_host = (getenv('MONGODB_REPLSET_SECONDARY_HOST') ?: static::DEFAULT_SECONDARY_HOST);
        static::$primary_host = (getenv('MONGODB_REPLSET_PRIMARY_HOST') ?: static::DEFAULT_PRIMARY_HOST);

        static::$arbiter_port =  (getenv('MONGODB_REPLSET_ARBITER_PORT') ?: static::DEFAULT_ARBITER_PORT);
        static::$hidden_port = (getenv('MONGODB_REPLSET_HIDDEN_PORT') ?: static::DEFAULT_HIDDEN_PORT);
        static::$secondary_tagged_port = (getenv('MONGODB_REPLSET_SECONDARY_TAGGED_PORT') ?: static::DEFAULT_SECONDARY_TAGGED_PORT);
        static::$secondary_port = (getenv('MONGODB_REPLSET_SECONDARY_PORT') ?: static::DEFAULT_SECONDARY_PORT);
        static::$primary_port = (getenv('MONGODB_REPLSET_PRIMARY_PORT') ?: static::DEFAULT_PRIMARY_PORT);

        static::$arbiter_server = static::$arbiter_host . ':' . static::$arbiter_port;
        static::$hidden_server = static::$hidden_host . ':' . static::$hidden_port;
        static::$secondary_tagged_server = static::$secondary_tagged_host . ':' . static::$secondary_tagged_port;
        static::$secondary_server = static::$secondary_host . ':' . static::$secondary_port;
        static::$primary_server = static::$primary_host . ':' . static::$primary_port;

        static::$arbiter_conn_str = 'mongodb://' . static::$arbiter_server;
        static::$hidden_conn_str = 'mongodb://' . static::$hidden_server;
        static::$secondary_tagged_conn_str = 'mongodb://' . static::$secondary_tagged_server;
        static::$secondary_conn_str = 'mongodb://' . static::$secondary_server;
        static::$primary_conn_str = 'mongodb://' . static::$primary_server;

        static::$replset_conn_str = "mongodb://" . implode(',', [
            static::$arbiter_server, // Make sure our arbiter/hidden members come first in the hosts array
            static::$hidden_server,  // so we can confirm we change hosts appropriately from the seed list
            static::$secondary_tagged_server,
            static::$secondary_server,
            static::$primary_server
        ]);
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
    protected function getTestClient(array $options = [])
    {
        $key = json_encode($options);
        if (!isset($this->testClients[$key])) {

            if (!isset($options['replicaSet'])) {
                $options['replicaSet'] = static::REPLSET_NAME;
            }

            $this->testClients[$key] = new MongoClient(static::$replset_conn_str, $options);
        }

        return $this->testClients[$key];
    }

    /**
     * @return \MongoDB
     */
    public function getTestDB(\MongoClient $m = null)
    {
        if (!$m) {
            $m = $this->getTestClient();
        }

        return $m->selectDB(static::TEST_DB);
    }
}
