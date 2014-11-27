<?php

/**
 * MongoTimestamp is used by sharding. If you're not looking to write sharding
 * tools, what you probably want is MongoDate.
 */
class MongoTimestamp
{
    /**
     * @var int
     */
    private static $globalInc = 0;

    /**
     * @var int
     */
    public $sec;

    /**
     * @var int
     */
    public $inc;

    /**
     * Creates a new timestamp.
     *
     * @param int $sec - Number of seconds since January 1st, 1970.
     * @param int $inc - Increment.
     *
     * @return  - Returns this new timestamp.
     */
    public function __construct($sec = -1, $inc = -1)
    {
        $this->sec = $sec < 0 ? time() : (int)$sec;
        if ($inc < 0) {
            $this->inc = self::$globalInc;
            self::$globalInc++;
        } else {
            $this->inc = (int) $inc;
        }
    }

    /**
     * Returns a string representation of this timestamp
     *
     * @return string - The seconds since epoch represented by this
     *   timestamp.
     */
    public function __toString()
    {
        return (string)$this->sec;
    }
}
