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
    public function __construct($sec = -1, $usec = 0) {
        if ($sec < 0) {
            $this->sec = time();
        } else {
            $this->sec = $sec;
        }

        $this->usec = $usec;
    }

    /**
     * Returns a string representation of this date
     *
     * @return string - This date.
     */
    public function __toString() {
        return (string) $this->sec . ' ' . $this->usec;
    }
}
