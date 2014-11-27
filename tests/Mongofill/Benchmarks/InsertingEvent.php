<?php

namespace Mongofill\Benchmarks;

class InsertingEvent extends AthleticEvent
{
    /**
     * @iterations 1000
     */
    public function simpleDocument()
    {
        $record = $this->buildSimpleDocument();
        $r = $this->getTestDB()->test->insert($record, ['w' => 1]);

        if ($r['ok'] != 1) {
            throw new \Exception('Non ok result at simpleDocumentW0'. json_encode($r));
        }
    }

    /**
     * @iterations 1000
     */
    public function simpleDocumentW0()
    {
        $record = $this->buildSimpleDocument();
        $r = $this->getTestDB()->test->insert($record, ['w' => 0]);

        if ($r !== true) {
            throw new \Exception('Non true result at simpleDocumentW0');
        }
    }

    /**
     * @iterations 1000
     */
    public function simpleNestedDocument()
    {
        $record = $this->buildSimpleNestedDocument();
        $this->getTestDB()->test->insert($record);
    }

    /**
     * @iterations 1000
     */
    public function complexDocument()
    {
        $record = $this->buildComplexDocument();
        $this->getTestDB()->test->insert($record);
    }

    /**
     * @iterations 1000
     */
    public function complexNestedDocument()
    {
        $record = $this->buildComplexNestedDocument();
        $this->getTestDB()->test->insert($record);
    }
}
