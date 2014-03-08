<?php

class MongoGridFSTest extends BaseTest
{
    public function setUp()
    {
        $this->grid = $this->getTestDB()->getGridFS();

        $mongo = new MongoClient();
        $db = $mongo->myfiles;

        // GridFS
        $this->grid = $db->getGridFS();
    }

    public function testFindOne()
    {
        $filename = 'oobelib.log';
        $file = $this->grid->findOne($filename);

        $this->assertInstanceOf('MongoGridFSFile', $file);
        $this->assertSame($filename, $file->getFilename());
    }
}