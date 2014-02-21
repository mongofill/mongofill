<?php

namespace Mongofill;


class Bson
{
    static public function encode(array $value)
    {
        return self::encDocument($value);
    }

    static private function encElement($name, $value)
    {
        switch(true) {
            case $value instanceof \MongoId:
                $bin = hex2bin($value);
                $sig = 0x0c;
                if (strlen($bin) != 12) throw new \RuntimeException('Invalid MongoId value');
                break;
            case $value instanceof \MongoInt64:
                $value = (int)(string)$value;
            case is_int($value):
                $wl = $value & 0x0000ffff;
                $wh = ($value >> 32) & 0x0000ffff;
                $bin = pack('V2', $wh, $wl);
                $sig  = 0x12;
                break;
            case $value instanceof \MongoInt32:
                $bin = pack('V', (int)(string)$value);
                $sig  = 0x10;
                break;
            case is_float($value):
                $bin = pack('d', $value);
                $sig  = 0x01;
                break;
            case is_string($value):
                $bin = pack('V', strlen($value)+1) . "$value\0";
                $sig  = 0x02;
                break;
            case is_array($value):
                $bin = self::encDocument($value);
                $sig  = 0x03;
                break;
            default:
                throw new \RuntimeException('Unsupported value type: '.gettype($value));
        }

        return chr($sig) . "$name\0" . $bin;
    }

    static private function encDocument(array $values)
    {
        $data = '';
        foreach ($values as $key => $value) {
            $data .= self::encElement($key, $value);
        }
        return pack('V', strlen($data)+5) . $data . chr(0);
    }
}