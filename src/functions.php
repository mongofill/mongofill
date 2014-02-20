<?php

use Mongofill\Bson;

function bson_encode($value)
{
    return Bson::encode($value);
}
