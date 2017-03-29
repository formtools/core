<?php

/**
 * Wrapper for any PHP sessions storage. Pre 3.0.0 session usage was littered throughout the code. Code needed to know
 * about the structure of the session data and made everything coupled with everything else. This class reduces
 * all that to a simple get/set interface where the internals are hidden away.
 *
 * This file is independent of the TYPE of session (PHP/Database). If the user selected database sessions, the
 * DatabaseSession class is instantiated on page load, which automatically routes the $_SESSION interaction here to
 * store the values in the database.
 */


namespace FormTools;


class Sessions
{
    public static function get($key) {
        return $_SESSION[$key];
    }

    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    public static function exists($key) {
        return isset($_SESSION[$key]);
    }

    public static function clear($key) {
        unset($_SESSION[$key]);
    }
}
