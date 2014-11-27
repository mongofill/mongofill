<?php

namespace Mongofill\Benchmarks;

use MongoClient;
use MongoId;

class UpdatingEvent extends AthleticEvent
{
    const EXAMPLE_ID = '53233675acf164c2f80041a7';

    protected function classSetUp()
    {
        parent::classSetUp();
        $record = $this->buildSimpleDocument([
            '_id' => new MongoId(self::EXAMPLE_ID)
        ]);

        $this->getTestDB()->test->insert($record);
    }

    /**
     * @iterations 1000
     */
    public function simpleDocument()
    {
        $query = [
            '_id' => new MongoId(self::EXAMPLE_ID)
        ];

        $update = [
            '$set' => ['foo' => $this->buildSimpleDocument()]
        ];

        $this->getTestDB()->test->update($query, $update);
    }

    /**
     * @iterations 1000
     */
    public function simpleNestedDocument()
    {
        $query = [
            '_id' => new MongoId(self::EXAMPLE_ID)
        ];

        $update = [
            '$set' => ['foo' => $this->buildSimpleNestedDocument()]
        ];

        $this->getTestDB()->test->update($query, $update);
    }

    /**
     * @iterations 1000
     */
    public function complexDocument()
    {
        $query = [
            '_id' => new MongoId(self::EXAMPLE_ID)
        ];

        $update = [
            '$set' => ['foo' => $this->buildComplexDocument()]
        ];

        $this->getTestDB()->test->update($query, $update);
    }

    /**
     * @iterations 1000
     */
    public function complexNestedDocument()
    {
        $query = [
            '_id' => new MongoId(self::EXAMPLE_ID)
        ];

        $update = [
            '$set' => ['foo' => $this->buildComplexNestedDocument()]
        ];

        $this->getTestDB()->test->update($query, $update);
    }
}
