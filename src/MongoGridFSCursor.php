<?php

/**
 * Cursor for database file results.
 */
class MongoGridFSCursor
{
    /**
     * @var MongoClient
     */
    private $cursor;
    
    /**
     * @var MongoGridFS
     */
    protected $gridfs;

    /**
     * Create a new cursor
     *
     * @param mongogridfs $gridfs - Related GridFS collection.
     * @param resource $connection - Database connection.
     * @param string $ns - Full name of database and collection.
     * @param array $query - Database query.
     * @param array $fields - Fields to return.
     *
     * @return  - Returns the new cursor.
     */
    public function __construct(
        MongoGridFS $gridfs, 
        MongoClient $connection, 
        $ns, 
        array $query, 
        array $fields
    )
    {
        $this->gridfs = $gridfs;
        $this->cursor = new MongoCursor($connection, $ns, $query, $fields);
    }

    /**
     * Returns the current file
     *
     * @return MongoGridFSFile - The current file.
     */
    public function current()
    {
        $current = $this->cursor->current();
        if (!$current) {
            return null;
        }

        return new MongoGridFSFile($this->gridfs, $current);
    }

    /**
     * Return the next file to which this cursor points, and advance the cursor
     *
     * @return MongoGridFSFile - Returns the next file.
     */
    public function getNext()
    {
        $this->cursor->getNext();

        return $this->current();
    }

    /**
     * Returns the current results filename
     *
     * @return string - The current results _id as a string.
     */
    public function key()
    {
        return $this->cursor->key();
    }

    public function limit($limit)
    {
        $this->cursor->limit($limit);

        return $this;
    }

    public function sort($fields)
    {
        $this->cursor->sort($fields);

        return $this;
    }

    public function skip($skip)
    {
        $this->cursor->skip($skip);

        return $this;
    }

    public function count($foundOnly = false)
    {
        return $this->cursor->count($foundOnly);
    }
}