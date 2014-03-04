<?php

namespace Mongofill {

use Mongofill\Util;

class Bson
{
    const ETYPE_STRING   = 0x02;
    const ETYPE_ID       = 0x07;
    const ETYPE_BOOL     = 0x08;
    const ETYPE_DATE     = 0x09;
    const ETYPE_INT32    = 0x10;
    const ETYPE_INT64    = 0x12;
    const ETYPE_DOCUMENT = 0x03;
    const ETYPE_ARRAY    = 0x04;
    const ETYPE_BINARY   = 0x05;
    const ETYPE_DOUBLE   = 0x01;
    const ETYPE_CODE     = 0x0d;
    const ETYPE_CODE_W_S = 0x0f;
    const ETYPE_UNDEF    = 0x06;
    const ETYPE_NULL     = 0x0a;
    const ETYPE_REGEX    = 0x0b;
    const ETYPE_SYMBOL   = 0x0e;
    const ETYPE_TIMESTAMP= 0x11;

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
            case is_bool($value):
                $bin = pack('C', $value);
                $sig  = self::ETYPE_BOOL;
                break;
            case is_null($value):
                $bin = '';
                $sig = self::ETYPE_NULL;
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
                $bin = pack('Va*', strlen($value)+1, "$value\0");
                $sig  = self::ETYPE_STRING;
                break;
            case $value instanceof \MongoCode:
                $scope = $value->getScope();
                $bin = pack('Va*', strlen($value)+1, "$value\0");
                if ($scope) {
                    $bin .= self::encDocument($scope);
                    $bin = pack('Va*', strlen($bin)+1, $bin);
                    $sig = self::ETYPE_CODE_W_S;
                } else {
                    $sig = self::ETYPE_CODE;
                }
                break;
            case $value instanceof \MongoRegex:
                $bin = $value->regex."\0".$value->flags."\0";
                $sig  = self::ETYPE_REGEX;
                break;
            case $value instanceof \MongoDate:
                $bin = pack('V2', $value->sec, $value->usec);
                $sig = self::ETYPE_DATE;
                break;
            case $value instanceof \MongoTimestamp:
                $bin = pack('V2', $value->inc, $value->sec);
                $sig = self::ETYPE_TIMESTAMP;
                break;
            case is_array($value):
                $bin = self::encDocument($value);
                $sig  = self::ETYPE_DOCUMENT;
                break;
            case $value instanceof \MongoBinData:
                if ($value->type != 2) {
                    $bin = pack('C', $value->type).$value->bin;
                } else {
                    $bin = pack('CV', $value->type, strlen($value->bin)).$value->bin;
                }
                $bin = pack("V", strlen($value->bin) + (($value->type == 2) ? 4 : 0)).$bin;

                $sig  = self::ETYPE_BINARY;
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
        return pack('Va*', strlen($data)+5, "$data\0");
    }

    static private function decElement($data, &$offset)
    {
        $sig    = ord($data{$offset});
        $offset++;
        $name   = Util::parseCString($data, $offset);
        switch($sig) {
            case self::ETYPE_ID:
                $binId = Util::unpack('a12id', $data, $offset, 12)['id'];
                $value = new \MongoId(bin2hex($binId));
                break;
            case self::ETYPE_STRING:
            case self::ETYPE_SYMBOL:
                $len = Util::unpack('Vlen', $data, $offset, 4)['len'];
                $value = substr($data, $offset, $len - 1); // subtract 1 for nul-terminator
                $offset += $len;
                break;
            case self::ETYPE_ARRAY:
            case self::ETYPE_DOCUMENT:
                $value = self::decDocument($data, $offset);
                break;
            case self::ETYPE_INT32:
                $value = Util::unpack('Vint', $data, $offset, 4)['int'];
                break;
            case self::ETYPE_BOOL:
                $value = Util::unpack('C', $data, $offset, 1);
                if($value){
                    $value = TRUE;
                } else {
                    $value = FALSE;
                }
                break;
            case self::ETYPE_UNDEF:
            case self::ETYPE_NULL:
                $value = null;
                break;
            case self::ETYPE_INT64:
                $vars = Util::unpack('V2i', $data, $offset, 8);
                $value = $vars['i1'] | ($vars['i2'] << 32);
                break;
            case self::ETYPE_DOUBLE:
                $value = Util::unpack('ddouble', $data, $offset, 8)['double'];
                break;
            case self::ETYPE_CODE_W_S:
                $offset += 4; // skip whole element size int
            case self::ETYPE_CODE:
                $scope = [];
                $len = Util::unpack('Vlen', $data, $offset, 4)['len'];
                $code = substr($data, $offset, $len - 1); // subtract 1 for nul-terminator
                $offset += $len;
                if ($sig === self::ETYPE_CODE_W_S) $scope = self::decDocument($data, $offset);
                $value = new \MongoCode($code, $scope);
                break;
            case self::ETYPE_REGEX:
                $regex = Util::parseCString($data, $offset);
                $flags = Util::parseCString($data, $offset);
                $value = new \MongoRegex('/'.$regex.'/'.$flags);
                break;
            case self::ETYPE_DATE:
                $vars = Util::unpack('V2i', $data, $offset, 8);
                $value = new \MongoDate($vars['i1'], $vars['i2']);
                break;
            case self::ETYPE_TIMESTAMP:
                $vars = Util::unpack('V2i', $data, $offset, 8);
                $value = new \MongoTimestamp($vars['i2'], $vars['i1']);
                break;
            case self::ETYPE_BINARY:
                $vars = Util::unpack('Vlen/Csubtype', $data, $offset, 5);
                $len = $vars['len'];
                $subtype = $vars['subtype'];
                if ($subtype == 2) {
                    // binary subtype 2 special case
                    $len2 = Util::unpack('Vlen', $data, $offset, 4)['len'];
                    if ($len2 == $len - 4) {
                        $len = $len2;
                    } else {
                        // something is not right, restore offset
                        $offset -= 4;
                    }
                }
                if ($len < 0) {
                    throw new \RuntimeException(sprintf("invalid binary length for key \"%s\": %d", $name, $len), 22);
                }
                $value = new \MongoBinData(substr($data, $offset, $len), $subtype);
                $offset += $len;
                break;
            default:
                throw new \RuntimeException('Invalid signature: 0x' . dechex($sig));
        }
        return [$name, $value];
    }

    static public function decDocument($data, &$offset)
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
            throw new \RuntimeException(
                "Document length doesn't match total size of parsed elements ($docLen:$parsedLen)");
        }
        $offset++; // add one byte for document nul-terminator
        return $document;
    }
}

}
