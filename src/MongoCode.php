<?php

/**
 * Represents JavaScript code for the database.
 */
class MongoCode
{
    /**
     * @var string
     */
    private $code;

    /**
     * @var array
     */
    private $scope;

    /**
     * Creates a new code object
     *
     * @param string $code - A string of code.
     * @param array $scope - The scope to use for the code.
     *
     * @return  - Returns a new code object.
     */
    public function __construct($code, array $scope = [])
    {
        $this->code = (string) $code;
        $this->scope = $scope;
    }

    public function getScope()
    {
        return $this->scope;
    }

    /**
     * Returns this code as a string
     *
     * @return string - This code, the scope is not returned.
     */
    public function __toString()
    {
        return $this->code;
    }
}
