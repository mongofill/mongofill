<?php

namespace Mongofill\Tests\Integration\Standalone;

use MongoId;
use MongoDate;
use MongoGridFSFile;

class MongoGridFSFileTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->grid = $this->getTestDB()->getGridFS();
    }

    public function testGetFilename()
    {
        $file = new MongoGridFSFile($this->grid, $this->getMockArray());
        $this->assertSame('foo.txt', $file->getFilename());
    }

    public function testGetSize()
    {
        $file = new MongoGridFSFile($this->grid, $this->getMockArray());
        $this->assertSame(447, $file->getSize());
    }

    public function testGetBytes()
    {
        $filename = __DIR__ . MongoGridFSTest::EXAMPLE_BIN_FILE;
        $this->grid->storeFile($filename);

        $file = $this->grid->findOne($filename);
        $this->assertSame(filesize($filename), strlen($file->getBytes()));
    }

    public function testWrite()
    {
        $output = '/tmp/' . rand();

        $filename = __DIR__ . MongoGridFSTest::EXAMPLE_BIN_FILE;
        $this->grid->storeFile($filename);

        $file = $this->grid->findOne($filename);
        $file->write($output);

        $this->assertTrue(file_exists($output));
        $content = file_get_contents($output);

        $this->assertSame($file->file['length'], strlen($content));
        $this->assertSame($file->file['md5'], md5($content));
    }

    private function getMockArray()
    {
        return [
            '_id' => new MongoId('531b7d94acf16443ae0041a8'),
            'metadata' => [
                'filename' => 'foo.txt',
            ],
            'filename' => 'foo.txt',
            'uploadDate' => new MongoDate(),
            'length' => 447,
            'chunkSize' => 262144,
            'md5' => 'f5c85408e67ef9a90f3e416863ba84de',
        ];
    }
}
