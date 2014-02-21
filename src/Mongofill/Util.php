<?php

namespace Mongofill;


class Util
{

    public static function unpack($format, $data, &$offset, $length)
    {
        $vars = unpack($format, substr($data, $offset, $length));
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
}