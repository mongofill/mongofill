<?php

namespace Mongofill\Benchmarks;

use MongoId;

class DeletingEvent extends AthleticEvent
{
    const EXAMPLE_ID = '53233675acf164c2f80041a7';

    public function setUp()
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
        $this->getTestDB()->test->remove([
            '_id' => new MongoId(self::EXAMPLE_ID)
        ]);
    }
}
