<?php

/**
 * FieldSizes.
 */

// -------------------------------------------------------------------------------------------------

namespace FormTools;

use PDO;

class FieldSizes {

    private static $fieldSizes = array(
        "1char"      => array("lang_key" => "phrase_1char", "sql" => "VARCHAR(1)"),
        "2chars"     => array("lang_key" => "phrase_2chars", "sql" => "VARCHAR(2)"),
        "tiny"       => array("lang_key" => "phrase_size_tiny", "sql" => "VARCHAR(5)"),
        "small"      => array("lang_key" => "phrase_size_small", "sql" => "VARCHAR(20)"),
        "medium"     => array("lang_key" => "phrase_size_medium", "sql" => "VARCHAR(255)"),
        "large"      => array("lang_key" => "phrase_size_large", "sql" => "TEXT"),
        "very_large" => array("lang_key" => "phrase_size_very_large", "sql" => "MEDIUMTEXT"),
    );


    public static function get() {
        return self::$fieldSizes;
    }

    /**
     * Returns an array of available database column sizes for a field type.
     *
     * @param integer $field_type_id
     */
    public static function getFieldTypeSizes($field_type_id)
    {
        $db = Core::$db;
        $db->query("
            SELECT compatible_field_sizes
            FROM   {PREFIX}field_types
            WHERE  field_type_id = :field_type_id
        ");
        $db->bind("field_type_id", $field_type_id);
        $db->execute();
        $result = $db->fetch(PDO::FETCH_COLUMN);

        return explode(",", $result);
    }


    /**
     * Helper function to output an object containing the acceptable field sizes for all field types. This
     * assumes the object namespace is already defined.
     *
     * @param string $namespace the object namespace
     */
    public static function generateFieldTypeSizesMapJs($namespace = "page_ns")
    {
        $db = Core::$db;

        $db->query("
            SELECT field_type_id, compatible_field_sizes
            FROM   {PREFIX}field_types
        ");
        $db->execute();

        $js_rows = array($namespace . ".field_types = {}");
        foreach ($db->fetchAll() as $row) {
            $js_rows[] = "$namespace" . ".field_types[\"field_type_{$row["field_type_id"]}\"] = \"" . $row["compatible_field_sizes"] . "\"";
        }

        return implode(";\n", $js_rows);
    }


    /**
     * The counterpart function to ft_generate_field_type_sizes_map_js: this generate a hash of field size keys (1char, tiny, etc)
     * to their label (in the appropriate language).
     *
     * @param string $namespace
     */
    public static function generateFieldTypeSizeLabels($namespace = "page_ns")
    {
        $LANG = Core::$L;

        $js_rows = array();
        while (list($key, $info) = each(self::$fieldSizes)) {
            $js_rows[] = "  \"$key\": \"" . $LANG[$info["lang_key"]] . "\"";
        }
        reset(self::$fieldSizes);

        return $namespace . ".field_sizes = {\n" . implode(",\n", $js_rows) . "\n}";
    }

}
