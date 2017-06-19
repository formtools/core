<?php


namespace FormTools;

use PDO, PDOException;


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

        $sortable_id  = $info["submission_list_sortable_id"];
        $sortable_rows = explode(",", $info["{$sortable_id}_sortable__rows"]);

        ViewColumns::deleteViewColumns($view_id);

        $list_order = 1;
        $placeholders = array();
        $data = array();
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

            $data[] = $view_id;
            $data[] = $field_id;
            $data[] = $list_order;
            $data[] = $is_sortable;
            $data[] = $auto_size;
            $data[] = $custom_width;
            $data[] = $truncate;
            $placeholders[] = "(?, ?, ?, ?, ?, ?, ?)";
            $list_order++;
        }

        try {
            $db->beginTransaction();
            $placeholder_str = implode(", ", $placeholders);
            $db->query("
                INSERT INTO {PREFIX}view_columns (view_id, field_id, list_order, is_sortable, auto_size, custom_width, truncate)
                VALUES $placeholder_str
            ");
            $db->execute($data);
            $db->processTransaction();
        } catch (PDOException $e) {
            $db->rollbackTransaction();
            Errors::queryError(__CLASS__, __FILE__, __LINE__, $e->getMessage());
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
