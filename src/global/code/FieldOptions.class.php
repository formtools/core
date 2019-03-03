<?php

/**
 * Handles any interaction with the field_options table.
 */

namespace FormTools;

class FieldOptions
{
    public static function addFieldOption($list_id, $list_group_id, $order, $value, $name, $is_new_sort_group)
    {
        $db = Core::$db;

        $db->query("
            INSERT INTO {PREFIX}field_options (list_id, list_group_id, option_order,
                option_value, option_name, is_new_sort_group)
            VALUES (:list_id, :list_group_id, :option_order, :option_value, :option_name, :is_new_sort_group)
        ");
        $db->bindAll(array(
            "list_id" => $list_id,
            "list_group_id" => $list_group_id,
            "option_order" => $order,
            "option_value" => $value,
            "option_name" => $name,
            "is_new_sort_group" => $is_new_sort_group
        ));
        $db->execute();
    }

    public static function deleteByListId($list_id)
    {
        Core::$db->query("
           DELETE FROM {PREFIX}field_options
           WHERE list_id = :list_id
        ");
        Core::$db->bind("list_id", $list_id);
        Core::$db->execute();
    }

}
