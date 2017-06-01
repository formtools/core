<?php


namespace FormTools;


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


    public static function updateViewColumnsSettings($view_id, $info)
    {
        $db = Core::$db;

        $sortable_id  = $info["submission_list_sortable_id"];
        $sortable_rows = explode(",", $info["{$sortable_id}_sortable__rows"]);

        $db->query("DELETE FROM {PREFIX}view_columns WHERE view_id = :view_id");
        $db->bind("view_id", $view_id);
        $db->execute();

        $insert_statements = array();
        $list_order = 1;
        foreach ($sortable_rows as $row_id) {

            // if the user didn't select a field for this row, ignore it
            if (empty($info["field_id_{$row_id}"])) {
                continue;
            }

            $field_id     = $info["field_id_{$row_id}"];
            $is_sortable  = (isset($info["is_sortable_{$row_id}"])) ? "yes" : "no";

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

            $insert_statements[] = "($view_id, $field_id, $list_order, '$is_sortable', '$auto_size', '$custom_width', '$truncate')";
            $list_order++;
        }

        if (!empty($insert_statements)) {
            $insert_statement_str = implode(",\n", $insert_statements);
            $db->query("
                INSERT INTO {PREFIX}view_columns (view_id, field_id, list_order, is_sortable, auto_size, custom_width, truncate)
                VALUES $insert_statement_str
            ");
            $db->execute();
        }
    }

}
