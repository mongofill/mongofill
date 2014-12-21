<?php

class MongoDB
{
    const PROFILING_OFF = 0;
    const PROFILING_SLOW = 1;
    const PROFILING_ON = 2;
    const NAMESPACES_COLLECTION = 'system.namespaces';
    const INDEX_COLLECTION = 'system.indexes';

    /**
     * @var int
     */
    public $w = 1;

    /**
     * @var int
     */
    public $w_timeout = 10000;

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
    private $collections = [];

    /**
     * @var array
     */
    private $readPreference;

    /**
     * Creates a new database
     *
     * @param MongoClient $client - Database connection.
     * @param string $name - Database name.
     */
    public function __construct(MongoClient $client, $name)
    {
        $this->name = $name;
        $this->client = $client;
        $this->readPreference = $client->getReadPreference();
    }

    /**
     * Gets a collection
     *
     * @param string $name - The name of the collection.
     *
     * @return MongoCollection - Returns the collection.
     */
    public function __get($name)
    {
        return $this->selectCollection($name);
    }

    /**
     * @return MongoClient
     */
    public function _getClient()
    {
        return $this->client;
    }

    /**
     * Gets a collection
     *
     * @param string $name - The collection name.
     *
     * @return MongoCollection - Returns a new collection object.
     */
    public function selectCollection($name)
    {
        if (!isset($this->collections[$name])) {
            $this->collections[$name] = new MongoCollection($this, $name);
        }

        return $this->collections[$name];
    }


    public function _getFullCollectionName($collectionName)
    {
        return $this->name . '.' . $collectionName;
    }

    /**
     * Drops this database
     *
     * @return array - Returns the database response.
     */
    public function drop()
    {
        $cmd = ['dropDatabase' => 1];

        return $this->command($cmd);
    }

    /**
     * Execute a database command
     *
     * @param array $command - The query to send.
     * @param array $options - This parameter is an associative array of
     *   the form array("optionname" => boolean, ...).
     *
     * @return array - Returns database response.
     */
    public function command(array $cmd, array $options = [])
    {
        $timeout = MongoCursor::$timeout;
        if (!empty($options['timeout'])) {
            $timeout = $options['timeout'];
        }

        $protocol = empty($options['protocol'])
            ? $this->client->_getWriteProtocol()
            : $options['protocol'];

        $response = $protocol->opQuery(
            "{$this->name}.\$cmd",
            $cmd,
            0, -1, 0,
            $timeout
        );

        return $response['result'][0];
    }

    /**
     * Get all collections from this database
     *
     * @param bool $includeSystemCollections -
     *
     * @return array - Returns the names of the all the collections in the
     *   database as an array.
     */
    public function getCollectionNames($includeSystemCollections = false)
    {
        $collections = [];
        $namespaces = $this->selectCollection(self::NAMESPACES_COLLECTION);
        foreach ($namespaces->find() as $collection) {
            if (
                !$includeSystemCollections &&
                $this->isSystemCollection($collection['name'])
            ) {
                continue;
            }

            if ($this->isAnIndexCollection($collection['name'])) {
                continue;
            }

            $collections[] = $this->getCollectionName($collection['name']);
        }

        return $collections;
    }

    /**
     * Gets an array of all MongoCollections for this database
     *
     * @param bool $includeSystemCollections -
     *
     * @return array - Returns an array of MongoCollection objects.
     */
    public function listCollections($includeSystemCollections = false)
    {
        $collections = [];
        $names = $this->getCollectionNames($includeSystemCollections);
        foreach ($names as $name) {
            $collections[] = $this->selectCollection($name);
        }

        return $collections;
    }

    private function isAnIndexCollection($namespace)
    {
        return !strpos($namespace, '$') === false;
    }


    private function isSystemCollection($namespace)
    {
        return !strpos($namespace, '.system.') === false;
    }

    private function getCollectionName($namespace)
    {
        $dot = strpos($namespace, '.');

        return substr($namespace, $dot + 1);
    }

    public function getIndexesCollection()
    {
        return $this->selectCollection(self::INDEX_COLLECTION);
    }

    /**
     * Fetches toolkit for dealing with files stored in this database
     *
     * @param string $prefix - The prefix for the files and chunks
     *   collections.
     *
     * @return MongoGridFS - Returns a new gridfs object for this database.
     */
    public function getGridFS($prefix = 'fs')
    {
        return new MongoGridFS($this, $prefix);
    }

    /**
     * Log in to this database
     *
     * @param string $username - The username.
     * @param string $password - The password (in plaintext).
     * @param array $options - This parameter is an associative array of
     *   the form array("optionname" => boolean, ...).
     *
     * @return array - Returns database response. If the login was
     *   successful, it will return    If something went wrong, it will
     *   return    ("auth fails" could be another message, depending on
     *   database version and what when wrong).
     */
    public function authenticate($username, $password, $options = [])
    {
        $response = $this->command(['getnonce' => 1], $options);
        if (!isset($response['nonce'])) {
            throw new Exception('Cannot get nonce');
        }

        $nonce = $response['nonce'];

        $passwordDigest = md5(sprintf('%s:mongo:%s', $username, $password));
        $digest = md5(sprintf('%s%s%s', $nonce, $username, $passwordDigest));

        return $this->command([
            'authenticate' => 1,
            'user' => $username,
            'nonce' => $nonce,
            'key' => $digest
        ], $options);
    }

    /**
     * Creates a collection
     *
     * @param string $name - The name of the collection.
     * @param array $options - An array containing options for the
     *   collections. Each option is its own element in the options array,
     *   with the option name listed below being the key of the element. The
     *   supported options depend on the MongoDB server version. At the
     *   moment, the following options are supported:      capped    If the
     *   collection should be a fixed size.      size    If the collection is
     *   fixed size, its size in bytes.      max    If the collection is
     *   fixed size, the maximum number of elements to store in the
     *   collection.      autoIndexId    If capped is TRUE you can specify
     *   FALSE to disable the automatic index created on the _id field.
     *   Before MongoDB 2.2, the default value for autoIndexId was FALSE.
     *
     * @return MongoCollection - Returns a collection object representing
     *   the new collection.
     */
    public function createCollection($name, array $options = [])
    {
        $options['create'] = $name;

        return $this->command($options);
    }

    /**
     * Creates a database reference
     *
     * @param string $collection - The collection to which the database
     *   reference will point.
     * @param mixed $documentOrId - If an array or object is given, its
     *   _id field will be used as the reference ID. If a MongoId or scalar
     *   is given, it will be used as the reference ID.
     *
     * @return array - Returns a database reference array.   If an array
     *   without an _id field was provided as the document_or_id parameter,
     *   NULL will be returned.
     */
    public function createDBRef($collection, $documentOrId)
    {
        throw new Exception('Not Implemented');
    }

    /**
     * Drops a collection [deprecated]
     *
     * @param mixed $coll - MongoCollection or name of collection to drop.
     *
     * @return array - Returns the database response.
     */
    public function dropCollection($coll)
    {
        $collection = $this->selectCollection($coll);
        if (!$collection) {
            return;
        }

        return $collection->drop();
    }

    /**
     * Runs JavaScript code on the database server.
     *
     * @param mixed $code - MongoCode or string to execute.
     * @param array $args - Arguments to be passed to code.
     *
     * @return array - Returns the result of the evaluation.
     */
    public function execute($code, array $args = [])
    {
        return $this->command(array('$eval' => $code, 'args' => $args));
    }

    /**
     * Creates a database error
     *
     * @return bool - Returns the database response.
     */
    public function forceError()
    {
        throw new Exception('Not Implemented');
    }

    /**
     * Fetches the document pointed to by a database reference
     *
     * @param array $ref - A database reference.
     *
     * @return array - Returns the document pointed to by the reference.
     */
    public function getDBRef(array $ref)
    {
        throw new Exception('Not Implemented');
    }

    /**
     * Gets this databases profiling level
     *
     * @return int - Returns the profiling level.
     */
    public function getProfilingLevel()
    {
        throw new Exception('Not Implemented');
    }

    /**
     * Get the read preference for this database
     *
     * @return array
     */
    public function getReadPreference()
    {
        return $this->readPreference;
    }

    /**
     * Get slaveOkay setting for this database
     *
     * @return bool - Returns the value of slaveOkay for this instance.
     */
    public function getSlaveOkay()
    {
        throw new Exception('Not Implemented');
    }

    /**
     * Check if there was an error on the most recent db operation performed
     *
     * @return array - Returns the error, if there was one.
     */
    public function lastError()
    {
        throw new Exception('Not Implemented');
    }

    /**
     * Checks for the last error thrown during a database operation
     *
     * @return array - Returns the error and the number of operations ago
     *   it occurred.
     */
    public function prevError()
    {
        throw new Exception('Not Implemented');
    }

    /**
     * Repairs and compacts this database
     *
     * @param bool $preserve_cloned_files - If cloned files should be kept
     *   if the repair fails.
     * @param bool $backup_original_files - If original files should be
     *   backed up.
     *
     * @return array - Returns db response.
     */
    public function repair($preserveClonedFiles = false, $backupOriginalFiles = false)
    {
        throw new Exception('Not Implemented');
    }

    /**
     * Clears any flagged errors on the database
     *
     * @return array - Returns the database response.
     */
    public function resetError()
    {
        throw new Exception('Not Implemented');
    }

    /**
     * Sets this databases profiling level
     *
     * @param int $level - Profiling level.
     *
     * @return int - Returns the previous profiling level.
     */
    public function setProfilingLevel($level)
    {
        throw new Exception('Not Implemented');
    }

    /**
     * Set the read preference for this database
     *
     * @param string $readPreference -
     * @param array $tags -
     *
     * @return bool -
     */
    public function setReadPreference($readPreference, array $tags = null)
    {
        if ($newPreference = MongoClient::_validateReadPreference($readPreference, $tags)) {
            $this->readPreference = $newPreference;
        }
        return (bool)$newPreference;
    }

    /**
     * Change slaveOkay setting for this database
     *
     * @param bool $ok - If reads should be sent to secondary members of a
     *   replica set for all possible queries using this MongoDB instance.
     *
     * @return bool - Returns the former value of slaveOkay for this
     *   instance.
     */
    public function setSgetGridFSlaveOkay($ok = true)
    {
        throw new Exception('Not Implemented');
    }

    /**
     * The name of this database
     *
     * @return string - Returns this databases name.
     */
    public function __toString()
    {
        return $this->name;
    }
}
