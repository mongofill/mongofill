<?php

namespace Mongofill\Tests\Integration\Standalone;

use MongoGridFS;

class MongoGridFSTest extends TestCase
{
    const EXAMPLE_BIN_FILE = '/../../../../resources/MongoGridFS/example.png';

    public function setUp()
    {
        parent::setUp();
        $this->grid = $this->getTestDB()->getGridFS();
    }

    public function testFindOne()
    {
        $filename = __DIR__ . MongoGridFSTest::EXAMPLE_BIN_FILE;
        $this->grid->storeFile($filename);

        $file = $this->grid->findOne($filename);

        $this->assertInstanceOf('MongoGridFSFile', $file);
        $this->assertSame($filename, $file->getFilename());
    }

    public function testGet()
    {
        $filename = __DIR__ . MongoGridFSTest::EXAMPLE_BIN_FILE;
        $id = $this->grid->storeFile($filename);

        $file = $this->grid->get($id);

        $this->assertInstanceOf('MongoGridFSFile', $file);
        $this->assertSame($filename, $file->getFilename());
    }

    public function testDrop()
    {
        $filename = __DIR__ . MongoGridFSTest::EXAMPLE_BIN_FILE;
        $this->grid->storeFile($filename);

        $this->grid->drop();

        $chunks = $this->getTestDB()->{'fs.chunks'};
        $this->assertSame(0, $chunks->find()->count());

        $chunks = $this->getTestDB()->{'fs.files'};
        $this->assertSame(0, $chunks->find()->count());
    }

    public function testDelete()
    {
        $filename = __DIR__ . MongoGridFSTest::EXAMPLE_BIN_FILE;
        $id = $this->grid->storeFile($filename);

        $this->assertSame(1, (int) $this->grid->delete($id)['ok']);

        $chunks = $this->getTestDB()->{'fs.chunks'};
        $this->assertSame(0, $chunks->find()->count());

        $chunks = $this->getTestDB()->{'fs.files'};
        $this->assertSame(0, $chunks->find()->count());
    }

    public function testPut()
    {
        $metadata = ['foo' => 'bar'];
        $filename = __DIR__ . self::EXAMPLE_BIN_FILE;

        $id = $this->grid->put($filename, $metadata);
        $this->assertInstanceOf('MongoId', $id);

        $file = $this->grid->findOne($filename);
        $this->assertInstanceOf('MongoGridFSFile', $file);
    }

    public function testStoreBytes()
    {
        $metadata = [
            '_id' => 'numbers',
            'foo' => 'bar',
            'chunkSize' => 10
        ];

        $bytes = '123456789012345678901234567890';

        $id = $this->grid->storeBytes($bytes, $metadata);
        $this->assertSame($metadata['_id'], $id);

        $file = $this->grid->get($id);
        $this->assertInstanceOf('MongoGridFSFile', $file);

        $this->assertSame((string) $id, (string) $file->file['_id']);
        $this->assertSame('bar', $file->file['foo']);
        $this->assertSame(10, $file->file['chunkSize']);
        $this->assertInstanceOf('MongoDate', $file->file['uploadDate']);
        $this->assertSame(strlen($bytes), $file->file['length']);
        $this->assertSame(md5($bytes), $file->file['md5']);

        $chunks = $this->grid->chunks;
        $this->assertSame(3, $chunks->find(
            ['files_id' => $file->file['_id']]
        )->count());
    }

    public function testStoreFile()
    {
        $metadata = ['foo' => 'bar'];
        $filename = __DIR__ . self::EXAMPLE_BIN_FILE;

        $id = $this->grid->storeFile($filename, $metadata);
        $this->assertInstanceOf('MongoId', $id);

        $file = $this->grid->findOne($filename);
        $this->assertInstanceOf('MongoGridFSFile', $file);

        $this->assertSame((string) $id, (string) $file->file['_id']);
        $this->assertSame('bar', $file->file['foo']);
        $this->assertSame($filename, $file->file['filename']);
        $this->assertSame(262144, $file->file['chunkSize']);
        $this->assertInstanceOf('MongoDate', $file->file['uploadDate']);
        $this->assertSame(filesize($filename), $file->file['length']);
        $this->assertSame(md5_file($filename), $file->file['md5']);

        $chunks = $this->grid->chunks;
        $this->assertSame(3, $chunks->find(
            ['files_id' => $file->file['_id']]
        )->count());
    }

    public function testStoreUpload()
    {
        $_FILES['test']['name'] = '/tmp/foo.bar.txt';
        $_FILES['test']['tmp_name'] = __DIR__ . self::EXAMPLE_BIN_FILE;

        $id = $this->grid->storeUpload('test');
        $this->assertInstanceOf('MongoId', $id[0]);

        $file = $this->grid->findOne($_FILES['test']['name']);
        $this->assertInstanceOf('MongoGridFSFile', $file);

        $chunks = $this->grid->chunks;
        $this->assertSame(3, $chunks->find(
            ['files_id' => $file->file['_id']]
        )->count());
    }
}
