<?php

/**
 * Fields.
 */

// -------------------------------------------------------------------------------------------------

namespace FormTools;


class Fields {

    /**
     * Retrieves all custom settings for an individual form field from the field_settings table.
     *
     * @param integer $field_id the unique field ID
     * @return array an array of hashes
     */
    public static function getFormFieldSettings($field_id, $evaluate_dynamic_fields = false)
    {
        if ($evaluate_dynamic_fields) {
            $query = mysql_query("
      SELECT *
      FROM   {$g_table_prefix}field_settings fs, {$g_table_prefix}field_type_settings fts
      WHERE  fs.setting_id = fts.setting_id AND
             field_id = $field_id
    ");
        }
        else
        {
            $query = mysql_query("
      SELECT *
      FROM   {$g_table_prefix}field_settings
      WHERE  field_id = $field_id
    ");
        }

        $settings = array();
        while ($row = mysql_fetch_assoc($query))
        {
            if ($evaluate_dynamic_fields && $row["default_value_type"] == "dynamic")
            {
                $settings[$row["setting_id"]] = "";
                $parts = explode(",", $row["setting_value"]);
                if (count($parts) == 2)
                {
                    $settings[$row["setting_id"]] = Settings::get($parts[0], $parts[1]);
                }
            }
            else
            {
                $settings[$row["setting_id"]] = $row["setting_value"];
            }
        }

        extract(Hooks::processHookCalls("end", compact("field_id", "settings"), array("settings")), EXTR_OVERWRITE);

        return $settings;
    }


}
