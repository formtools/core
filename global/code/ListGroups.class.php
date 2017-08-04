<?php

/**
 * ListGroups.
 */

// -------------------------------------------------------------------------------------------------

namespace FormTools;


class ListGroups {

    /**
     * Inserts a new list group.
     */
    public static function addListGroup($group_type, $group_name, $next_order = "")
    {
        $db = Core::$db;

        if (empty($next_order)) {
            $next_order = self::getNextListOrder();
        }

        $db->query("
            INSERT INTO {PREFIX}list_groups (group_type, group_name, custom_data, list_order)
            VALUES (:group_type, :group_name, :custom_data, :list_order)
        ");
        $db->bindAll(array(
            "group_type" => $group_type,
            "group_name" => $group_name,
            "custom_data" => "",
            "list_order" => $next_order
        ));
        $db->execute();

        return array(
            "group_id"   => $db->getInsertId(),
            "group_name" => $group_name
        );
    }


    // list groups are ordered. This returns the next number, used when creating a new one.
    public static function getNextListOrder($group_type)
    {
        Core::$db->query("
            SELECT list_order
            FROM   {PREFIX}list_groups
            WHERE  group_type = :group_type
            ORDER BY list_order 
            DESC LIMIT 1
        ");
        Core::$db->bind("group_type", $group_type);
        Core::$db->execute();
        $result = Core::fetch();
        return (!isset($result["list_order"])) ? 1 : $result["list_order"] + 1;
    }

    public static function deleteListGroup($group_id)
    {
        Core::$db->query("DELETE FROM {PREFIX}list_groups WHERE group_id = :group_id");
        Core::$db->bind("group_id", $group_id);
        Core::$db->execute();
    }


    public static function deleteByGroupType($group_type)
    {
        Core::$db->query("DELETE FROM {PREFIX}list_groups WHERE group_type = :group_type");
        Core::$db->bind("group_type", $group_type);
        Core::$db->execute();
    }

    public static function getByGroupType($group_type)
    {
        Core::$db->query("
            SELECT *
            FROM   {PREFIX}list_groups
            WHERE  group_type = :group_type
            ORDER BY list_order
        ");
        Core::$db->bind("group_type", $group_type);
        Core::$db->execute();

        return Core::$db->fetchAll();
    }
}
