<?php
/**
 * Copyright (c) 2016. by Csaba Dobai (aka Assarte), all rights reserved due to European Laws of Intellectual Properties OR licence attached
 */

/**
 * Created by PhpStorm.
 * User: assarte
 * Date: 2016.05.21.
 * Time: 1:18
 */

namespace Webapper\U\Utils;

class Strings
{
    /**
     * Returns the string in escaped hexa-decimal form, eg. "Hello\r\n" will be '\0x48\0x65\0x6c\0x6c\0x6f\0x0d\0x0a'
     * @param string $blob
     * @return string
     */
    public static function hexdump($blob) {
        $result = '';
        for ($i = 0; $i < strlen($blob); $i++) {
            $result .= str_pad(dechex(ord($blob{$i})), 5, '\\0x0', STR_PAD_LEFT);
        }
        return $result;
    }

    /**
     * Inserts given subject into the string at passed position and returns the string
     * @param string $into
     * @param string $subject
     * @param int $where
     * @return string
     */
    public static function insertInto($into, $subject, $where) {
        return substr_replace($into, $subject, $where, 0);
    }
}