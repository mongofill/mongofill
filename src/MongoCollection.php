<?php

/**
 * Represents a MongoDB collection.
 */
class MongoCollection
{
    const ASCENDING = 1;
    const DESCENDING = -1;

    /**
     * @var MongoDB
     */
    public $db;

    /**
     * @var int
     */
    public $w;

    /**
     * @var int
     */
    public $wtimeout;

    /**
     * @var string
     */
    private $fqn;

    /**
     * @var string
     */
    private $name;

    /**
     * @var MongoClient
     */
    private $client;

    /**
     * @var array
     */
    private $readPreference;

    /**
     * Creates a new collection
     *
     * @param MongoDB $db   - Parent database.
     * @param string  $name -
     *
     * @return - Returns a new collection object.
     */
    public function __construct(MongoDB $db, $name)
    {
        $this->db = $db;
        $this->name = $name;
        $this->readPreference = $db->getReadPreference();
        $this->fqn = $db->_getFullCollectionName($name);
        $this->client = $db->_getClient();
    }

    /**
     * Gets a collection
     *
     * @param string $name - The next string in the collection name.
     *
     * @return MongoCollection - Returns the collection.
     */
    public function __get($name)
    {
        return $this->db->selectCollection($this->name . '.' . $name);
    }

    /**
     * Counts the number of documents in this collection
     *
     * @param array $query - Associative array or object with fields to
     *   match.
     * @param int $limit - Specifies an upper limit to the number returned.
     * @param int $skip  - Specifies a number of results to skip before
     *   starting the count.
     *
     * @return int - Returns the number of documents matching the query.
     */
    public function count(array $query = [], $limit = 0, $skip = 0)
    {
        $cmd = [
            'count' => $this->name,
            'query' => $query
        ];

        if ($limit) {
            $cmd['limit'] = $limit;
        }

        if ($skip) {
            $cmd['skip'] = $skip;
        }

        $result = $this->db->command($cmd);

        if (isset($result['ok'])) {
            return (int) $result['n'];
        }

        return false;
    }

    /**
     * Creates a database reference
     *
     * @param mixed $documentOrId - If an array or object is given, its
     *   _id field will be used as the reference ID. If a MongoId or scalar
     *   is given, it will be used as the reference ID.
     *
     * @return array - Returns a database reference array.   If an array
     *   without an _id field was provided as the document_or_id parameter,
     *   NULL will be returned.
     */
    public function createDBRef($documentOrId)
    {
        throw new Exception('Not Implemented');
    }

    /**
     * Fetches the document pointed to by a database reference
     *
     * @param array $ref - A database reference.
     *
     * @return array - Returns the database document pointed to by the reference.
     */
    public function getDBRef(array $ref)
    {
        throw new Exception('Not Implemented');
    }

    /**
     * Queries this collection, returning a for the result set
     *
     * @param array $query - The fields for which to search. MongoDB's
     *   query language is quite extensive.
     * @param array $fields - Fields of the results to return.
     *
     * @return MongoCursor - Returns a cursor for the search results.
     */
    public function find(array $query = [], array $fields = [])
    {
        return new MongoCursor($this->client, $this->fqn, $query, $fields);
    }

    /**
     * Queries this collection, returning a single element
     *
     * @param array $query - The fields for which to search. MongoDB's
     *   query language is quite extensive.
     * @param array $fields - Fields of the results to return.
     *
     * @return array - Returns record matching the search or NULL.
     */
    public function findOne($query = [], array $fields = [])
    {
        $cursor = $this->find($query, $fields)->limit(1);

        return $cursor->getNext();
    }

    /**
     * Update a document and return it
     *
     * @param array $query   -
     * @param array $update  -
     * @param array $fields  -
     * @param array $options -
     *
     * @return array - Returns the original document, or the modified
     *   document when new is set.
     */
    public function findAndModify(
        array $query, array $update = null, array $fields = null,
        array $options = null
    )
    {
        $command = ['findandmodify' => $this->name];

        if ($query) {
            $command['query'] = $query;
        }

        if ($update) {
            $command['update'] = $update;
        }

        if ($fields) {
            $command['fields'] = $fields;
        }

        if ($options) {
            $command = array_merge($command, $options);
        }

        $result = $this->db->command($command);

        if (isset($result['value'])) {
            return $result['value'];
        }
        return [];
    }

    /**
     * Drops this collection
     *
     * @return array - Returns the database response.
     */
    public function drop()
    {
        $this->db->command(['drop' => $this->name]);
    }

    /**
     * Returns this collections name
     *
     * @return string - Returns the name of this collection.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Inserts a document into the collection
     *
     * @param array|object $a - An array or object. If an object is used,
     *   it may not have protected or private properties.    If the parameter
     *   does not have an _id key or property, a new MongoId instance will be
     *   created and assigned to it. This special behavior does not mean that
     *   the parameter is passed by reference.
     * @param array $options - Options for the insert.
     *
     * @return bool|array - Returns an array containing the status of the
     *   insertion if the "w" option is set.
     */
    public function insert(&$document, array $options = [])
    {
        $timeout = MongoCursor::$timeout;
        if (!empty($options['timeout'])) {
            $timeout = $options['timeout'];
        }

        $this->fillIdInDocumentIfNeeded($document);
        $documents = [&$document];

        return $this->client->_getWriteProtocol()->opInsert(
            $this->fqn,
            $documents,
            $options,
            $timeout
        );
    }

    /**
     * Inserts multiple documents into this collection
     *
     * @param array $a       - An array of arrays or objects.
     * @param array $options - Options for the inserts.
     *
     * @return mixed - If the w parameter is set to acknowledge the write,
     *   returns an associative array with the status of the inserts ("ok")
     *   and any error that may have occurred ("err"). Otherwise, returns
     *   TRUE if the batch insert was successfully sent, FALSE otherwise.
     */
    public function batchInsert(array &$documents, array $options = [])
    {
        $timeout = MongoCursor::$timeout;
        if (!empty($options['timeout'])) {
            $timeout = $options['timeout'];
        }

        $count = count($documents);
        $keys = array_keys($documents);
        for ($i=0; $i < $count; $i++) {
            $this->fillIdInDocumentIfNeeded($documents[$keys[$i]]);
        }

        $this->client->_getWriteProtocol()->opInsert($this->fqn, $documents, $options, $timeout);

        // Fake response for async insert -
        // TODO: detect "w" option and return status array
        return true;
    }

    private function fillIdInDocumentIfNeeded(&$document)
    {
        if (is_object($document)) {
            $document = get_object_vars($document);
        }

        if (!isset($document['_id'])) {
            $document['_id'] = new MongoId();
        }
    }

    /**
     * Update records based on a given criteria
     *
     * @param array $criteria   - Description of the objects to update.
     * @param array $new_object - The object with which to update the
     *   matching records.
     * @param array $options - This parameter is an associative array of
     *   the form array("optionname" => boolean, ...)
     *
     * @return bool|array - Returns an array containing the status of the
     *   update if the "w" option is set. Otherwise, returns TRUE.   Fields
     *   in the status array are described in the documentation for
     *   MongoCollection::insert().
     */
    public function update(array $criteria, array $newObject, array $options = [])
    {
        $timeout = MongoCursor::$timeout;
        if (!empty($options['timeout'])) {
            $timeout = $options['timeout'];
        }

        return $this->client->_getWriteProtocol()->opUpdate(
            $this->fqn,
            $criteria,
            $newObject,
            $options,
            $timeout
        );
    }

    /**
     * Saves a document to this collection
     *
     * @param array|object $a - Array or object to save. If an object is
     *   used, it may not have protected or private properties.
     * @param array $options - Options for the save.
     *
     * @return mixed - If w was set, returns an array containing the status
     *   of the save. Otherwise, returns a boolean representing if the array
     *   was not empty (an empty array will not be inserted).
     */
    public function save($document, array $options = [])
    {
        if (!$document) {
            return false;
        }

        if (is_object($document)) {
            $document = get_object_vars($document);
        }

        if (isset($document['_id'])) {
            $options['upsert'] = true;

            return $this->update(['_id' => $document['_id']], $document, $options);
        } else {
            return $this->insert($document, $options);
        }
    }

    /**
     * Remove records from this collection
     *
     * @param array $criteria - Description of records to remove.
     * @param array $options  - Options for remove.    "justOne"   Remove at
     *   most one record matching this criteria.
     *
     * @return bool|array - Returns an array containing the status of the
     *   removal if the "w" option is set. Otherwise, returns TRUE.   Fields
     *   in the status array are described in the documentation for
     *   MongoCollection::insert().
     */
    public function remove(array $criteria = [], array $options = [])
    {
        $timeout = MongoCursor::$timeout;
        if (!empty($options['timeout'])) {
            $timeout = $options['timeout'];
        }

        return $this->client->_getWriteProtocol()->opDelete(
            $this->fqn,
            $criteria,
            $options,
            $timeout
        );
    }

    /**
     * Validates this collection
     *
     * @param bool $scanData - Only validate indices, not the base collection.
     *
     * @return array - Returns the databases evaluation of this object.
     */
    public function validate($scanData = false)
    {
        $result = $this->db->command([
            'validate' => $this->name,
            'full' => $scanData
        ]);

        if ($result) {
            return $result;
        }

        return false;
    }

    /**
     * Converts keys specifying an index to its identifying string
     *
     * @param mixed $keys - Field or fields to convert to the identifying string
     *
     * @return string - Returns a string that describes the index.
     */
    protected static function toIndexString($keys)
    {
        if (is_string($keys)) {
            return self::toIndexStringFromString($keys);
        } elseif (is_object($keys)) {
            $keys = get_object_vars($keys);
        }

        if (is_array($keys)) {
            return self::toIndexStringFromArray($keys);
        }

        trigger_error('MongoCollection::toIndexString(): The key needs to be either a string or an array', E_USER_WARNING);

        return null;
    }

    public static function _toIndexString($keys)
    {
        return self::toIndexString($keys);
    }

    private static function toIndexStringFromString($keys)
    {
        return str_replace('.', '_', $keys . '_1');
    }

    private static function toIndexStringFromArray(array $keys)
    {
        $prefValue = null;
        if (isset($keys['weights'])) {
            $keys = $keys['weights'];
            $prefValue = 'text';
        }

        $keys = (array) $keys;
        foreach ($keys as $key => $value) {
            if ($prefValue) {
                $value = $prefValue;
            }

            $keys[$key] = str_replace('.', '_', $key . '_' . $value);
        }

        return implode('_', $keys);
    }

    /**
     * Deletes an index from this collection
     *
     * @param string|array $keys - Field or fields from which to delete the
     *   index.
     *
     * @return array - Returns the database response.
     */
    public function deleteIndex($keys)
    {
        $cmd = [
            'deleteIndexes' => $this->name,
            'index' => self::toIndexString($keys)
        ];

        return $this->db->command($cmd);
    }

    /**
     * Delete all indices for this collection
     *
     * @return array - Returns the database response.
     */
    public function deleteIndexes()
    {
        return (bool) $this->db->getIndexesCollection()->drop();
    }

    /**
     * Creates an index on the given field(s), or does nothing if the index
     *    already exists
     *
     *
     * @param string|array $key|keys -
     * @param array        $options  - This parameter is an associative array of
     *   the form array("optionname" => boolean, ...).
     *
     * @return bool - Returns an array containing the status of the index
     *   creation if the "w" option is set. Otherwise, returns TRUE.   Fields
     *   in the status array are described in the documentation for
     *   MongoCollection::insert().
     */
    public function ensureIndex($keys, array $options = [])
    {
        if (!is_array($keys)) {
            $keys = [$keys => 1];
        }

        $index = [
            'ns' => $this->fqn,
            'name' => self::toIndexString($keys, $options),
            'key' => $keys
        ];

        $insertOptions = [];
        if (isset($options['safe'])) {
            $insertOptions['safe'] = $options['safe'];
        }

        if (isset($options['w'])) {
            $insertOptions['w'] = $options['w'];
        }

        if (isset($options['fsync'])) {
            $insertOptions['fsync'] = $options['fsync'];
        }

        if (isset($options['timeout'])) {
            $insertOptions['timeout'] = $options['timeout'];
        }

        $index = array_merge($index, $options);

        $return = (bool) $this->db->getIndexesCollection()->insert(
            $index,
            $insertOptions
        );

        return $return;
    }

    /**
     * Returns information about indexes on this collection
     *
     * @return array - This function returns an array in which each element
     *   describes an index. Elements will contain the values name for the
     *   name of the index, ns for the namespace (a combination of the
     *   database and collection name), and key for a list of all fields in
     *   the index and their ordering. Additional values may be present for
     *   special indexes, such as unique or sparse.
     */
    public function getIndexInfo()
    {
        $indexes = $this->db->getIndexesCollection()->find([
            'ns' => $this->fqn
        ]);

        return iterator_to_array($indexes);
    }

    /**
     * Set the read preference for this collection
     *
     * @param string $readPreference
     * @param array  $tags
     *
     * @return bool
     */
    public function setReadPreference($readPreference, array $tags = null)
    {
        if ($newPreference = MongoClient::_validateReadPreference($readPreference, $tags)) {
            $this->readPreference = $newPreference;
        }
        return (bool)$newPreference;
    }

    /**
     * Get the read preference for this collection
     *
     * @return array
     */
    public function getReadPreference()
    {
        return $this->readPreference;
    }

    /**
     * Change slaveOkay setting for this collection
     *
     * @param bool $ok - If reads should be sent to secondary members of a
     *   replica set for all possible queries using this MongoCollection
     *   instance.
     *
     * @return bool - Returns the former value of slaveOkay for this
     *   instance.
     */
    public function setSlaveOkay($ok = true)
    {
        throw new Exception('Not Implemented');
    }

    /**
     * Get slaveOkay setting for this collection
     *
     * @return bool - Returns the value of slaveOkay for this instance.
     */
    public function getSlaveOkay()
    {
        throw new Exception('Not Implemented');
    }

    /**
     * Perform an aggregation using the aggregation framework
     *
     * @param array $pipeline -
     * @param array $op       -
     * @param array $...      -
     *
     * @return array - The result of the aggregation as an array. The ok
     *   will be set to 1 on success, 0 on failure.
     */
    public function aggregate(array $pipeline)
    {
        if (func_num_args() > 1) {
            $pipeline = func_get_args();
        }

        $cmd = [
            'aggregate' => $this->name,
            'pipeline' => $pipeline
        ];

        return $this->db->command($cmd);
    }

    /**
     * Retrieve a list of distinct values for the given key across a collection.
     *
     * @param string $key   -
     * @param array  $query -
     *
     * @return array - Returns an array of distinct values,
     */
    public function distinct($key, array $query = [])
    {
        $cmd = [
            'distinct' => $this->name,
            'key' => $key,
        ];

        if (!empty($query)) {
            $cmd['query'] = $query;
        }

        $results = $this->db->command($cmd);
        if (!isset($results['values'])) {
            return [];
        }

        return $results['values'];
    }

    /**
     * Performs an operation similar to SQL's GROUP BY command
     *
     * @param mixed $keys - Fields to group by. If an array or non-code
     *   object is passed, it will be the key used to group results.
     * @param array $initial - Initial value of the aggregation counter
     *   object.
     * @param mongocode $reduce - A function that takes two arguments (the
     *   current document and the aggregation to this point) and does the
     *   aggregation.
     * @param array $options - Optional parameters to the group command
     *
     * @return array - Returns an array containing the result.
     */
    public function group(
        $keys,
        array $initial,
        $reduce,
        array $options = array()
    )
    {
        $cmd = [
            'group' => [
                'ns' => $this->name,
                'key' => $keys,
                '$reduce' => $reduce,
                'initial' => $initial
            ]
        ];

        if (isset($options['finalize'])) {
            $cmd['group']['finalize'] = $options['finalize'];
        }

        if (isset($options['condition'])) {
            $cmd['group']['cond'] = $options['condition'];
        }

        return $this->db->command($cmd);
    }

    /**
     * String representation of this collection
     *
     * @return string - Returns the full name of this collection.
     */
    public function __toString()
    {
        return $this->fqn;
    }
}
