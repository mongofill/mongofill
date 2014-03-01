<?php

use Mongofill\Protocol;

class MongoCursor implements Iterator
{
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
    private $firstResult = true;

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

    private function fetchDocuments()
    {
        if (null === $this->cursorId) {
            $this->fetching = true;
            $query = $this->query;
            if ($this->querySort !== null)
               $query = [ '$query'=>$query, '$orderby'=>$this->querySort ];

            $response = $this->protocol->opQuery($this->fcn, $query, $this->querySkip, $this->queryLimit, 0, $this->fields);
            $this->cursorId = $response['cursorId'];
        } else {
            $response = $this->protocol->opGetMore($this->fcn, 0, $this->cursorId);
            $this->firstResult = false;
        }
        if (0 === $response['count']) $this->end = true;
        $this->documents = $response['result'];
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
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     */
    public function current()
    {
        if (!$this->fetching)
            $this->fetchDocuments();
        return current($this->documents);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    public function next()
    {
        if (!$this->fetching)
            $this->fetchDocuments();
        if (!$this->end && false === next($this->documents)) {
            if (null !== $this->cursorId) {
                $this->fetchDocuments();
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
        return count($this->documents) ? $this->currKey : null;
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
        if (!$this->fetching)
            $this->fetchDocuments();
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
        if (!$this->firstResult) {
            $this->documents   = [];
            $this->cursorId    = null;
            $this->firstResult = true;
        }
        reset($this->documents);
        $this->end = false;
    }
}
