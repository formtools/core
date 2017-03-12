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

}
