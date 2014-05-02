<?php

/**
 * Cursor for database file results.
 */
class MongoGridFSCursor extends MongoCursor
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

        parent::__construct($connection, $ns, $query, $fields);
    }

    /**
     * Returns the current file
     *
     * @return MongoGridFSFile - The current file.
     */
    public function current()
    {
        $current = parent::current();
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
        parent::getNext();

        return $this->current();
    }

    /**
     * Returns the current results filename
     *
     * @return string - The current results _id as a string.
     */
    public function key()
    {
        return parent::key();
    }

    public function limit($limit)
    {
        parent::limit($limit);

        return $this;
    }

    public function sort(array $fields)
    {
        parent::sort($fields);

        return $this;
    }

    public function skip($skip)
    {
        parent::skip($skip);

        return $this;
    }

    public function count($foundOnly = false)
    {
        return parent::count($foundOnly);
    }
}
