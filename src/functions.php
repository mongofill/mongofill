<?php

use Mongofill\Bson;

function bson_encode($value)
{
    return Bson::encode($value);
}

function bson_decode($data)
{
    return Bson::decode($data);
}