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
    private $db;

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
            return (int) $result[0]['n'];
        }

        return false;
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
     * @param array $query
     * @param array $fields
     * @return array or null
     */
    public function findOne(array $query = [], array $fields = [])
    {
        $cur = $this->find($query, $fields)->limit(1);
        return ($cur->valid()) ? $cur->current() : null;
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

    /**
     * __toString return full name of collections.
     * @return string
     */

    public function  __toString()
    {
        return $this->fqn;
    }

    /**
     * @param       array $criteria Query specifing objects to be updated
     * @param       array $new_object document to update
     * @param       array $options
     *
     * @return bool
     */

    public function update(array $criteria , array $new_object , array $options = [] )
    {
         $this->protocol->opUpdate($this->fqn, $criteria, $new_object, $options);
    }

    public function save($a, array $options = [])
    {
        if(empty($a)){
            return FALSE;
        }
        if(!empty($a['_id'])){
            $this->update(array('_id' => $a['_id']), $a, $options );
        } else {
            return $this->insert($a, $options);
        }
        //TODO: Handle timeout
        return TRUE;
    }

    public function remove(array $criteria = [], array $options = [])
    {
        $this->protocol->opDelete($this->fqn, $criteria, $options);
    }

    /**
     * @param boolean $scan_data Enable scan of base class
     * @param boolean $full
     */

    public function validate($full=FALSE, $scan_data = FALSE)
    {
        $result =  $this->db->command(array('validate'=>$this->name, 'full'=>$full, 'scandata'=>$scan_data));
        if(!empty($result[0])){
            return $result[0];
        }
        return FALSE;
    }

    /**
     * @var string|array $keys
     * @return string|null
     */
    protected static function toIndexString($keys)
    {
        if (is_string($keys)) {
          return str_replace('.', '_', $keys . '_1');
        } else if (is_array($keys) || is_object($keys)) {
          $keys = (array)$keys;
          foreach ($keys as $k => $v) {
            $keys[$k] = str_replace('.', '_', $k . '_' . $v);
          }
          return implode('_', $keys);
        } 
        trigger_error('MongoCollection::toIndexString(): The key needs to be either a string or an array', E_USER_WARNING);
        return null;
    }

}
