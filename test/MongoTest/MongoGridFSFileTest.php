<?php

class MongoGridFSFileTest extends BaseTest
{
    public function setUp()
    {
        $this->grid = $this->getTestDB()->getGridFS();
    }

    public function testGetFilename()
    {
        $file = new MongoGridFSFile($this->grid, $this->getMockArray());
        $this->assertSame('foo.jpg', $file->getFilename());
    }

    public function testGetSize()
    {
        $file = new MongoGridFSFile($this->grid, $this->getMockArray());
        $this->assertSame(31184902, $file->getSize());
    }

    private function getMockArray()
    {
        return [
            '_id' => new MongoId(),
            'metadata' => [
                'filename' => 'foo.jpg',
            ],
            'filename' => 'foo.jpg',
            'uploadDate' => new MongoDate(),
            'length' => 31184902,
            'chunkSize' => 262144,
            'md5' => 'c6e7dead49dbba0eb9546a7fafcc9c0b',
        ];
    }
}