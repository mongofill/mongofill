<?php

/**
 * This class can be used to create regular expressions. Typically, these
 * expressions will be used to query the database and find matching strings.
 * More unusually, they can be saved to the database and retrieved.
 */
class MongoRegex
{
    /**
     * @var string
     */
    public $regex;

    /**
     * @var string
     */
    public $flags;

    /**
     * Creates a new regular expression
     *
     * @param string $regex - Regular expression string of the form
     *   /expr/flags.
     *
     * @return  - Returns a new regular expression.
     */
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

    /**
     * A string representation of this regular expression
     *
     * @return string - This regular expression in the form "/expr/flags".
     */
    public function __toString()
    {
        return '/' . $this->regex . '/' . $this->flags;
    }
}
