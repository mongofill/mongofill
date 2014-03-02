<?php

class MongoRegex
{
    public $regex;
    public $flags;

    public function __construct($regex)
    {
        $flagsStart = strrpos($regex, $regex[0]);  
        $this->regex = (string)substr($regex, 1, $flagsStart - 1);
        $this->flags = (string)substr($regex, $flagsStart + 1);

        if (!$this->regexIsValid($regex)) {
            throw new MongoException('invalid regex', MongoException::INVALID_REGEX);
        }
    }

    public function regexIsValid($regex)
    {
        return substr_count($regex, '/') >= 2 && 
          ((strlen($regex) && @preg_match($regex, null) !== false) || strlen($this->flags));
    }

    public function __toString()
    {
        return '/' . $this->regex . '/' . $this->flags;
    }

}
