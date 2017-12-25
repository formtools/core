<?php


namespace FormTools;

use PDO;


class FieldSettings
{
    public static function getFieldSetting($field_id, $setting_id)
    {
        $db = Core::$db;

        $db->query("
            SELECT setting_value
            FROM   {PREFIX}field_settings
            WHERE  field_id = :field_id AND
                   setting_id = :setting_id
        ");
        $db->bindAll(array(
            "field_id" => $field_id,
            "setting_id" => $setting_id
        ));
        $db->execute();

        return $db->fetch(PDO::FETCH_COLUMN);
    }

    public static function addSetting($field_id, $setting_id, $setting_value)
    {
        $db = Core::$db;

        $db->query("
            INSERT INTO {PREFIX}field_settings (field_id, setting_id, setting_value)
            VALUES (:field_id, :setting_id, :setting_value)
        ");
        $db->bindAll(array(
            "field_id" => $field_id,
            "setting_id" => $setting_id,
            "setting_value" => $setting_value
        ));
        $db->execute();
    }


    public static function deleteSettings($field_id)
    {
        $db = Core::$db;

        $db->query("DELETE FROM {PREFIX}field_settings WHERE field_id = :field_id");
        $db->bind("field_id", $field_id);
        $db->execute();
    }

}
