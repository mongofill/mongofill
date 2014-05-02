<?php

/**
 * The class can be used to save 32-bit integers to the database on a 64-bit
 * system.
 */
class MongoInt32
{
    /**
     * @var string
     */
    public $value;

    /**
     * Creates a new 32-bit integer.
     *
     * @param string $value - A number.
     *
     * @return  - Returns a new integer.
     */
    public function __construct($value)
    {
        $this->value = (string) $value;
    }

    /**
     * Returns the string representation of this 32-bit integer.
     *
     * @return string - Returns the string representation of this integer.
     */
    public function __toString()
    {
        return (string) $this->value;
    }
}
