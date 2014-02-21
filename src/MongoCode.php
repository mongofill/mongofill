<?php

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
     * @param string $code
     * @param array $scope
     */
    function __construct($code, array $scope = [])
    {
        $this->code = (string)$code;
        $this->scope = $scope;
    }

    public function getScope()
    {
        return $this->scope;
    }

    public function __toString()
    {
        return $this->code;
    }
}