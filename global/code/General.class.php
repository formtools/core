<?php

/**
 * General methods. Added in 2.3.0 - will replace the older genera.php file.
 *
 * @copyright Benjamin Keen 2017
 * @author Benjamin Keen <ben.keen@gmail.com>
 * @package 2-3-x
 * @subpackage Database
 */


// -------------------------------------------------------------------------------------------------

namespace FormTools;

use PDO;


class General
{
    /**
     * Helper function that's used on Step 2 to confirm that the Core Field Types module folder exists.
     *
     * @param string $module_folder
     */
    public static function checkModuleAvailable($module_folder)
    {
        return is_dir(realpath(__DIR__ . "/../../modules/$module_folder"));
    }


    /**
     * Gets a list of known Form Tools tables in a database.
     * @return array
     */
    public static function getExistingTables(Database $db, array $all_tables, $table_prefix)
    {
        $db->query("SHOW TABLES");

        $prefixed_tables = array();
        foreach ($all_tables as $table_name) {
            $prefixed_tables[] = $table_prefix . $table_name;
        }

        $existing_tables = array();
        foreach ($db->fetchAll(PDO::FETCH_NUM) as $row) {
            $curr_table = $row[0];
            if (in_array($curr_table, $prefixed_tables)) {
                $existing_tables[] = $curr_table;
            }
        }

        return $existing_tables;
    }


    /**
     * Helper method to convert an array to rows of HTML in bullet points.
     * @return array
     */
    public static function getErrorListHTML(array $errors) {
        array_walk($errors, create_function('&$el','$el = "&bull;&nbsp; " . $el;'));
        return join("<br />", $errors);
    }

    /**
     * Returns a date in Y-m-d H:i:s format, generally used for inserting into a MySQL
     * datetime field.
     *
     * @param string $timestamp an optional Unix timestamp to convert to a datetime
     * @return string the current datetime in string format
     * */
    public static function getCurrentDatetime($timestamp = "")
    {
        if (!empty($timestamp)) {
            $datetime = date("Y-m-d H:i:s", $timestamp);
        } else {
            $datetime = date("Y-m-d H:i:s");
        }
        return $datetime;
    }


    /**
     * Checks to see if a database table exists. Handy for modules to check to see if they've been installed
     * or not.
     *
     * @return boolean
     */
    public static function checkDbTableExists($table)
    {
        $db = Core::$db;
        $db_name = Core::getDbName();

        $found = false;
        $db->query("SHOW TABLES FROM :db_name");
        $db->bind(":db_name", $db_name);
        $db->execute();
        foreach ($db->fetchAll() as $row) {
            if ($row[0] == $table) {
                $found = true;
                break;
            }
        }
        return $found;
    }


    /**
     * Helper function to convert a MySQL datetime to a unix timestamp.
     *
     * @param string $datetime
     * @return string
     */
    public static function convertDatetimeToTimestamp($datetime)
    {
        list($date, $time) = explode(" ", $datetime);
        list($year, $month, $day) = explode("-", $date);
        list($hours, $minutes, $seconds) = explode(":", $time);

        return mktime($hours, $minutes, $seconds, $month, $day, $year);
    }


    /**
     * This is used for major errors, especially when no database connection can be made. All it does is output
     * the error string with no other dependencies - not even language strings. This is always output in English.
     *
     * @param string $error
     */
    public static function displaySeriousError($error) {
        echo <<< END
<!DOCTYPE>
<html>
<head>
  <title>Error</title>
  <style type="text/css">
  h1 {
    margin: 0px 0px 16px 0px;
  }
  body {
    background-color: #f9f9f9;
    text-align: center;
    font-family: verdana;
    font-size: 11pt;
    line-height: 22px;
  }
  div {
    -webkit-border-radius: 20px;
    -moz-border-radius: 20px;
    border-radius: 20px;
    border: 1px solid #666666;
    padding: 40px;
    background-color: white;
    width: 600px;
    text-align: left;
    margin: 30px auto;
    word-wrap: break-word;
  }
  </style>
</head>
<body>
<div class="error">
  <h1>Uh-oh.</h1>
  {$error}
</div>
</body>
</html>
END;
    }
}
