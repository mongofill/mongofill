<?php

namespace Mongofill\Tests\Unit;

use PHPUnit_Framework_TestCase;
use MongoClient;

abstract class TestCase extends PHPUnit_Framework_TestCase
{

    /**
     * @var MongoClient
     */
    private $testClient;

    protected function setUp()
    {
        parent::setUp();
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    /**
     * @return \MongoClient
     */
    protected function getTestClient()
    {
        if (!$this->testClient) {
            $this->testClient = new MongoClient();
        }
        return $this->testClient;
    }
}
