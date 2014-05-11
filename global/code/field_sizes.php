<?php

/**
 * This file contains all functions relating to the available database field sizes. Added in 2.1.0 to
 * stop hardcoding all references.
 *
 * @copyright Benjamin Keen 2012
 * @author Benjamin Keen <ben.keen@gmail.com>
 * @package 2-2-x
 * @subpackage FieldTypes
 */

// -------------------------------------------------------------------------------------------------


$g_field_sizes = array(
  "1char"      => array("lang_key" => "phrase_1char", "sql" => "VARCHAR(1)"),
  "2chars"     => array("lang_key" => "phrase_2chars", "sql" => "VARCHAR(2)"),
  "tiny"       => array("lang_key" => "phrase_size_tiny", "sql" => "VARCHAR(5)"),
  "small"      => array("lang_key" => "phrase_size_small", "sql" => "VARCHAR(20)"),
  "medium"     => array("lang_key" => "phrase_size_medium", "sql" => "VARCHAR(255)"),
  "large"      => array("lang_key" => "phrase_size_large", "sql" => "TEXT"),
  "very_large" => array("lang_key" => "phrase_size_very_large", "sql" => "MEDIUMTEXT"),
);


/**
 * Returns an array of available database column sizes for a field type.
 *
 * @param integer $field_type_id
 */
function ft_get_field_type_sizes($field_type_id)
{
  global $g_table_prefix;

  $query = @mysql_query("
    SELECT compatible_field_sizes
    FROM   {$g_table_prefix}field_types
    WHERE  field_type_id = $field_type_id
  ");

  $result = mysql_fetch_assoc($query);
  return explode(",", $result["compatible_field_sizes"]);
}


/**
 * Helper function to output an object containing the acceptable field sizes for all field types. This
 * assumes the object namespace is already defined.
 *
 * @param string $namespace the object namespace
 */
function ft_generate_field_type_sizes_map_js($namespace = "page_ns")
{
  global $g_table_prefix;

  // order isn't important
  $query = mysql_query("
    SELECT field_type_id, compatible_field_sizes
    FROM   {$g_table_prefix}field_types
  ");

  $js_rows = array($namespace . ".field_types = {}");
  while ($row = mysql_fetch_assoc($query))
  {
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
function ft_generate_field_type_size_labels($namespace = "page_ns")
{
  global $LANG, $g_field_sizes;

  $js_rows = array();
  while (list($key, $info) = each($g_field_sizes))
  {
  	$js_rows[] = "  \"$key\": \"" . $LANG[$info["lang_key"]] . "\"";
  }
  reset($g_field_sizes);

  return $namespace . ".field_sizes = {\n" . implode(",\n", $js_rows) . "\n}";
}
