<?php

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
   * @param $sec int
   * @param $inc int
   * @return void
   */
  public function __construct($sec = -1, $inc = -1) {
    $this->sec = $sec < 0 ? time() : (int)$sec;
    if ($inc < 0) {
      $this->inc = self::$globalInc;
      self::$globalInc++;
    } else {
      $this->inc = (int)$inc;
    }
  }

  /**
   * @return string
   */
  public function  __toString() {
    return (string)$this->sec;
  }

}
