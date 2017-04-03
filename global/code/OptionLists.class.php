<?php

/**
 * ListGroups.
 */

// -------------------------------------------------------------------------------------------------

namespace FormTools;


class OptionLists {

    /**
     * Returns all list options in the database.
     *
     * @param $page_num current page number, or "all" for all results.
     * @return array ["results"] an array of option group information
     *               ["num_results"] the total number of option groups in the database.
     */
    public static function getList($page_num = 1, $order = "option_list_name-ASC")
    {
        $db = Core::$db;

        if ($page_num == "all") {
            $limit_clause = "";
        } else {
            $num_option_lists_per_page = isset($_SESSION["ft"]["settings"]["num_option_lists_per_page"]) ?
            $_SESSION["ft"]["settings"]["num_option_lists_per_page"] : 10;

            // determine the LIMIT clause
            if (empty($page_num)) {
                $page_num = 1;
            }
            $first_item = ($page_num - 1) * $num_option_lists_per_page;
            $limit_clause = "LIMIT $first_item, $num_option_lists_per_page";
        }

        $order_clause = self::getOptionListOrderClause($order);

        $db->query("
            SELECT *
            FROM   {PREFIX}option_lists
            $order_clause
            $limit_clause
        ");

        $count_result = mysql_query("
            SELECT count(*) as c
            FROM 	 {PREFIX}option_lists
        ");
        $count_hash = mysql_fetch_assoc($count_result);

        $option_lists = array();
        while ($row = mysql_fetch_assoc($result))
            $option_lists[] = $row;

        $return_hash = array();
        $return_hash["results"] = $option_lists;
        $return_hash["num_results"]  = $count_hash["c"];

        extract(Hooks::processHookCalls("end", compact("return_hash"), array("return_hash")), EXTR_OVERWRITE);

        return $return_hash;
    }


    // --------------------------------------------------------------------------------------------

    /**
     * Used in a couple of places, so I moved it here.
     *
     * @param string $order
     */
    private static function getOptionListOrderClause($order)
    {
        switch ($order)
        {
            case "list_id-DESC":
                $order_clause = "list_id DESC";
                break;
            case "list_id-ASC":
                $order_clause = "list_id ASC";
                break;
            case "option_list_name-ASC":
                $order_clause = "option_list_name ASC";
                break;
            case "option_list_name-DESC":
                $order_clause = "option_list_name DESC";
                break;

            default:
                $order_clause = "option_list_name ASC";
                break;
        }

        return "ORDER BY $order_clause";
    }

}
