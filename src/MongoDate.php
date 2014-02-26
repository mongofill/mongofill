<?php

/**
 * Class MongoDate
 */

class MongoDate
{
    /* Fields */

    public $sec ;
    public $usec ;

    /* Methods */

    /**
     * @param $sec int
     * @param $usec int
     */

    public function __construct ($sec = -1, $usec = 0 ) {
        if($sec < 0 ){
            $this->sec = time();
        } else {
            $this->sec = $sec;
        }
        $this->usec = $usec;
    }

    /**
     * @returns string
     */

    public function  __toString (  ){
        return "" . $this->sec . " " . $this->usec;
    }
}
