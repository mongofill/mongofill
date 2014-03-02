<?php

class MongoBinData
{

    /* Constants */
    const GENERIC      = 0;
    const FUNC         = 1;
    const BYTE_ARRAY   = 2;
    const UUID         = 3;
    const UUID_RFC4122 = 4;
    const MD5          = 5;
    const CUSTOM       = 128;

    /**
     * @var string
     */
    public $bin;

    /**
     * @var int
     */
    public $type;

    /**
     * @param string $data
     * @param int $type
     */
    public function __construct($data, $type = 2)
    {
        $this->bin = $data;
        $this->type = $type;
    }

    public function __toString()
    {
        return "<Mongo Binary Data>";
    }
}
