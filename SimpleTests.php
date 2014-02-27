<?php


require_once('vendor/autoload.php');

$m = new \MongoClient();

$c = $m->selectCollection("mongofillsimpletest", "test1");

$c->drop();

$c->insert(array('foo'=> TRUE));

echo print_r($c->validate(TRUE), true);
