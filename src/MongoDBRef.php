<?php

/**
 * This class can be used to create lightweight links between objects in
 * different collections.
 */
class MongoDBRef
{
    private function __construct($collection, $id, $db = null)
    {
        $this->{'$ref'} = $collection;
        $this->{'$id'} = $id;
        if ($db) {
            $this->{'$db'} = $db;
        }
    }

    /**
     * Creates a new database reference
     *
     * @param string $collection - Collection name (without the database
     *   name).
     * @param mixed $id - The _id field of the object to which to link.
     * @param string $database - Database name.
     *
     * @return array - Returns the reference.
     */
    public static function create($collection, $id, $database = null)
    {
        return new MongoDBRef($collection, $id, $database);
    }

    /**
     * Fetches the object pointed to by a reference
     *
     * @param mongodb $db - Database to use.
     * @param array $ref - Reference to fetch.
     *
     * @return array - Returns the document to which the reference refers
     *   or NULL if the document does not exist (the reference is broken).
     */
    public static function get(MongoDB $db, $ref)
    {
        $ref = (array)$ref;
        if (!isset($ref['$id']) || !isset($ref['$ref'])) {
            return;
        }
        $ns = $ref['$ref'];
        $id = $ref['$id'];

        $refdb = null;
        if (isset($ref['$db'])) {
            $refdb = $ref['$db'];
        }

        if (!is_string($ns)) {
            throw new MongoException('MongoDBRef::get: $ref field must be a string', 10);
        }
        if (isset($refdb)) {
            if (!is_string($refdb)) {
                throw new MongoException('MongoDBRef::get: $db field of $ref must be a string', 11);
            }
            if ($refdb != (string)$db) {
                $db = $db->_getClient()->$refdb;
            }
        }
        $collection = new MongoCollection($db, $ns);
        $query = ['_id' => $id];
        return $collection->findOne($query);
    }

    /**
     * Checks if an array is a database reference
     *
     * @param mixed $ref - Array or object to check.
     *
     * @return bool -
     */
    public static function isRef($ref)
    {
        if (is_array($ref)) {
            if (isset($ref['$id']) && isset($ref['$ref'])) {
                return true;
            }
        } elseif (is_object($ref)) {
            if (isset($ref->{'$ref'}) && isset($ref->{'$id'})) {
                return true;
            }
        }
        return false;
    }
}
