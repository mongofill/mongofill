<?php

namespace Mongofill\Benchmarks;

class BsonEvent extends AthleticEvent
{

    public function __construct()
    {
        // generate documents to encode
        $this->simpleDoc = $this->buildSimpleDocument();
        $this->simpleNestedDoc = $this->buildSimpleNestedDocument();
        $this->complexDoc = $this->buildComplexDocument();
        $this->complexNestedDoc = $this->buildComplexNestedDocument();

        // generate data and make sure hhvm jit compiles the functions
        for ($i = 0; $i < 20; $i++) {
            $this->simpleBson = $this->simpleEncode();
            $this->simpleNestedBson = $this->simpleNestedEncode();
            $this->complexBson = $this->complexEncode();
            $this->complexNestedBson = $this->complexNestedEncode();
            $this->simpleDecode();
            $this->simpleNestedDecode();
            $this->complexDecode();
            $this->complexNestedDecode();
        }
    }

    /**
     * @iterations 10000
     */
    public function simpleEncode()
    {
        $bson = bson_encode($this->simpleDoc);
        return $bson;
    }

    /**
     * @iterations 10000
     */
    public function simpleNestedEncode()
    {
        $bson = bson_encode($this->simpleNestedDoc);
        return $bson;
    }

    /**
     * @iterations 10000
     */
    public function complexEncode()
    {
        $bson = bson_encode($this->complexDoc);
        return $bson;
    }

    /**
     * @iterations 10000
     */
    public function complexNestedEncode()
    {
        $bson = bson_encode($this->complexNestedDoc);
        return $bson;
    }

    /**
     * @iterations 10000
     */
    public function simpleDecode()
    {
        $doc = bson_decode($this->simpleBson);
    }

    /**
     * @iterations 10000
     */
    public function simpleNestedDecode()
    {
        $doc = bson_decode($this->simpleNestedBson);
    }

    /**
     * @iterations 10000
     */
    public function complexDecode()
    {
        $doc = bson_decode($this->complexBson);
    }

    /**
     * @iterations 10000
     */
    public function complexNestedDecode()
    {
        $doc = bson_decode($this->complexNestedBson);
    }
}
