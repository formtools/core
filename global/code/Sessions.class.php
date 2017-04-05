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
    public static function get($key = "", $group = null) {
        if (empty($key)) {
            return $_SESSION;
        }
        if ($group == null) {
            return $_SESSION["ft"][$key];
        } else {
            return $_SESSION["ft"][$group][$key];
        }
    }

    /**
     * Stores data in sessions. The third parameter is for grouping settings, e.g. settings for a specific module
     * or area (menus, general settings etc).
     * @param $key
     * @param $value
     * @param $group
     */
    public static function set($key, $value, $group = null) {
        if (!isset($_SESSION["ft"])) {
            $_SESSION["ft"] = array();
        }
        if ($group == null) {
            $_SESSION["ft"][$key] = $value;
        } else {
            if (!isset($_SESSION["ft"][$group])) {
                $_SESSION["ft"][$group] = array();
            }
            $_SESSION["ft"][$group][$key] = $value;
        }
    }

    public static function exists($key, $group = null) {
        if ($group == null) {
            return isset($_SESSION["ft"][$key]);
        } else {
            return isset($_SESSION["ft"][$group][$key]);
        }
    }

    public static function clear($key) {
        unset($_SESSION["ft"][$key]);
    }
}
