<?php

namespace Mongofill\Benchmarks;

use Athletic\AthleticEvent as Base;
use MongoClient;
use MongoDate;
use MongoRegex;
use MongoBinData;
use MongoTimestamp;

abstract class AthleticEvent extends Base
{
    const TEST_DB = 'mongofill-benchmark';

    private $testClient;
    private $testDB;

    protected function classSetUp()
    {
        parent::classSetUp();
        $this->getTestDB()->drop();
    }

    protected function classTearDown()
    {
        $this->testClient = null;
        parent::classTearDown();
    }

    protected function getTestClient()
    {
        if (!$this->testClient) {
            $this->testClient = new MongoClient();
        }

        return $this->testClient;
    }

    protected function getTestDB()
    {
        if (!$this->testDB) {
            $this->testDB = $this->getTestClient()->selectDB(self::TEST_DB);
        }

        return $this->testDB;
    }

    protected function buildSimpleDocument(array $initial = [])
    {
        return array_merge($initial, [
            'string' => 'foo',
            'int' => 32,
            'float' => 32.12,
            'field4' => 'value',
            'field5' => 'value',
            'field6' => 'value',
            'field7' => 'value',
            'field8' => 'value',
            'field9' => 'value',
        ]);
    }

    protected function buildSimpleNestedDocument(array $initial = [])
    {
        $record = $this->buildSimpleDocument($initial);
        $nested = $this->buildSimpleDocument();
        for($i=0; $i < 100; $i++) {
            $record['nested'][$i] = $nested;
        }

        return $record;
    }

    protected function buildComplexDocument(array $initial = [])
    {
        return array_merge($initial, [
            'string' => 'foo',
            'int' => 32,
            'float' => 32.12,
            'field4' => 'value',
            'field5' => 'value',
            'field6' => 'value',
            'field7' => 'value',
            'field8' => 'value',
            'field9' => 'value',
            'date' => new MongoDate(),
            'regexp' => new MongoRegex('/^Nicolas/i'),
            'bin' => new MongoBinData('foo', MongoBinData::GENERIC),
            'timestamp' => new MongoTimestamp()
        ]);
    }

    protected function buildComplexNestedDocument(array $initial = [])
    {
        $record = $this->buildComplexDocument($initial);
        $nested = $this->buildComplexDocument();
        for($i=0; $i < 100; $i++) {
            $record['nested'][$i] = $nested;
        }

        return $record;
    }
}
