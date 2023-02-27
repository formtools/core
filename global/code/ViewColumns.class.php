<?php


namespace FormTools;

use PDO, Exception;


class ViewColumns
{
    /**
     * Returns all information about a View columns.
     *
     * @param integer $view_id
     */
    public static function getViewColumns($view_id)
    {
        $db = Core::$db;

        $db->query("
            SELECT *
            FROM   {PREFIX}view_columns
            WHERE  view_id = :view_id
            ORDER BY list_order
        ");
        $db->bind("view_id", $view_id);
        $db->execute();

        return $db->fetchAll();
    }


    // yuck! $info is a mess
    public static function updateViewColumns($view_id, $info)
    {
        $db = Core::$db;

        // delete the old view columns
        ViewColumns::deleteViewColumns($view_id);

        $sortable_id  = $info["submission_list_sortable_id"];

        // if there are no view columns, fine! We've deleted the old ones. Just return
        if ($info["{$sortable_id}_sortable__rows"] === "") {
            return;
        }

        $sortable_rows = explode(",", $info["{$sortable_id}_sortable__rows"]);

        $list_order = 1;
        $data = array();

        foreach ($sortable_rows as $row_id) {

            // if the user didn't select a field for this row, ignore it
            if (empty($info["field_id_{$row_id}"])) {
                continue;
            }

            $field_id    = $info["field_id_{$row_id}"];
            $is_sortable = (isset($info["is_sortable_{$row_id}"])) ? "yes" : "no";

            $custom_width = "";
            if (isset($info["auto_size_{$row_id}"])) {
                $auto_size = "yes";
            } else {
                $auto_size = "no";

                // validate the custom width field
                if (!isset($info["custom_width_{$row_id}"])) {
                    $auto_size = "yes";
                } else {
                    $custom_width = trim($info["custom_width_{$row_id}"]);
                    if (!is_numeric($custom_width)) {
                        $auto_size = "yes";
                        $custom_width = "";
                    }
                }
            }

            $truncate = $info["truncate_{$row_id}"];

            $data[] = array(
                "view_id" => $view_id,
                "field_id" => $field_id,
                "list_order" => $list_order,
                "is_sortable" => $is_sortable,
                "auto_size" => $auto_size,
                "custom_width" => $custom_width,
                "truncate" => $truncate
            );
            $list_order++;
        }

        try {
            $cols = array("view_id", "field_id", "list_order", "is_sortable", "auto_size", "custom_width", "truncate");
            $db->insertQueryMultiple("view_columns", $cols, $data);
        } catch (Exception $e) {
            Errors::queryError(__CLASS__, __FILE__, __LINE__, $e->getMessage());
            exit;
        }
    }


    public static function deleteViewColumns($view_id)
    {
        $db = Core::$db;

        $db->query("DELETE FROM {PREFIX}view_columns WHERE view_id = :view_id");
        $db->bind("view_id", $view_id);
        $db->execute();
    }
}
