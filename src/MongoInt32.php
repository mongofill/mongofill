<?php

class MongoInt32
{
    /**
     * @var string
     */
    public $value;

    /**
     * @param string $value
     */
    function __construct($value)
    {
        $this->value = (string)$value;
    }

    public function __toString()
    {
        return (string)$this->value;
    }
}