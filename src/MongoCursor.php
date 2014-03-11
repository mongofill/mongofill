<?php

use Mongofill\Protocol;

/**
 * A cursor is used to iterate through the results of a database query.
 */
class MongoCursor implements Iterator
{
    const DEFAULT_BATCH_SIZE = 100;

    /**
     * @var MongoClient
     */
    private $client;

    /**
     * @var Protocol
     */
    private $protocol;

    /**
     * Full collection name
     * @var string
     */
    private $fcn;

    /**
     * @var array[]
     */
    private $documents = [];

    /**
     * @var int
     */
    private $currKey = 0;

    /**
     * @var null|int
     */
    private $cursorId = null;

    /**
     * @var bool
     */
    private $fetching = false;

    /**
     * @var bool
     */
    private $end = false;

    /**
     * @var bool
     */
    private $hasMore = false;

    /**
     * @var array
     */
    private $query = [ ];

    /**
     * @var array
     */
    private $fields = [ ];

    /**
     * @var int
     */
    private $queryLimit = 0;

    /**
     * @var int
     */
    private $querySkip = 0;

    /**
     * @var array|null
     */
    private $querySort = null;

    /**
     * @var int
     */
    private $batchSize = self::DEFAULT_BATCH_SIZE;

    /**
     * Create a new cursor
     *
     * @param mongoclient $connection - Database connection.
     * @param string      $ns         - Full name of database and collection.
     * @param array       $query      - Database query.
     * @param array       $fields     - Fields to return.
     *
     * @return - Returns the new cursor.
     */
    public function __construct(MongoClient $connection, $ns, array $query = [], array $fields = [])
    {
        $this->client   = $connection;
        $this->protocol = $connection->_getProtocol();
        $this->fcn      = $ns;
        $this->query    = $query;
        $this->fields   = $fields;
    }

    /**
     * Sets the fields for a query
     *
     * @param array $fields - Fields to return (or not return).
     *
     * @return MongoCursor - Returns this cursor.
     */
    public function fields(array $fields)
    {
        throw new Exception('Not Implemented');
    }

    /**
     * Limits the number of results returned
     *
     * @param int $num - The number of results to return.
     *
     * @return MongoCursor - Returns this cursor.
     */
    public function limit($num)
    {
       $this->queryLimit = $num;

       return $this;
    }

    /**
     * Sorts the results by given fields
     *
     * @param array $fields - An array of fields by which to sort. Each
     *   element in the array has as key the field name, and as value either
     *   1 for ascending sort, or -1 for descending sort.
     *
     * @return MongoCursor - Returns the same cursor that this method was
     *   called on.
     */
    public function sort(array $fields)
    {
        $this->querySort = $fields;

        return $this;
    }

    /**
     * Skips a number of results
     *
     * @param int $num - The number of results to skip.
     *
     * @return MongoCursor - Returns this cursor.
     */
    public function skip($num)
    {
        $this->querySkip = $num;

        return $this;
    }

    /**
     * Limits the number of elements returned in one batch.
     *
     * @param int $batchSize - The number of results to return per batch.
     *   Each batch requires a round-trip to the server.
     *
     * @return MongoCursor - Returns this cursor.
     */
    public function batchSize($batchSize)
    {
        $this->batchSize = $batchSize;

        return $this;
    }

    /**
     * Gets the query, fields, limit, and skip for this cursor
     *
     * @return array - Returns the namespace, limit, skip, query, and
     *   fields for this cursor.
     */
    public function info()
    {
        throw new Exception('Not Implemented');
    }

    /**
     * Counts the number of results for this query
     *
     * @param bool $foundOnly -
     *
     * @return int - The number of documents returned by this cursor's
     *   query.
     */
    public function count($foundOnly = false)
    {
        $this->doQuery();

        if ($foundOnly) {
            return $this->countLocalData();
        }

        return $this->countQuerying();
    }

    private function countQuerying()
    {
        $ns = explode('.', $this->fcn, 2);

        $query = [
            'count' => $ns[1],
            'query' => $this->getQuery()
        ];

        $response = $this->protocol->opQuery($ns[0] . '.$cmd', $query, 0, -1, 0);

        return (int) $response['result'][0]['n'];
    }

    private function countLocalData()
    {
        while (!$this->end) {
            $this->fetchMoreDocuments();
        }

        return count($this->documents);
    }

    /**
     * Execute the query.
     *
     * @return void - NULL.
     */
    protected function doQuery()
    {
        if (!$this->fetching) {
            $this->fetchDocuments();
        }
    }

    private function fetchDocuments()
    {
        $this->fetching = true;
        $response = $this->protocol->opQuery(
            $this->fcn,
            $this->getQuery(),
            $this->querySkip,
            $this->calculateRequestLimit(),
            0, //no flags
            $this->fields
        );

        $this->cursorId = $response['cursorId'];
        $this->setDocuments($response);
    }

    private function calculateRequestLimit()
    {
        if ($this->queryLimit < 0) {
            return $this->queryLimit;
        } else if ($this->batchSize < 0) {
            return $this->batchSize;
        }

        if ($this->queryLimit > $this->batchSize) {
            return $this->batchSize;
        } else {
            return $this->queryLimit;
        }

        if ($this->batchSize && (!$limitAt || $this->batchSize <= $limitAt)) {
            return $this->batchSize;
        } else if ($limitAt && (!$limitAt || $this->batchSize > $limitAt)) {
            return $limitAt;
        }

        return 0;
    }

    private function fetchMoreDocuments()
    {
        $limit = $this->calculateNextRequestLimit();    
        if ($this->end) {
            return;
        }

        $response = $this->protocol->opGetMore(
            $this->fcn, 
            $limit, 
            $this->cursorId
        );

        $this->setDocuments($response);
    }

    private function calculateNextRequestLimit()
    {
        $current = count($this->documents);
        if ($this->queryLimit && $current >= $this->queryLimit) {
            $this->end = true;
            return 0;
        }

        if ($this->queryLimit >= $current) {
            $remaining = $this->queryLimit - $current;
        } else {
            $remaining = $this->queryLimit;
        }

        if ($remaining > $this->batchSize) {
            return $this->batchSize;
        }

        return $remaining;
    }

    private function setDocuments(array $response)
    {
        if (0 === $response['count']) {
            $this->end = true;
        }

        $this->documents = array_merge($this->documents, $response['result']);
    }

    private function getQuery()
    {
        $query = $this->query;
        if ($this->querySort !== null) {
            $query = [
                '$query' => $query,
                '$orderby' => $this->querySort
            ];
        }

        return $query;
    }

    /**
     * Return an explanation of the query, often useful for optimization and
     * debugging
     *
     * @return array - Returns an explanation of the query.
     */
    public function explain()
    {
        throw new Exception('Not Implemented');
    }

    /**
     * Adds a top-level key/value pair to a query
     *
     * @param string $key   - Fieldname to add.
     * @param mixed  $value - Value to add.
     *
     * @return MongoCursor - Returns this cursor.
     */
    public function addOption($key, $value)
    {
        throw new Exception('Not Implemented');
    }

    /**
     * Sets whether this cursor will wait for a while for a tailable cursor to
     * return more data
     *
     * @param bool $wait - If the cursor should wait for more data to
     *   become available.
     *
     * @return MongoCursor - Returns this cursor.
     */
    public function awaitData($wait = true)
    {
        throw new Exception('Not Implemented');
    }

    /**
     * Checks if there are documents that have not been sent yet from the
     * database for this cursor
     *
     * @return bool - Returns if there are more results that have not been
     *   sent to the client, yet.
     */
    public function dead()
    {
        throw new Exception('Not Implemented');
    }

    /**
     * Return the next object to which this cursor points, and advance the
     * cursor
     *
     * @return array - Returns the next object.
     */
    public function getNext()
    {
        throw new Exception('Not Implemented');
    }

    /**
     * Get the read preference for this query
     *
     * @return array -
     */
    public function getReadPreference()
    {
        throw new Exception('Not Implemented');
    }

    /**
     * Checks if there are any more elements in this cursor
     *
     * @return bool - Returns if there is another element.
     */
    public function hasNext()
    {
        throw new Exception('Not Implemented');
    }

    /**
     * Gives the database a hint about the query
     *
     * @param mixed $index - Index to use for the query. If a string is
     *   passed, it should correspond to an index name. If an array or object
     *   is passed, it should correspond to the specification used to create
     *   the index (i.e. the first argument to
     *   MongoCollection::ensureIndex()).
     *
     * @return MongoCursor - Returns this cursor.
     */
    public function hint($index)
    {
        throw new Exception('Not Implemented');
    }

    /**
     * Sets whether this cursor will timeout
     *
     * @param bool $liveForever - If the cursor should be immortal.
     *
     * @return MongoCursor - Returns this cursor.
     */
    public function immortal($liveForever = true)
    {
        throw new Exception('Not Implemented');
    }

    /**
     * If this query should fetch partial results from  if a shard is down
     *
     * @param bool $okay - If receiving partial results is okay.
     *
     * @return MongoCursor - Returns this cursor.
     */
    public function partial($okay = true)
    {
        throw new Exception('Not Implemented');
    }

    /**
     * Clears the cursor
     *
     * @return void - NULL.
     */
    public function reset()
    {
        throw new Exception('Not Implemented');
    }

    /**
     * Sets arbitrary flags in case there is no method available the specific
     * flag
     *
     * @param int $flag - Which flag to set. You can not set flag 6
     *   (EXHAUST) as the driver does not know how to handle them. You will
     *   get a warning if you try to use this flag. For available flags,
     *   please refer to the wire protocol documentation.
     * @param bool $set - Whether the flag should be set (TRUE) or unset
     *   (FALSE).
     *
     * @return MongoCursor - Returns this cursor.
     */
    public function setFlag($flag, $set = true)
    {
        throw new Exception('Not Implemented');
    }

    /**
     * Set the read preference for this query
     *
     * @param string $readPreference -
     * @param array  $tags           -
     *
     * @return MongoCursor - Returns this cursor.
     */
    public function setReadPreference($readPreference, array $tags)
    {
        throw new Exception('Not Implemented');
    }

    /**
     * Sets whether this query can be done on a secondary
     *
     * @param bool $okay - If it is okay to query the secondary.
     *
     * @return MongoCursor - Returns this cursor.
     */
    public function slaveOkay($okay = true)
    {
        throw new Exception('Not Implemented');
    }

    /**
     * Use snapshot mode for the query
     *
     * @return MongoCursor - Returns this cursor.
     */
    public function snapshot()
    {
        throw new Exception('Not Implemented');
    }

    /**
     * Sets whether this cursor will be left open after fetching the last
     * results
     *
     * @param bool $tail - If the cursor should be tailable.
     *
     * @return MongoCursor - Returns this cursor.
     */
    public function tailable($tail = true)
    {
        throw new Exception('Not Implemented');
    }

    /**
     * Sets a client-side timeout for this query
     *
     * @param int $ms -
     *
     * @return MongoCursor - This cursor.
     */
    public function timeout($ms)
    {
        throw new Exception('Not Implemented');
    }

    /**
     * Returns the current element
     *
     * @return array - The current result as an associative array.
     */
    public function current()
    {
        $this->doQuery();
        if (!isset($this->documents[$this->currKey])) {
            return null;
        }

        return $this->documents[$this->currKey];
    }

    /**
     * Advances the cursor to the next result
     *
     * @return void - NULL.
     */
    public function next()
    {
        $this->doQuery();
        if (!isset($this->documents[$this->currKey+1])) {
            if ($this->cursorId) {
                $this->fetchMoreDocuments();
            } else {
                $this->end = true;
            }
        }

        $this->currKey++;
    }

    /**
     * Returns the current results _id
     *
     * @return string - The current results _id as a string.
     */
    public function key()
    {
        $record = $this->current();

        if (!isset($record['_id'])) {
            return $this->currKey;
        }

        return (string) $record['_id'];
    }

    /**
     * Checks if the cursor is reading a valid result.
     *
     * @return bool - If the current result is not null.
     */
    public function valid()
    {
        $this->doQuery();

        return !$this->end;
    }

    /**
     * Returns the cursor to the beginning of the result set
     *
     * @return void - NULL.
     */
    public function rewind()
    {
        $this->currKey = 0;
        $this->end = false;
    }
}
