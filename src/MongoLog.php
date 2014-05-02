<?php

/**
 * Logging can be used to get detailed information about what the driver is
 * doing. The logging mechanism as used by MongoLog emits all log messages as
 * a PHP notice.
 */
class MongoLog
{
    /* Constants */
    const NONE = 0;
    const ALL = 31;

    /* Level constants */
    const WARNING = 1;
    const INFO = 2;
    const FINE = 4;

    /* Module constants */
    const RS = 1;
    const POOL = 1; // This is not a typo, it's mapped to RS for backwards compatibility.
    const IO = 4;
    const SERVER = 8;
    const PARSE = 16;

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
     * Retrieve the previously set callback function name
     *
     * @return void - Returns the callback function name, or FALSE if not
     *   set yet.
     */
    public static function getCallback()
    {
        return self::$callback;
    }

    /**
     * Gets the log level
     *
     * @return int - Returns the current level.
     */
    public static function getLevel()
    {
        return self::$level;
    }

    /**
     * Gets the modules currently being logged
     *
     * @return int - Returns the modules currently being logged.
     */
    public static function getModule()
    {
        return self::$module;
    }

    /**
     * Set a callback function to be called on events
     *
     * @param callable $logFunction - The function to be called on events.
     *
     * @return void -
     */
    public static function setCallback(callable $logFunction)
    {
        self::$callback = $logFunction;
    }

    /**
     * Sets logging level
     *
     * @param int $level - The levels you would like to log.
     *
     * @return void -
     */
    public static function setLevel($level)
    {
        self::$level = $level;
    }

    /**
     * Sets driver functionality to log
     *
     * @param int $module - The module(s) you would like to log.
     *
     * @return void -
     */
    public static function setModule($module)
    {
        self::$module = $module;
    }
}
