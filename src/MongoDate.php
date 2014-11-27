<?php

/**
 * Represent date objects for the database. This class should be used to save
 * dates to the database and to query for dates.
 */
class MongoDate
{
    /**
     * @var int
     */
    public $sec ;

    /**
     * @var int
     */
    public $usec ;

    /**
     * Creates a new date.
     *
     * @param int $sec - Number of seconds since January 1st, 1970.
     * @param int $usec - Microseconds. Please be aware though that
     *   MongoDB's resolution is milliseconds and not microseconds, which
     *   means this value will be truncated to millisecond resolution.
     *
     * @return  - Returns this new date.
     */
    public function __construct($sec = 0, $usec = 0)
    {
        if (func_num_args() == 0) {
            $time = microtime(true);
            $sec = floor($time);
            $usec = ($time - $sec) * 1000000.0;
        }
        $this->sec = $sec;
        $this->usec = (int)floor(($usec / 1000)) * 1000;
    }

    /**
     * Returns a string representation of this date
     *
     * @return string - This date.
     */
    public function __toString()
    {
        return (string) $this->sec . ' ' . $this->usec;
    }

    /**
     * returns date in milliseconds since Unix epoch
     *
     * @return int
     */
    public function getMs()
    {
        return $this->sec*1000 + (int)floor($this->usec/1000);
    }

    /**
     * Creates MongoDate from milliseconds
     *
     * @param int milliseconds since Unix epoch
     */
    public static function createFromMs($val)
    {
        $usec = (int)(((($val * 1000) % 1000000) + 1000000) % 1000000);
        $sec = (int)(($val / 1000) - ($val < 0 && $usec));
        return new MongoDate($sec, $usec);
    }
}
