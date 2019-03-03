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
    public static function get($key = "")
    {
        if (empty($key)) {
            return $_SESSION["ft"];
        }

        $parts = explode(".", $key);
        $ref = &$_SESSION["ft"];
        foreach ($parts as $section) {
            $ref = &$ref[$section];
        }

        return $ref;
    }

    /**
     * Simple helper method to return the value of something in session, or if it's not defined,
     * @param $key
     * @param $defaultValue
     */
    public static function getWithFallback($key, $defaultValue)
    {
        if (Sessions::exists($key)) {
            return Sessions::get($key);
        } else {
            return $defaultValue;
        }
    }

    /**
     * Stores data in sessions.
     * @param $key string dot-delimited e.g. settings.num_per_page
     */
    public static function set($key, $value)
    {
        $parts = explode(".", $key);
        if (!isset($_SESSION["ft"])) {
            $_SESSION["ft"] = array();
        }
        $ref = &$_SESSION["ft"];
        foreach ($parts as $section) {
            $ref = &$ref[$section];
        }
        $ref = $value;
    }


    public static function setIfNotExists($key, $value)
    {
        if (!self::exists($key)) {
            self::set($key, $value);
        }
    }


    public static function appendArrayItem($key, $value) {
        $array = Sessions::get($key);
        $array[] = $value;
        self::set($key, $array);
    }


    public static function removeArrayItem($key, $value) {
        $items = self::get($key);
        if (!in_array($value, $items)) {
            return;
        }
        array_splice($items, array_search($value, self::get($key)), 1);
        self::set($key, $items);
    }


    public static function exists($key)
    {
        if (!isset($_SESSION["ft"])) {
            return false;
        }

        // TODO test!
        $parts = explode(".", $key);
        $exists = false;
        $temp = &$_SESSION["ft"];
        foreach ($parts as $section) {
            $exists = false;
            if (isset($temp[$section])) {
                $exists = true;
            }
            $temp = &$temp[$section];
        }

        return $exists;
    }


    /**
     * Returns true is the key doesn't exist or if it's empty().
     */
    public static function isEmpty($key)
    {
        if (!self::exists($key)) {
            return true;
        }
        $value = self::get($key);
        return empty($value);
    }


    /**
     * Helper to find out if a key exists and is non-empty.
     */
    public static function isNonEmpty($key) {
        if (!isset($_SESSION["ft"])) {
            return false;
        }

        // TODO test!!!
        $parts = explode(".", $key);
        $exists = false;
        $temp = &$_SESSION["ft"];
        foreach ($parts as $section) {
            $exists = false;
            if (isset($temp[$section]) && !empty($temp[$section])) {
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
     * Clears a key or keys.
     * @param $key
     */
    public static function clear($key) {
        if (is_string($key)) {
            unset($_SESSION["ft"][$key]);
        }
    }

    public static function clearAll() {
        unset($_SESSION["ft"]);
        $_SESSION["ft"] = array();
    }
}
