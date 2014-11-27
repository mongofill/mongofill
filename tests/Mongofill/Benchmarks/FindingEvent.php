<?php

namespace Mongofill\Benchmarks;

use Exception;

class FindingEvent extends AthleticEvent
{
    protected $recordCount = 200;

    protected function classSetUp()
    {
        parent::classSetUp();

        $simpleRecord = $this->buildSimpleDocument();
        $simpleNestedRecord = $this->buildSimpleNestedDocument();
        $complexRecord = $this->buildComplexDocument();
        $complexNestedRecord = $this->buildComplexNestedDocument();

        for ($i=0; $i < $this->recordCount; $i++) {
            $this->getTestDB()->simple->insert($simpleRecord);
            $this->getTestDB()->simpleNested->insert($simpleNestedRecord);
            $this->getTestDB()->complex->insert($complexRecord);
            $this->getTestDB()->complexNested->insert($complexNestedRecord);

            unset($simpleRecord['_id']);
            unset($simpleNestedRecord['_id']);
            unset($complexRecord['_id']);
            unset($complexNestedRecord['_id']);
        }
    }

    /**
     * @iterations 100
     */
    public function simpleDocument()
    {
        $result = iterator_to_array($this->getTestDB()->simple->find([]));
        $this->throwExceptionIfCountNotMatch($result);
    }

    /**
     * @iterations 100
     */
    public function simpleNestedDocument()
    {
        $result = iterator_to_array($this->getTestDB()->simpleNested->find([]));
        $this->throwExceptionIfCountNotMatch($result);
    }

    /**
     * @iterations 100
     */
    public function complexDocument()
    {
        $result = iterator_to_array($this->getTestDB()->complex->find([]));
        $this->throwExceptionIfCountNotMatch($result);
    }

    /**
     * @iterations 100
     */
    public function complexNestedDocument()
    {
        $result = iterator_to_array($this->getTestDB()->complexNested->find([]));
        $this->throwExceptionIfCountNotMatch($result);
    }

    private function throwExceptionIfCountNotMatch(array $result)
    {
        if (count($result) != $this->recordCount) {
            throw new Exception(sprintf(
                'missmatch result find %d, expected %d records',
                count($result),
                $this->recordCount
            ));
        }
    }
}
