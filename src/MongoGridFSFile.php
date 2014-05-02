<?php

/**
 * A database file object.
 */
class MongoGridFSFile
{
    /**
     * @var array
     */
    public $file;

    /**
     * @var MongoGridFS
     */
    protected $gridfs;

    /**
     * Create a new GridFS file
     *
     * @param mongogridfs $gridfs - The parent MongoGridFS instance.
     * @param array $file - A file from the database.
     *
     * @return  - Returns a new MongoGridFSFile.
     */
    public function __construct(MongoGridFS $gridfs, array $file)
    {
        $this->gridfs = $gridfs;
        $this->file = $file;
    }

    /**
     * Returns this files filename
     *
     * @return string - Returns the filename.
     */
    public function getFilename()
    {
        return $this->file['filename'];
    }

    /**
     * Returns this files size
     *
     * @return int - Returns this file's size
     */
    public function getSize()
    {
        return $this->file['length'];
    }

    /**
     * Returns this files contents as a string of bytes
     *
     * @return string - Returns a string of the bytes in the file.
     */
    public function getBytes()
    {
        $this->trowExceptionIfInvalidLength();

        $bytes = '';

        $query = ['files_id' => $this->file['_id']];
        $sort = ['n' => 1];

        $chunks = $this->gridfs->chunks->find($query)->sort($sort);
        foreach ($chunks as $chunk) {
            $bytes .= $chunk['data']->bin;
        };

        return $bytes;
    }

    private function trowExceptionIfInvalidLength()
    {
        if (!isset($this->file['length'])) {
            throw new MongoException('couldn\'t find file size', 14);
        }
    }

    /**
     * Returns a resource that can be used to read the stored file
     *
     * @return stream - Returns a resource that can be used to read the
     *   file with
     */
    public function getResource()
    {
        throw new Exception('Not implemented');
    }

    /**
     * Writes this file to the filesystem
     *
     * @param string $filename - The location to which to write the file.
     *   If none is given, the stored filename will be used.
     *
     * @return int - Returns the number of bytes written.
     */
    public function write($filename = null)
    {
        if (!$filename) {
            $this->trowExceptionIfInvalidFilename();
            $filename = $this->file['filename'];
        }

        $bytes = $this->getBytes();

        return file_put_contents($filename, $bytes);
    }

    private function trowExceptionIfInvalidFilename()
    {
        if (!isset($this->file['filename'])) {
            throw new MongoException('Cannot find filename', 15);
        }
    }
}
