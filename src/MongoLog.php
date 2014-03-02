<?php

class MongoLog
{

  /* Constants */
  const NONE = 0;
  const ALL  = 31;

  /* Level constants */
  const WARNING = 1;
  const INFO    = 2;
  const FINE    = 4;

  /* Module constants */
  const RS     = 1;
  const POOL   = 1; // This is not a typo, it's mapped to RS for backwards compatibility.
  const IO     = 4;
  const SERVER = 8;
  const PARSE  = 16;

  /**
   * @var int
   */
  private static $level;

  /**
   * @var int
   */
  private static $module;

  /**
   * @var callable
   */
  private static $callback;

  /**
   * @return string
   */
  public static function getCallback()
  {
    return self::$callback;
  }

  /**
   * @return int
   */
  public static function getLevel()
  {
    return self::$level; 
  }

  /**
   * @return int
   */
  public static function getModule()
  {
    return self::$module;
  }

  /**
   * @param callable $log_function
   * @return void
   */
  public static function setCallback(callable $log_function)
  {
    self::$callback = $log_function;
  }

  /**
   * @param int $level
   * @return void
   */
  public static function setLevel($level)
  {
    self::$level = $level;
  }

  /**
   * @param int $module
   * @return void
   */
  public static function setModule($module)
  {
    self::$module = $module;
  }

}
