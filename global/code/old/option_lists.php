<?php

/**
 * This file contains all functions relating to managing the Option Lists. Before 2.1.0, Option Lists
 * were called "Field Option Groups" & this file was named field_option_groups.php
 *
 * @copyright Benjamin Keen 2014
 * @author Benjamin Keen <ben.keen@gmail.com>
 * @package 2-2-x
 * @subpackage OptionLists
 */


// -------------------------------------------------------------------------------------------------

use FormTools\ListGroups;
use FormTools\OptionLists;


/**
 * This function is called whenever the user adds an option list through the Add External form process. It checks
 * all existing option lists to see if an identical set already exists. If it does, it returns the existing
 * option list ID and if not, creates a new one and returns that ID.
 *
 * @param integer $form_id
 * @param array $option_list_info
 * @return integer $list_id the new or existing option list ID
 */
function ft_create_unique_option_list($form_id, $option_list_info)
{
	global $g_table_prefix;

	$existing_option_lists = OptionLists::getList(array(
        "per_page" => Sessions::get("settings.num_option_lists_per_page")
    ));

	$already_exists = false;
	$list_id = "";
	foreach ($existing_option_lists["results"] as $existing_option_list)
	{
		$curr_list_id = $existing_option_list["list_id"];

		// when comparing field groups, just compare the actual field options. The option list name & original
		// form aren't considered. This may lead to a little head-shaking in the UI when they see an inappropriate
		// option list name, but it's easily changed
		$grouped_option_list_info = OptionLists::getOptionListOptions($curr_list_id);

		// $curr_options contains an array of hashes. Each hash contains information about the group & info about
		// the options in that group. Since we're just comparing a brand new list, we know that it only has one group:
		// hence, rule out those option lists with more than one group
		if (count($grouped_option_list_info) > 1) {
            continue;
        }

		// fringe case. Technically, a user may have created an Option List then deleted all options & groups.
		if (count($grouped_option_list_info) == 0) {
            continue;
        }

		$curr_options = $grouped_option_list_info[0]["options"];
		if (count($curr_options) != count($option_list_info["options"])) {
            continue;
        }

		$has_same_option_fields = true;
		for ($i=0; $i<count($curr_options); $i++) {
			$val = $curr_options[$i]["option_value"];
			$txt = $curr_options[$i]["option_name"];

			$val2 = $option_list_info["options"][$i]["value"];
			$txt2 = $option_list_info["options"][$i]["text"];

			if ($val != $val2 || $txt != $txt2) {
				$has_same_option_fields = false;
				break;
			}
		}

		if (!$has_same_option_fields) {
            continue;
        }

		$already_exists = true;
		$list_id = $curr_list_id;
		break;
	}

	// if this group didn't already exist, add it!
	if (!$already_exists)
	{
		$option_list_name = $option_list_info["option_list_name"];

		$query = "INSERT INTO {$g_table_prefix}option_lists (option_list_name, is_grouped, original_form_id)
      VALUES ('$option_list_name', 'no', $form_id)";
		$result = mysql_query($query) or ft_handle_error($query, mysql_error());

		if (!$result)
			return false;

		$list_id = mysql_insert_id();

		// now add the list group entry
		$query = mysql_query("
      INSERT INTO {$g_table_prefix}list_groups (group_type, list_order)
      VALUES ('option_list_{$list_id}', 1)
    ") or die(mysql_error());
		$list_group_id = mysql_insert_id();

		// add the options
		$order = 1;
		foreach ($option_list_info["options"] as $option)
		{
			$value = $option["value"];
			$text  = $option["text"];

			$query = "
        INSERT INTO {$g_table_prefix}field_options (list_id, list_group_id, option_value, option_name, option_order)
        VALUES ($list_id, $list_group_id, '$value', '$text', $order)
          ";
			$result = mysql_query($query) or ft_handle_error($query, mysql_error());
			$order++;
		}
	}

	return $list_id;
}


