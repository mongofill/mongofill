<?php

namespace Mongofill;

class Util
{
    public static function unpack($format, $data, &$offset, $length)
    {
        $bin = substr($data, $offset, $length);
        $vars = unpack($format, $bin);
        $offset += $length;

        return $vars;
    }

    public static function parseCString($data, &$offset)
    {
        $nulPos = strpos($data, "\0", $offset);
        if (false === $nulPos) {
            throw new \RuntimeException("Can't parse cstring, no nul-character found.");
        }
        $str = substr($data, $offset, $nulPos - $offset);
        $offset = $nulPos + 1;

        return $str;
    }

    public static function encodeInt64($value)
    {
        $i1 = $value & 0xffffffff;
        $i2 = ($value >> 32) & 0xffffffff;

        return pack('V2', $i1, $i2);
    }

    public static function decodeInt64($i1, $i2 = null)
    {
        if (null !== $i2 && is_string($i1)) {
            $vars = Util::unpack('V2i', $i1, $offset, 8);
            extract($vars, EXTR_OVERWRITE);
        }

        return $i1 | ($i2 << 32);
    }
}
