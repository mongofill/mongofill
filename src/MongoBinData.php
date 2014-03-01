<?php

class MongoBinData
{
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
