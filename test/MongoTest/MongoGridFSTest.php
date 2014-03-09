<?php

class MongoGridFSTest extends BaseTest
{
    const EXAMPLE_BIN_FILE = '/../resources/MongoGridFS/example.png';

    public function setUp()
    {
        parent::setUp();
        $this->grid = $this->getTestDB()->getGridFS();
    }

    public function testFindOne()
    {
        $filename = __DIR__ . MongoGridFSTest::EXAMPLE_BIN_FILE;
        $this->grid->storeFile($filename);

        $file = $this->grid->findOne(basename($filename));

        $this->assertInstanceOf('MongoGridFSFile', $file);
        $this->assertSame(basename($filename), $file->getFilename());
    }

    public function testStoreFile()
    {
        $metadata = ['foo' => 'bar'];
        $filename = __DIR__ . self::EXAMPLE_BIN_FILE;

        $id = $this->grid->storeFile($filename, $metadata);
        $this->assertInstanceOf('MongoId', $id);

        $file = $this->grid->findOne(basename($filename));
        $this->assertInstanceOf('MongoGridFSFile', $file);

        $this->assertSame((string) $id, (string) $file->file['_id']);
        $this->assertSame($metadata, $file->file['metadata']);
        $this->assertSame(basename($filename), $file->file['filename']);
        $this->assertSame(MongoGridFS::DEFAULT_CHUNK_SIZE, $file->file['chunkSize']);
        $this->assertInstanceOf('MongoDate', $file->file['uploadDate']);
        $this->assertSame(filesize($filename), $file->file['length']);
        $this->assertSame(md5_file($filename), $file->file['md5']);

        $chunks = $this->grid->chunks;
        $this->assertSame(3, $chunks->find(
            ['files_id' => $file->file['_id']]
        )->count());
    }
}