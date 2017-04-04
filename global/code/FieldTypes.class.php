<?php

/**
 * FieldTypes.
 */

// -------------------------------------------------------------------------------------------------

namespace FormTools;


class FieldTypes {

    /**
     * Return information about the field types in the database. To provide a little re-usability, the two
     * params let you choose whether or not to return the field types AND their settings or just
     * the field types, and whether or not you want to limit the results to specific field type IDs.
     *
     * @param array $return_settings
     * @param array $field_type_ids
     * @return array
     */
    public static function get($return_settings = false, $field_type_ids = array())
    {
        $db = Core::$db;

        if (!empty($field_type_ids)) {
            $field_type_id_str = implode(",", $field_type_ids);
            $db->query("
                SELECT *, g.list_order as group_list_order, ft.list_order as field_type_list_order
                FROM   {PREFIX}field_types ft, {PREFIX}list_groups g
                WHERE  g.group_type = :field_types AND
                       ft.group_id = g.group_id AND 
                       ft.field_type_id IN ($field_type_id_str)
                ORDER BY g.list_order, ft.list_order
            ");
        } else {
            $db->query("
                SELECT *, g.list_order as group_list_order, ft.list_order as field_type_list_order
                FROM   {PREFIX}field_types ft, {PREFIX}list_groups g
                WHERE  g.group_type = :field_types AND
                       ft.group_id = g.group_id
                ORDER BY g.list_order, ft.list_order
            ");
        }
        $db->bind(":field_types", "field_types");
        $db->execute();
        $results = $db->fetchAll();

        $field_types = array();
        foreach ($results as $row) {
            if ($return_settings) {
                $curr_field_type_id = $row["field_type_id"];
                $row["settings"] = ft_get_field_type_settings($curr_field_type_id, false);
            }
            $field_types[] = $row;
        }

        return $field_types;
    }


    public static function getFieldTypeByIdentifier($identifier)
    {
        $db = Core::$db;
        $db->query("
            SELECT *
            FROM   {PREFIX}field_types
            WHERE  field_type_identifier = :identifier
        ");
        $db->bind(":identifier", $identifier);
        $db->execute();
        $info = $db->fetch();

        if (!empty($info)) {
            $field_type_id = $info["field_type_id"];
            $info["settings"] = ft_get_field_type_settings($field_type_id);
        }

        return $info;
    }

}
