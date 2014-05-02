<?php

/**
 * An object that can be used to store or retrieve binary data from the
 * database.
 */
class MongoBinData
{
    const GENERIC = 0;
    const FUNC = 1;
    const BYTE_ARRAY = 2;
    const UUID = 3;
    const UUID_RFC4122 = 4;
    const MD5 = 5;
    const CUSTOM = 128;

    /**
     * @var string
     */
    public $bin;

    /**
     * @var int
     */
    public $type;

    /**
     * Creates a new binary data object.
     *
     * @param string $data - Binary data.
     * @param int $type - Data type.
     *
     * @return  - Returns a new binary data object.
     */
    public function __construct($data, $type = self::BYTE_ARRAY)
    {
        $this->bin = $data;
        $this->type = $type;
    }

    /**
     * The string representation of this binary data object.
     *
     * @return string - Returns the string "Mongo Binary Data". To access
     *   the contents of a MongoBinData, use the bin field.
     */
    public function __toString()
    {
        return "<Mongo Binary Data>";
    }
}
