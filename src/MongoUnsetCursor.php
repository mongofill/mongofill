<?php

/**
 * A cursor is used to iterate through the results of a database query.
 */
class MongoUnsetCursor extends MongoCursor
{
    public function __construct(MongoClient $client, $ns, array $query = [], array $fields = [])
    {
        parent::__construct($client, $ns, $query, $fields);
    }

    /**
     * Advances the cursor to the next result
     *
     * @return void - NULL.
     */
    public function next()
    {
        $this->doQuery();
        $this->fetchMoreDocumentsIfNeeded();

        // unset($this->documents[$this->currKey]);
        $this->documents[$this->currKey] = null;

        $this->currKey++;
    }
}
