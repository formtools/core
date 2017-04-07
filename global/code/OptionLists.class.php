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
     * @param $page_num number the current page number, or "all" for all results.
     * @return array ["results"] an array of option group information
     *               ["num_results"] the total number of option groups in the database.
     */
    public static function getList($default_options = array())
    {
        $db = Core::$db;

        $options = array_merge(array(
            "page" => "all", // or a number for a particular page num...
            "order" => "option_list_name-ASC",
            "per_page" => 10
        ), $default_options);

        if ($options["page"] == "all") {
            $limit_clause = "";
        } else {
            $first_item = ($options["per_page"] - 1) * $options["per_page"];
            $limit_clause = "LIMIT $first_item, {$options["per_page"]}";
        }

        $order_clause = self::getOptionListOrderClause($options["page"]);

        $db->query("
            SELECT *
            FROM   {PREFIX}option_lists
            $order_clause
            $limit_clause
        ");
        $db->execute();
        $results = $db->fetchAll();

        $db->query("SELECT count(*) as c FROM {PREFIX}option_lists");
        $db->execute();
        $count_hash = $db->fetch();

        $option_lists = array();
        foreach ($results as $row) {
            $option_lists[] = $row;
        }

        $return_hash = array(
            "results" => $option_lists,
            "num_results" => $count_hash["c"]
        );

        extract(Hooks::processHookCalls("end", compact("return_hash"), array("return_hash")), EXTR_OVERWRITE);

        return $return_hash;
    }


    /**
     * Returns the total number of option lists in the database.
     *
     * @return integer
     */
    public static function getNumOptionLists()
    {
        $db = Core::$db;
        $db->query("SELECT count(*) as c FROM {PREFIX}option_lists");
        $db->execute();
        $result = $db->fetch();
        return $result["c"];
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
