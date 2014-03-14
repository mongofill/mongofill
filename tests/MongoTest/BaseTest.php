<?php

abstract class BaseTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var MongoClient
     */
    private  $testClient;

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
     * @return MongoClient
     */
    protected  function getTestClient()
    {
        if (!$this->testClient) {
            $this->testClient = new MongoClient();
        }
        return $this->testClient;
    }

    /**
     * @return MongoDB
     */
    public function getTestDB()
    {
        return $this->getTestClient()->selectDB(TEST_DB);
    }
}