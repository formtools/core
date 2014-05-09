<?php

// any time the user changes the field type, we reset all the non-main values (i.e. anything other than field title,
// database field size & pass on)
if (isset($request["update_field_type"]))
{
  $field_id = $request["field_id"];
  $new_field_type = $request["field_type"];

  // N.B. we don't bother displaying any message when they change the field type
  ft_change_field_type($form_id, $field_id, $new_field_type);
}

if (!isset($request["field_id"]))
{
	header("location: edit.php?page=fields");
	exit;
}

$field_id = $request["field_id"];
$field_info    = ft_get_form_field($field_id);
$form_info     = ft_get_form($form_id);

// get the ID of the previous and next field options. We should probably cache this, but until I'm sure
// it's slowing things down, we'll keep it simple
$ordered_field_ids = array();
$ordered_form_fields = ft_get_form_fields($form_id);
$previous_field_link = "<span class=\"light_grey\">{$LANG["phrase_previous_field"]}</span>";
$next_field_link = "<span class=\"light_grey\">{$LANG["phrase_next_field"]}</span>";
$num_form_fields = count($ordered_form_fields);

for ($i=0; $i<$num_form_fields; $i++)
{
	$curr_field_id = $ordered_form_fields[$i]["field_id"];

	if ($curr_field_id == $field_id)
	{
		if ($i != 0)
		{
			$previous_field_id = $ordered_form_fields[$i-1]["field_id"];
			$previous_field_link = "<a href=\"{$_SERVER["PHP_SELF"]}?page=field_options&field_id=$previous_field_id\">{$LANG["phrase_previous_field"]}</a>";
		}
		if ($i != $num_form_fields - 1)
		{
			$next_field_id = $ordered_form_fields[$i+1]["field_id"];
			$next_field_link = "<a href=\"{$_SERVER["PHP_SELF"]}?page=field_options&field_id=$next_field_id\">{$LANG["phrase_next_field"]}</a>";
		}
	}
}

switch ($field_info["field_type"])
{
	case "system":
		require_once("field_types/system.php");
		break;
	case "textbox":
		require_once("field_types/textbox.php");
		break;
	case "password":
		require_once("field_types/password.php");
		break;
  case "textarea":
		require_once("field_types/textarea.php");
		break;
	case "select":
		require_once("field_types/select.php");
		break;
	case "multi-select":
		require_once("field_types/multi_select.php");
		break;
	case "radio-buttons":
		require_once("field_types/radios.php");
		break;
	case "checkboxes":
		require_once("field_types/checkboxes.php");
		break;
	case "file":
		require_once("field_types/file.php");
		break;
	case "wysiwyg":
		require_once("field_types/wysiwyg.php");
		break;
}

