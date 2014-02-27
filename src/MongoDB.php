<?php

use Mongofill\Protocol;

class MongoDB
{
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
     * Drops this database
     */
    public function command($cmd)
    {
        $response = $this->protocol->opQuery("{$this->name}.\$cmd", $cmd, 0, -1, 0);
        return $response['result'];
    }
}
