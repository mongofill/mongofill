<?php

class MongoRegex
{
    public $regex;
    public $flags;

    public function __construct($regex)
    {
        if (!$this->isValidRegEx($regex)) {
            throw new MongoException('invalid regex');
        }
        
        $this->parseRegex($regex);
    }

    public function __toString()
    {
        return (string) $this->regex;
    }

    private function isValidRegEx($regex)
    {
        return @preg_match($regex, null) !== false;
    }

    private function parseRegex($regex)
    {
        $delimiter = $regex[0];
        $flagsStart = strrpos($regex, $delimiter);  

        $this->regex = substr($regex, 1, $flagsStart - 1);
        $this->flags = substr($regex, $flagsStart + 1);
    }
}