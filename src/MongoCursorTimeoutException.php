<?php

/**
 * Caused by a query timing out. You can set the length of time to wait before
 * this exception is thrown by calling MongoCursor::timeout() on the cursor or
 * setting MongoCursor::$timeout.
 */
class MongoCursorTimeoutException extends MongoException
{
}
