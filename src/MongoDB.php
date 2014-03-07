<?php

use Mongofill\Protocol;

class MongoDB
{
    const NAMESPACES_COLLECTION = 'system.namespaces';
    const INDEX_COLLECTION = 'system.indexes';

    /**
     * @var string
     */
    private $name;

    /**
     * @var MongoClient
     */
    private $client;

    /**
     * @var Protocol
     */
    private $protocol;

    /**
     * @var array
     */
    private $collections = [];

    /**
     * @param MongoClient $client
     * @param string $name
     */
    function __construct(MongoClient $client, $name)
    {
        $this->name     = $name;
        $this->client   = $client;
        $this->protocol = $client->_getProtocol();
    }

    /**
     * @return MongoClient
     */
    public function _getClient()
    {
        return $this->client;
    }

    /**
     * @param string $name
     * @return MongoCollection
     */
    public function selectCollection($name)
    {
        if (!isset($this->collections[$name])) {
            $this->collections[$name] = new MongoCollection($this, $name);
        }
        return $this->collections[$name];
    }

    /**
     * @param string $name
     * @return MongoCollection
     */
    public function __get($name)
    {
        return $this->selectCollection($name);
    }

    public function _getFullCollectionName($collectionName)
    {
        return "{$this->name}.{$collectionName}";
    }

    /**
     * Drops this database
     */
    public function drop()
    {
        $response = $this->protocol->opQuery("{$this->name}.\$cmd", [ 'dropDatabase' => 1 ], 0, -1, 0);
        return $response['result'];
    }

    /**
     * Execute a database command
     */
    public function command($cmd)
    {
        $response = $this->protocol->opQuery("{$this->name}.\$cmd", $cmd, 0, -1, 0);
        return $response['result'][0];
    }

    /**
     * Gets an array of all MongoCollections for this database
     */
    public function listCollections($includeSystemCollections = false)
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

            $name =  $this->getCollectionName($collection['name']);

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

    public function __toString()
    {
        return $this->name;
    }
}
