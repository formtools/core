<?php

/**
 * Handles any interaction with the field_options table.
 */

namespace FormTools;

class FieldOptions
{
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
