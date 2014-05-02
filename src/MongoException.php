<?php

/**
 * Default Mongo exception.
 */
class MongoException extends Exception
{
    const NOT_CONNECTED = 0;
    const SAVE_EMPTY_KEY = 1;
    const KEY_CONTAINS_DOT = 2;
    const INSERT_TOO_LARGE = 3;
    const NO_ELEMENTS = 4;
    const DOC_TOO_BIG = 5;
    const NO_DOC_SUPPLIED = 6;
    const WRONG_GROUP_TYPE = 7;
    const KEY_NOT_STRING = 8;
    const INVALID_REGEX = 9;
    const REF_NOT_STRING = 10;
    const DB_NOT_STRING = 11;
    const NON_UTF_STRING = 12;
    const MUTEX_ERROR = 13;
    const INDEX_TOO_LONG = 14;
}
