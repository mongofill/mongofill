<?php

use Mongofill\Protocol;

class MongoCursor implements Iterator
{
    const INTERNAL_QUERY_LIMIT = 100;

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
     * @param MongoClient $connection
     * @param $ns
     * @param array $query
     * @param array $fields
     */
    function __construct(MongoClient $connection, $ns, array $query = [], array $fields = [])
    {
        $this->client   = $connection;
        $this->protocol = $connection->_getProtocol();
        $this->fcn      = $ns;
        $this->query    = $query;
        $this->fields   = $fields;
    }

    private function fetchDocumentsIfNeeded()
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
            $this->queryLimit, 
            0, //no flags
            $this->fields
        );

        $this->cursorId = $response['cursorId'];
        $this->setDocuments($response);
    }

    private function fetchMoreDocuments()
    {
        if (!$this->hasMore) {
            $this->end = true;
            return; 
        }

        $limit = self::INTERNAL_QUERY_LIMIT;
        $limited = true;
        if ($this->queryLimit && $this->queryLimit < self::INTERNAL_QUERY_LIMIT) {
            $limit = $this->queryLimit - count($this->documents);
            $limited = false;
        }

        $response = $this->protocol->opGetMore($this->fcn, $limit+1, $this->cursorId);
    
        $this->setDocuments($response);
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

    private function setDocuments(array $response)
    {
        if (0 === $response['count']) {
            $this->end = true;
        }

        if ($response['count'] > self::INTERNAL_QUERY_LIMIT) {
            $this->hasMore = true;
        }

        $this->documents = array_merge($this->documents, $response['result']);
    }

    /**
     * @param int $limit
     * @return MongoCursor
     */
    public function limit($limit)
    {
       $this->queryLimit = $limit;
       return $this;
    }

    /**
     * @param array|string $fields
     * @return MongoCursor
     */
    public function sort($fields)
    {
        if (is_string($fields))
            $fields = [ $fields=>1 ];
        $this->querySort = $fields;
        return $this;
    }

    /**
     * @param int $skip
     * @return MongoCursor
     */
    public function skip($skip)
    {
        $this->querySkip = $skip;
        return $this;
    }

    /**
     * @param boolean $foundOnly
     * @return int
     */
    public function count($foundOnly = false)
    {
        $this->fetchDocumentsIfNeeded();

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
        while($this->hasMore && !$this->end) {
            $this->fetchMoreDocuments();
        }

        return count($this->documents);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     */
    public function current()
    {
        $this->fetchDocumentsIfNeeded();
        if (!isset($this->documents[$this->currKey])) {
            return null;
        }
        
        return $this->documents[$this->currKey];
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    public function next()
    {
        $this->fetchDocumentsIfNeeded();
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
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
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
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    public function valid()
    {
        $this->fetchDocumentsIfNeeded();

        return !$this->end;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
        $this->currKey = 0;
        $this->end = false;
    }
}
