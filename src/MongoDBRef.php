<?php

class MongoDbRef
{

    private function __construct($collection, $id, $db = null)
    {
        $this->{'$ref'} = $collection;
        $this->{'$id'} = $id;
        if ($db) {
            $this->{'$db'} = $db;
        }
    }

    public static function create($collection, $id, $database = null)
    {
        return new MongoDbRef($collection, $id, $database);
    }

    public static function isRef($ref)
    {
        if (is_array($ref)) {
            if (isset($ref['$id']) && isset($ref['$ref'])) {
                return true;
            }
        } elseif (is_object($ref)) {
            if (isset($ref->{'$ref'}) && isset($ref->{'$id'})) {
                return true;
            }
        }
        return false;
    }

    public static function get(MongoDB $db, $ref)
    {
        $refdb = null;
        if (is_array($ref)) {
            if (!isset($ref['$id']) || !isset($ref['$ref'])) {
                return;
            }
            $ns = $ref['$ref'];
            $id = $ref['$id'];
            if (isset($ref['$db'])) {
                $refdb = $ref['$db'];
            }
        } elseif (is_object($ref)) {
            if (!isset($ref->{'$id'}) || !isset($ref->{'$ref'})) {
                return;
            }
            $ns = $ref->{'$ref'};
            $id = $ref->{'$id'};
            if (isset($ref->{'$db'})) {
                $refdb = $ref->{'$db'};
            }
        }
        if (!is_string($ns)) {
            throw new MongoException('MongoDBRef::get: $ref field must be a string', 10);
        }
        if (isset($refdb)) {
            if (!is_string($refdb)) {
                throw new MongoException('MongoDBRef::get: $db field of $ref must be a string', 11);
            }
            if ($refdb != $db->getDB()) {
                $db = $db->link->selectDB($refdb);
            }
        }
        $collection = new MongoCollection($db, $ns);
        $query = array('_id' => $id);
        return $collection->findOne($query);
    }
}

