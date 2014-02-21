<?php

class MongoId
{
    private $id;

    private $timestamp;
    private $hostname;
    private $pid;
    private $inc;

    static private $refInc = null;

    /**
     * @param null|string $id
     * @throws MongoException
     */
    function __construct($id = null)
    {
        $this->hostname = self::getHostname();
        if (null === $id) {
            $id = $this->generateId();
        } else if (self::isValid($id)) {
            $this->disassembleId($id);
        } else {
            throw new MongoException('Invalid object ID', 19);
        }
        $this->id = $id;
        $this->{'$id'} = $id;
    }

    private function generateId()
    {
        if (null === self::$refInc) {
           self::$refInc = (int)mt_rand(0, pow(2, 24));
        }
        $this->timestamp = time();
        $this->pid = getmypid();
        $this->inc = self::$refInc++;
        return $this->assembleId();
    }

    private function assembleId()
    {
        $crc = crc32($this->hostname);
        $m1 = ($crc) & 255;
        $m2 = ($crc >> 8) & 255;
        $m3 = ($crc >> 16) & 255;
        $i1 = ($this->inc) & 255;
        $i2 = ($this->inc >> 8) & 255;
        $i3 = ($this->inc >> 16) & 255;
        $binId = pack('NC3vC3',
            $this->timestamp,
            $m3, $m2, $m1,
            $this->pid,
            $i3, $i2, $i1
        );
        return bin2hex($binId);
    }

    private function disassembleId($id)
    {
        $vars = unpack('Nts/C3m/vpid/C3i', hex2bin($id));
        $this->timestamp = $vars['ts'];
        $this->pid = $vars['pid'];
        $this->inc = $vars['i3'] | ($vars['i2'] << 8) | ($vars['i1'] << 16);
    }

    static public function getHostname()
    {
        return gethostname();
    }

    public function getTimestamp()
    {
        return $this->timestamp;
    }

    public function getPid()
    {
        return $this->pid;
    }

    public function getInc()
    {
        return $this->inc;
    }

    static public function isValid($id)
    {
        return preg_match('/[0-9a-fA-F]{24}/', $id);
    }

    public static function __set_state($props)
    {
        $id = new self('000000000000000000000000');
        foreach($props as $propName => $value) {
            $id->{$propName} = $value;
        }
        $id->id = $id->assembleId();
        return $id;
    }

    public function __toString()
    {
        return (string)$this->id;
    }
}