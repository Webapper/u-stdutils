<?php
/**
 * Copyright (c) 2016. by Csaba Dobai (aka Assarte), all rights reserved due to European Laws of Intellectual Properties OR licence attached
 */

/**
 * Created by PhpStorm.
 * User: assarte
 * Date: 2016.05.21.
 * Time: 3:20
 */

namespace U\Utils;

class Arrays
{
    /**
     * Returns the value of given nth item of given array
     * @param array $array
     * @param int $index
     * @return mixed
     */
    public static function getNth(array $array, $index) {
        if ($index >= count($array)) throw new \OutOfBoundsException(sprintf('Requested item #%s points over the array given with length: %s', $index, count($array)));
        $result = $array[array_keys($array)[$index]];
        return $result;
    }

    /**
     * Returns the key of given nth item of given array
     * @param array $array
     * @param int $index
     * @return mixed
     */
    public static function getNthKey(array $array, $index) {
        if ($index >= count($array)) throw new \OutOfBoundsException(sprintf('Requested item #%s points over the array given with length: %s', $index, count($array)));
        $result = array_keys($array)[$index];
        return $result;
    }
}