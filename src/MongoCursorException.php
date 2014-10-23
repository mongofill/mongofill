<?php

/**
 * Caused by accessing a cursor incorrectly or a error receiving a reply.
 */
class MongoCursorException extends MongoException
{
    /**
     * The hostname of the server that encountered the error
     *
     * @return string - Returns the hostname, or NULL if the hostname is
     *   unknown.
     */
    public function getHost()
    {
        throw new Exception('Not Implemented');
    }
}
