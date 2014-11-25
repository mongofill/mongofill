<?php

/**
 * Utilities for storing and retrieving files from the database.
 */
class MongoGridFS extends MongoCollection
{
    const DEFAULT_CHUNK_SIZE = 262144; //256k

    /**
     * @var MongoCollection
     */
    public $chunks;

    /**
     * @var MongoCollection
     */
    private $files;

    /**
     * @var string
     */
    protected $filesName;

    /**
     * @var string
     */
    protected $chunksName;

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
        $thisName = $prefix . '.files';
        $this->chunksName = $prefix . '.chunks';

        $this->chunks = $db->selectCollection($this->chunksName);

        parent::__construct($db, $thisName);
    }

    /**
     * Delete a file from the database
     *
     * @param mixed $id - _id of the file to remove.
     * @return bool - Returns if the remove was successfully sent to the database.
     */
    public function delete($id)
    {
        return $this->remove(['_id' => $id]);
    }

    /**
     * Drops the files and chunks collections
     *
     * @return array - The database response.
     */
    public function drop()
    {
        $this->chunks->drop();
        parent::drop();
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
            $this->__toString(),
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

        return $cur->getNext();
    }

    /**
     * Retrieve a file from the database
     *
     * @param mixed $id - _id of the file to find.
     * @return MongoGridFSFile - Returns the file, if found, or NULL.
     */
    public function get($id)
    {
        return $this->findOne(['_id' => $id]);
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
        return $this->storeFile($filename, $metadata);
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
        //TODO: implement $options
        $files = parent::find($criteria, ['_id' => 1]);
        $ids = [];
        foreach ($files as $record) {
            $ids[] = $record['_id'];
        }

        if (!$ids) {
            return false;
        }

        $this->chunks->remove(['files_id' => [
            '$in' => $ids
        ]]);

        return parent::remove(['_id' => [
            '$in' => $ids
        ]], $options);
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
        $chunkSize = self::DEFAULT_CHUNK_SIZE;
        if (isset($metadata['chunkSize'])) {
            $chunkSize = $metadata['chunkSize'];
        }

        $file = $this->insertFileFromBytes($bytes, $metadata, $chunkSize);
        $this->insertChunksFromBytes($bytes, $file['_id'], $chunkSize);

        return $file['_id'];
    }

    private function insertFileFromBytes($bytes, array $metadata, $chunkSize)
    {
        $record = [
            'uploadDate' => new MongoDate(),
            'chunkSize' => $chunkSize,
            'length' => mb_strlen($bytes, '8bit'),
            'md5' => md5($bytes)
        ];

        $record = array_merge($metadata, $record);
        $this->insert($record);

        return $record;
    }

    private function insertChunksFromBytes($bytes, $id, $chunkSize)
    {
        $length = mb_strlen($bytes, '8bit');
        $offset = 0;
        $n = 0;

        while($offset < $length) {
            $data = mb_substr($bytes, $offset, $chunkSize, '8bit');
            $this->insertChunk($id, $data, $n++);

            $offset += $chunkSize;
        }
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
        $this->throwExceptionIfFilenameNotExists($filename);

        $chunkSize = self::DEFAULT_CHUNK_SIZE;
        if (isset($metadata['chunkSize'])) {
            $chunkSize = $metadata['chunkSize'];
        }

        $file = $this->insertFileFromFilename($filename, $metadata, $chunkSize);
        $this->insertChunksFromFilename($filename, $file['_id'], $chunkSize);

        return $file['_id'];
    }

    private function throwExceptionIfFilenameNotExists($filename)
    {
        if (!file_exists($filename)) {
            throw new MongoException(sprintf(
                'error setting up file: %s',
                $filename
            ));
        }
    }

    private function insertFileFromFilename($filename, array $metadata, $chunkSize)
    {
        $record = [
            'filename' => $filename,
            'uploadDate' => new MongoDate(),
            'chunkSize' => $chunkSize,
            'length' => filesize($filename),
            'md5' => md5_file($filename)
        ];

        if (isset($metadata['filename'])) {
            $record['filename'] = $metadata['filename'];
        }

        $record = array_merge($metadata, $record);
        $this->insert($record);

        return $record;
    }

    private function insertChunksFromFilename($filename, MongoId $id, $chunkSize)
    {
        $handle = fopen($filename, 'r');

        $n = 0;
        while (!feof($handle)) {
            $data = stream_get_contents($handle, $chunkSize);
            $this->insertChunk($id, $data, $n++);
        }

        fclose($handle);
    }

    private function insertChunk($filesId, $data, $n)
    {
        $record = [
            'files_id' => $filesId,
            'data' => new MongoBinData($data),
            'n' => $n
        ];

        return $this->chunks->insert($record);
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
    public function storeUpload($name, array $metadata  = [])
    {
        $this->throwExceptionIfMissingUpload($name);
        $this->throwExceptionIfMissingTmpName($name);

        $uploaded = $_FILES[$name];
        $uploaded['tmp_name'] = (array) $uploaded['tmp_name'];
        $uploaded['name'] = (array) $uploaded['name'];

        $results = [];
        foreach ($uploaded['tmp_name'] as $key => $file) {
            $metadata['filename'] = $uploaded['name'][$key];
            $results[] = $this->storeFile($file, $metadata);
        }

        return $results;
    }

    private function throwExceptionIfMissingUpload($name)
    {
        if (isset($_FILES[$name])) {
            return;
        }

        throw new MongoGridFSException(sprintf(
            'could not find uploaded file %s',
            $name
        ), 11);
    }

    private function throwExceptionIfMissingTmpName($name)
    {
        if (isset($_FILES[$name]['tmp_name']) && (
            is_array($_FILES[$name]['tmp_name']) ||
            is_string($_FILES[$name]['tmp_name'])
        )) {
            return;
        }

        throw new MongoGridFSException(
            'tmp_name was not a string or an array',
            13
        );
    }
}
