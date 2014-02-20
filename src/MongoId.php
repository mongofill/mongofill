<?php

class MongoId
{
    /**
     * @var string
     */
    public $id;

    private $timestamp;
    private $hostname;
    private $pid;
    private $inc;

    static private $refInc = null;

    /**
     * @param null|string $id
     */
    function __construct($id = null)
    {
        if (null === $id) {
            $this->id = $this->generateId();
        } else {
            $this->id = $id;
        }
    }

    private function generateId()
    {
        if (null === self::$refInc) {
           self::$refInc = (int)mt_rand(0, pow(2, 24));
        }

        $this->hostname = gethostname();
        $this->timestamp = time();
        $this->pid = getmypid();
        $this->inc = self::$refInc++;

        $crc = crc32($this->hostname);
        $m0 = ($crc) & 255;
        $m1 = ($crc >> 8) & 255;
        $m2 = ($crc >> 16) & 255;

        $i0 = ($this->inc) & 255;
        $i1 = ($this->inc >> 8) & 255;
        $i2 = ($this->inc >> 16) & 255;

        $binId = pack('VC3vC3',
            $this->timestamp,
            $m2, $m1, $m0,
            $this->pid,
            $i2, $i1, $i0
        );

        return bin2hex($binId);
    }

    public function __toString()
    {
        return (string)$this->id;
    }

}