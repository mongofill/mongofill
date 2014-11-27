<?php

use Mongofill\Bson;

if (!function_exists('bson_encode')) {
    function bson_encode($value)
    {
        return Bson::encode($value);
    }
}

if (!function_exists('bson_decode')) {
    function bson_decode($data)
    {
        return Bson::decode($data);
    }
}

if (!function_exists('bson_encode_multiple')) {
    function bson_encode_multiple(array $documents)
    {
        return Bson::encode_multiple($documents);
    }
}

if (!function_exists('bson_decode_multiple')) {
    function bson_decode_multiple($data)
    {
        return Bson::decode_multiple($data);
    }
}
