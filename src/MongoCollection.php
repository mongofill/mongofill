<?php

use Mongofill\Protocol;

class MongoCollection
{
    /**
     * @var string
     */
    private $fqn;

    /**
     * @var string
     */
    private $name;


    /**
     * @var MongoDB
     */
    public $db;

    /**
     * @var MongoClient
     */
    private $client;

    /**
     * @var Protocol
     */
    private $protocol;

    /**
     * @param MongoDB $db
     * @param string $name
     */
    function __construct(MongoDB $db, $name)
    {
        $this->db       = $db;
        $this->name     = $name;
        $this->fqn      = $db->_getFullCollectionName($name);
        $this->client   = $db->_getClient();
        $this->protocol = $this->client->_getProtocol();
    }

    public function count($query=[], $limit=0, $skip=0 )
    {
        $result = $this->db->command( array( 'count'=>$this->name, 'query'=> $query, 'limit' => $limit, 'skip'=>$skip));
        if(!empty($result[0]['ok'])){
            return $result[0]['n'];
        }
        return FALSE;
    }

    /**
     * @param array $query
     * @param array $fields
     * @return MongoCursor
     */
    public function find(array $query = [], array $fields = [])
    {
        return new MongoCursor($this->client, $this->fqn, $query, $fields);
    }

    /**
     * Drop the current collection
     * @returns array
     */

    public function drop()
    {
        $this->db->command(array('drop'=>$this->name));
    }

    /**
     * Return the collection name - NOT the fqn
     * @return string
     */

    public function getName()
    {
        return $this->name;
    }

    /**
     * Insert a document
     * @param array $a
     * @param array $options
     * @returns bool|array
     */

    public function insert(array $a, array $options = [])
    {
        $this->batchInsert([ $a ], $options);

        // Fake response for async insert -
        // TODO: detect "w" option and return status array
        return TRUE;
    }

    /**
     * Insert a set of documents
     * @param array $a
     * @param array $options
     * @returns bool|array
     */

    public function batchInsert(array $a, array $options = [])
    {
        $this->protocol->opInsert($this->fqn, $a, false);

        // Fake response for async insert -
        // TODO: detect "w" option and return status array
        return TRUE;
    }
}
