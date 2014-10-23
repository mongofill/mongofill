<?php

/**
 * The MongoResultException is thrown by several command helpers (such as
 * MongoCollection::findAndModify) in the event of failure. The original
 * result document is available through MongoResultException::getDocument.
 */
class MongoResultException extends MongoException
{
    /**
     * Retrieve the full result document
     *
     * @return array - The full result document as an array, including
     *   partial data if available and additional keys.
     */
    public function getDocument()
    {
        throw new Exception('Not Implemented');
    }
}
