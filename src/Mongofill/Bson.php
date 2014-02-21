<?php

namespace Mongofill;


use Mongofill\Util;

class Bson
{
    const ETYPE_STRING   = 0x02;
    const ETYPE_ID       = 0x0c;
    const ETYPE_INT32    = 0x10;
    const ETYPE_INT64    = 0x12;
    const ETYPE_DOCUMENT = 0x03;
    const ETYPE_ARRAY    = 0x04;
    const ETYPE_DOUBLE   = 0x01;

    static public function encode(array $value)
    {
        return self::encDocument($value);
    }

    static public function decode($data)
    {
        $offset = 0;
        return self::decDocument($data, $offset);
    }

    static private function encElement($name, $value)
    {
        switch(true) {
            case $value instanceof \MongoId:
                $bin = hex2bin($value);
                $sig = self::ETYPE_ID;
                if (strlen($bin) != 12) throw new \RuntimeException('Invalid MongoId value');
                break;
            case $value instanceof \MongoInt64:
                $value = (int)(string)$value;
            case is_int($value):
                $i1 = $value & 0xffffffff;
                $i2 = ($value >> 32) & 0xffffffff;
                $bin = pack('V2', $i1, $i2);
                $sig  = self::ETYPE_INT64;
                break;
            case $value instanceof \MongoInt32:
                $bin = pack('V', (int)(string)$value);
                $sig  = self::ETYPE_INT32;
                break;
            case is_float($value):
                $bin = pack('d', $value);
                $sig  = self::ETYPE_DOUBLE;
                break;
            case is_string($value):
                $bin = pack('V', strlen($value)+1) . "$value\0";
                $sig  = self::ETYPE_STRING;
                break;
            case is_array($value):
                $bin = self::encDocument($value);
                $sig  = self::ETYPE_DOCUMENT;
                break;
            default:
                throw new \RuntimeException('Unsupported value type: ' . gettype($value));
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

    static private function decElement($data, &$offset)
    {
        $sig    = ord($data{$offset});
        $offset++;
        $name   = Util::parseCString($data, $offset);
        switch($sig) {
            case self::ETYPE_ID:
                $binId = Util::unpack('a24id', $data, $offset, 24)['id'];
                $value = bin2hex($binId);
                break;
            case self::ETYPE_STRING:
                $len = Util::unpack('Vlen', $data, $offset, 4)['len'];
                $value = substr($data, $offset, $len - 1); // subtract 1 fort nul-terminator
                $offset += $len;
                break;
            case self::ETYPE_ARRAY:
            case self::ETYPE_DOCUMENT:
                $value = self::decDocument($data, $offset);
                break;
            case self::ETYPE_INT32:
                $value = Util::unpack('Vint', $data, $offset, 4)['int'];
                break;
            case self::ETYPE_INT64:
                $vars = Util::unpack('V2i', $data, $offset, 8);
                $value = $vars['i1'] | ($vars['i2'] << 32);
                break;
            case self::ETYPE_DOUBLE:
                $value = Util::unpack('ddouble', $data, $offset, 8)['double'];
                break;
            default:
                throw new \RuntimeException('Invalid signature: 0x' . dechex($sig));
        }
        return [$name, $value];
    }

    static private function decDocument($data, &$offset)
    {
        $docLen = Util::unpack('Vlen', $data, $offset, 4)['len'] - 5; // subtract len. and null-terminator
        $document = [];
        $parsedLen = 0;
        while(0 !== ord($data{$offset})) {
            $elmLen = $offset;
            $elm = self::decElement($data, $offset);
            $parsedLen += ($offset - $elmLen);
            $document[$elm[0]] = $elm[1];
        }
        if ($docLen !== $parsedLen) {
            die(" $docLen - $elmLen ");
            throw new \RuntimeException("Document length doesn't match total size of parsed elements");
        }
        $offset++; // add one byte for document nul-terminator
        return $document;
    }
}