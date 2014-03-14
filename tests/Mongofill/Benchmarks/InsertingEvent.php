<?php

namespace Mongofill\Benchmarks;

class InsertingEvent extends AthleticEvent
{
    /**
     * @iterations 10000
     */
    public function simpleDocument()
    {
        $record = $this->buildSimpleDocument();
        $this->getTestDB()->test->insert($record);
    }

    /**
     * @iterations 3000
     */
    public function simpleNestedDocument()
    {
        $record = $this->buildSimpleNestedDocument();
        $this->getTestDB()->test->insert($record);
    }

    /**
     * @iterations 10000
     */
    public function complexDocument()
    {
        $record = $this->buildSimpleDocument();
        $this->getTestDB()->test->insert($record);
    }

    /**
     * @iterations 3000
     */
    public function complexNestedDocument()
    {
        $record = $this->buildComplexNestedDocument();
        $this->getTestDB()->test->insert($record);
    }
}