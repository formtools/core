<?php

/**
 * Views.
 */

// -------------------------------------------------------------------------------------------------

namespace FormTools;


class Views {

    /**
     * Returns all fields in a View.
     *
     * @param integer $view_id the unique View ID
     * @return array $info an array of hashes containing the various view field values.
     */
    public static function getViewFields($view_id, $custom_params = array())
    {
        $db = Core::$db;

        $params = array(
            "include_field_settings" => (isset($custom_params["include_field_settings"])) ? $custom_params["include_field_settings"] : false
        );

        $db->query("
            SELECT vf.field_id
            FROM   {PREFIX}list_groups lg, {PREFIX}view_fields vf
            WHERE  lg.group_type = :group_type AND
                   lg.group_id = vf.group_id
            ORDER BY lg.list_order ASC, vf.list_order ASC
        ");
        $db->bind("group_type", "view_fields_$view_id");
        $db->execute();

        $fields_info = array();
        foreach ($db->fetchAll() as $field_info) {
            $field_id = $field_info["field_id"];
            $fields_info[] = Views::getViewField($view_id, $field_id, $params);
        }

        return $fields_info;
    }

    /**
     * Returns the View field values from the view_fields table, as well as a few values
     * from the corresponding form_fields table.
     *
     * @param integer $view_id the unique View ID
     * @param integer $field_id the unique field ID
     * @return array a hash containing the various view field values
     */
    public static function getViewField($view_id, $field_id, $custom_params = array())
    {
        $db = Core::$db;

        $params = array(
            "include_field_settings" => (isset($custom_params["include_field_settings"])) ? $custom_params["include_field_settings"] : false
        );

        $db->query("
            SELECT vf.*, ft.field_title, ft.col_name, ft.field_type_id, ft.field_name
            FROM   {PREFIX}view_fields vf, {PREFIX}form_fields ft
            WHERE  view_id = $view_id AND
                   vf.field_id = ft.field_id AND
                   vf.field_id = $field_id
        ");
        $db->bindAll(array(
            "view_id" => $view_id,
            "field_id" => $field_id
        ));
        $db->execute();

        $result = $db->fetch();
        if ($params["include_field_settings"]) {
            $result["field_settings"] = Fields::getFormFieldSettings($field_id);
        }

        return $result;
    }



}
