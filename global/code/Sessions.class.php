<?php

/**
 * Wrapper for any PHP sessions storage. Pre 3.0.0 session usage was littered throughout the code. Code needed to know
 * about the structure of the session data and made everything coupled with everything else. This class reduces
 * all that to a simple get/set interface.
 *
 * This file is independent of the TYPE of session (PHP/Database). If the user selected database sessions, the
 * DatabaseSession class is instantiated on page load, which automatically routes the $_SESSION interaction here to
 * store the values in the database.
 */


namespace FormTools;


class Sessions
{
    public static function get($key = "") {
        if (empty($key)) {
            return $_SESSION["ft"];
        }

        $parts = mb_split("\.", $key);
        $ref = &$_SESSION["ft"];
        foreach ($parts as $section) {
            $ref = &$ref[$section];
        }

        return $ref;
    }


    /**
     * Stores data in sessions.
     * @param $key string dot-delimited e.g. settings.num_per_page
     */
    public static function set($key, $value) {
        $parts = mb_split("\.", $key);
        if (!isset($_SESSION["ft"])) {
            $_SESSION["ft"] = array();
        }
        $ref = &$_SESSION["ft"];
        foreach ($parts as $section) {
            $ref = &$ref[$section];
        }
        $ref = $value;
    }


    public static function exists($key) {
        if (!isset($_SESSION["ft"])) {
            return false;
        }

        $parts = mb_split("\.", $key);
        $exists = false;
        $temp = &$_SESSION["ft"];
        foreach ($parts as $section) {
            if (isset($temp[$section])) {
                $exists = true;
            }
            $temp = &$temp[$section];
        }

        return $exists;
    }

    public static function createIfNotExists($key, $value) {
        if (!self::exists($key)) {
            self::set($key, $value);
        }
    }

    /**
     * Clears a top-level key.
     * @param $key
     */
    public static function clear($key) {
        unset($_SESSION["ft"][$key]);
    }


    public static function clearAll() {
        unset($_SESSION["ft"]);
        $_SESSION["ft"] = array();
    }
}
