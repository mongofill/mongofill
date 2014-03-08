<?php

/**
 * Utilities for storing and retrieving files from the database.   
 */
class MongoGridFS {

    /**
     * @var string
     */
    private $prefix;

    /**
     * @var string
     */
    private $chunks;

    /**
     * @var MongoDB
     */
    private $db;

    /**
     * Creates new file collections
     *
     * @param mongodb $db - Database.
     * @param string $prefix -
     * @param mixed $chunks -
     */
    public function __construct(MongoDB $db, $prefix = 'fs', $chunks = 'fs')
    {
        $this->db = $db;
        $this->files = $db->selectCollection($prefix . '.files');
        $this->chunks = $db->selectCollection($prefix . '.chunks');
    }

    /**
     * Delete a file from the database
     *
     * @param mixed $id - _id of the file to remove.
     * @return bool - Returns if the remove was successfully sent to the database.
     */
    public function delete($id)
    {
        throw new Exception('Not implemented');
    }

    /**
     * Drops the files and chunks collections
     *
     * @return array - The database response.
     */
    public function drop()
    {
        throw new Exception('Not implemented');
    }

    /**
     * Queries for files
     *
     * @param array $query - The query.
     * @param array $fields - Fields to return.
     * @return MongoGridFSCursor - A MongoGridFSCursor.
     */
    public function find(array $query = [], array $fields = [])
    {
        return new MongoGridFSCursor(
            $this,
            $this->db->_getClient(), 
            $this->files->__toString(),
            $query,
            $fields
        );
    }

    /**
     * Returns a single file matching the criteria
     *
     * @param mixed $query - The filename or criteria for which to search.
     * @param mixed $fields - Fields to return.
     * @return MongoGridFSFile - Returns a MongoGridFSFile or NULL.
     */
    public function findOne($query = [], array $fields = [])
    {
        if (is_string($query)) {
            $query = ['filename' => $query];
        }

        $cur = $this->find($query, $fields)->limit(1);

        return $cur->current();
    }

    /**
     * Retrieve a file from the database
     *
     * @param mixed $id - _id of the file to find.
     * @return MongoGridFSFile - Returns the file, if found, or NULL.
     */
    public function get($id)
    {
        throw new Exception('Not implemented');
    }

    /**
     * Stores a file in the database
     *
     * @param string $filename - Name of the file to store.
     * @param array $metadata - Other metadata fields to include in the document.
     * @return mixed -
     */
    public function put($filename, array $metadata = [])
    {
        throw new Exception('Not implemented');
    }

    /**
     * Removes files from the collections
     *
     * @param array $criteria -
     * @param array $options - Options for the remove. Valid options are:
     * @return bool - Returns if the removal was successfully sent to the database.
     */
    public function remove(array $criteria = [],  array $options = [])
    {
        throw new Exception('Not implemented');
    }

    /**
     * Stores a string of bytes in the database
     *
     * @param string $bytes - String of bytes to store.
     * @param array $metadata - Other metadata fields to include in the document.
     * @param array $options - Options for the store.
     *
     * @return mixed -
     */
    public function storeBytes($bytes, array $metadata = [], array $options = [])
    {
        throw new Exception('Not implemented');
    }

    /**
     * Stores a file in the database
     *
     * @param string $filename - Name of the file to store.
     * @param array $metadata - Other metadata fields to include in the document.
     * @param array $options - Options for the store.
     * @return mixed -
     */
    public function storeFile($filename, array $metadata = [], array $options = [])
    {
        throw new Exception('Not implemented');
    }

    /**
     * Stores an uploaded file in the database
     *
     * @param string $name - The name of the uploaded file to store. This
     *   should correspond to the file field's name attribute in the HTML
     *   form.
     * @param array $metadata - Other metadata fields to include in the
     *   file document.    The filename index will be populated with the
     *   filename used.
     * @return mixed -
     */
    public function storeUpload($name, array $metadata)
    {
        throw new Exception('Not implemented');
    }

}