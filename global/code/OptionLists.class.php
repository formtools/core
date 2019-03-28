<?php

/**
 * Option Lists are preset lists of static strings.
 *
 * The DB structure that houses the data is a bit odd - looks like I retrofitted it to allow for grouping elements
 * in the list and I wanted to generalize the "grouping" part of it, hence the "list_group" table, used to house grouping
 * of any lists (not just option lists).
 *
 * DB structure for an option list.
 *      option_list [list_id] - the main (single) entry for the option list. This just stores a couple of pieces of
 *                              metadata: the name of the option list & whether the user wants it grouped or not.
 *      list_group [group_id] - all option lists have at least ONE list group. For option lists, this table contains
 *                              a (non-enforced!) unique identifier for the option list in the `group_type` column of
 *                              the form `option_list_N`, where N is the option list ID.
 *      field_options         - the actual items in the option list. Each item is mapped to a particular list group.
 */

// -------------------------------------------------------------------------------------------------

namespace FormTools;

use PDO, Exception;


class OptionLists {

    /**
     * Returns all list options in the database.
     *
     * @param $page_num number the current page number, or "all" for all results.
     * @return array ["results"] an array of option group information
     *               ["num_results"] the total number of option groups in the database.
     */
    public static function getList($default_options = array())
    {
        $db = Core::$db;

        $options = array_merge(array(
            "page" => "all", // or a number for a particular page num...
            "order" => "option_list_name-ASC",
            "per_page" => 10
        ), $default_options);

        if ($options["page"] == "all") {
            $limit_clause = "";
        } else {
            $first_item = ($options["page"] - 1) * $options["per_page"];
            $limit_clause = "LIMIT $first_item, {$options["per_page"]}";
        }

        $order_clause = self::getOptionListOrderClause($options["order"]);

        $db->query("
            SELECT *
            FROM   {PREFIX}option_lists
            $order_clause
            $limit_clause
        ");
        $db->execute();
        $results = $db->fetchAll();

        $option_lists = array();
        foreach ($results as $row) {
            $option_lists[] = $row;
        }

        $return_hash = array(
            "results" => $option_lists,
            "num_results" => OptionLists::getNumOptionLists()
        );

        extract(Hooks::processHookCalls("end", compact("return_hash"), array("return_hash")), EXTR_OVERWRITE);

        return $return_hash;
    }


    /**
     * Returns the total number of option lists in the database.
     *
     * @return integer
     */
    public static function getNumOptionLists()
    {
        $db = Core::$db;
        $db->query("SELECT count(*) as c FROM {PREFIX}option_lists");
        $db->execute();
        $result = $db->fetch();
        return $result["c"];
    }


    /**
     * Creates an identical copy of an existing Option List, or creates a new blank one. This can be handy if
     * the user was using a single group for multiple fields, but one of the form fields changed. They can just
     * create a new copy, tweak it and re-assign the field.
     *
     * @param integer $list_id
     * @param integer $field_id if this parameter is set, the new Option List will be assigned to whatever
     *   field IDs are specified. Note: this only works for Field Types that have a single
     * @return mixed the list ID if successful, false if not
     */
    public static function duplicateOptionList($list_id = "", $field_ids = array())
    {
        $db = Core::$db;

        $new_option_list_name = self::getNextOptionListName();

        if (empty($list_id)) {
            $new_list_id = self::addOptionList($new_option_list_name, "no");
        } else {
            $option_list_info = self::getOptionList($list_id);
            $new_list_id = self::addOptionList($new_option_list_name, $option_list_info["is_grouped"], $option_list_info["options"]);
        }

        // if we need to map this new option list to a field - or fields, loop through them and add them
        // one by one. Note: field types may use multiple Option Lists, which makes this extremely difficult. But
        // to make it as generic as possible, this code picks the first Option List field for the field type (as determined
        // by the setting list order)
        if (!empty($field_ids)) {
            foreach ($field_ids as $field_id) {
                $field_type_id = Fields::getFieldTypeIdByFieldId($field_id);
                $field_settings = FieldTypes::getFieldTypeSettings($field_type_id);

                $option_list_setting_id = "";
                foreach ($field_settings as $field_setting_info) {
                    if ($field_setting_info["field_type"] == "option_list_or_form_field") {
                        $option_list_setting_id = $field_setting_info["setting_id"];
                        break;
                    }
                }

                // this should ALWAYS have found a setting, but just in case...
                if (!empty($option_list_setting_id)) {
                    $db->query("DELETE FROM {PREFIX}field_settings WHERE field_id = $field_id AND setting_id = $option_list_setting_id");
                    $db->query("
                        INSERT INTO {PREFIX}field_settings (field_id, setting_id, setting_value)
                        VALUES (:field_id, :setting_id, :setting_value)
                    ");
                    $db->bindAll(array(
                        "field_id" => $field_id,
                        "setting_id" => $option_list_setting_id,
                        "setting_value" => $new_list_id
                    ));
                    $db->execute();
                }
            }
        }

        return $new_list_id;
    }


    /**
     * All new option lists need a unique name. This queries the database to find a sensible default value.
     * @return string
     */
    public static function getNextOptionListName()
    {
        $all_option_lists = OptionLists::getList();

        $list_names = array();
        foreach ($all_option_lists["results"] as $list_info) {
            $list_names[] = $list_info["option_list_name"];
        }

        $base_new_option_list = Core::$L["phrase_new_option_list"];
        $new_option_list_name = $base_new_option_list;

        $count = 1;
        while (in_array($new_option_list_name, $list_names)) {
            $count++;
            $new_option_list_name = "$base_new_option_list ($count)";
        }

        return $new_option_list_name;
    }


    /**
     * Returns the number of options in an option list - regardless of whether the option list
     * is grouped or not.
     *
     * @param integer $list_id
     * @return integer the option count
     */
    public static function getNumOptionsInOptionList($list_id)
    {
        Core::$db->query("
            SELECT count(*) as c
            FROM   {PREFIX}field_options
            WHERE  list_id = :list_id
        ");
        Core::$db->bind("list_id", $list_id);
        Core::$db->execute();
        $result = Core::$db->fetch();

        return $result["c"];
    }


    /**
     * Creates a new option list in the database. If the third $field_options parameter is set, it expects it
     * to be an array of form:
     *    array(
     *       array(
     *          "group_info" => array(
     *              "group_type" => "",
     *              "group_name" => ""
     *          ),
     *          "options" => array(
     *              array(
     *                 "option_value" => "",
     *                 "option_name" => "",
     *                 "is_new_sort_group" => "yes" | "no"
     *              )
     *          )
     *       )
     *    )
     *
     * Any other fields in the array are ignored. This allows us to pass the content from the getOptionList() right into
     * this method & it will create a new option list with the same data.
     */
    public static function addOptionList($name, $is_grouped = "no", $field_options = array())
    {
        $db = Core::$db;
        $db->query("
            INSERT INTO {PREFIX}option_lists (option_list_name, is_grouped)
            VALUES (:option_list_name, :is_grouped)
        ");
        $db->bindAll(array(
            "option_list_name" => $name,
            "is_grouped" => $is_grouped
        ));
        $db->execute();

        // now we add the groups for the option list items
        $list_id = $db->getInsertId();

        if ($is_grouped == "no") {
			$new_list_group_info = ListGroups::addListGroup("option_list_{$list_id}", "", 1);
			$new_list_group_id = $new_list_group_info["group_id"];

			$option_order = 1;
			foreach ($field_options[0]["options"] as $opt) {
				FieldOptions::addFieldOption($list_id, $new_list_group_id, $option_order, $opt["option_value"],
					$opt["option_name"], $opt["is_new_sort_group"]);
				$option_order++;
			}
        } else {
            // add the option groups and their field options
            $order = 1;
            foreach ($field_options as $grouped_option_info) {
                $group_info = $grouped_option_info["group_info"];
                $options    = $grouped_option_info["options"];

                $group_type = "option_list_{$list_id}";
                $group_name = $group_info["group_name"];

                $new_list_group_info = ListGroups::addListGroup($group_type, $group_name, $order);
                $new_list_group_id = $new_list_group_info["group_id"];

                $option_order = 1;
                foreach ($options as $opt) {
                    FieldOptions::addFieldOption($list_id, $new_list_group_id, $option_order, $opt["option_value"],
                        $opt["option_name"], $opt["is_new_sort_group"]);
                    $option_order++;
                }
                $order++;
            }

        }

        return $list_id;
    }


    /**
     * Returns all info about an option list.
     * @param integer $list_id
     */
    public static function getOptionList($list_id)
    {
        $db = Core::$db;

        $db->query("
            SELECT *
            FROM   {PREFIX}option_lists
            WHERE  list_id = :list_id
        ");
        $db->bind("list_id", $list_id);
        $db->execute();

        $info = $db->fetch();
        $info["options"] = OptionLists::getOptionListOptions($list_id);

        return $info;
    }

    /**
     * Awkward name, but there you go. It returns all options in an option list. If you want to get ALL
     * information about the group (e.g. the group name etc), use getOptionList().
     *
     * Option lists may or may not be grouped - but for consistency on the backend, even ungrouped option
     * lists are stored in an "empty" group. This function returns the GROUPED option lists as an array with the
     * following structure (there will only ever be a single top level array index for ungrouped option lists):
     *
     *   [0] => array(
     *            "group_info" => array(
     *                              "group_id"   => X,
     *                              "group_type" => "...",
     *                              "group_name" => "...",
     *                              "list_order" => Y
     *                            ),
     *            "options" => array(
     *              - an array of the option info directly from the ft_field_options table.
     *            )
     *          )
     *   [1] => ...
     *
     * Whether or not the option list is grouped is found in the "is_grouped" field in the ft_option_lists
     * table. That info is not returned by this function - only by OptionsLists::getOptionList().
     *
     * @param integer $list_id the option list ID
     * @return array
     */
    public static function getOptionListOptions($list_id)
    {
        $db = Core::$db;
        $groups = ListGroups::getByGroupType("option_list_{$list_id}");

        $info = array();
        foreach ($groups as $row) {
            $db->query("
                SELECT *
                FROM   {PREFIX}field_options
                WHERE  list_group_id = :group_id
                ORDER BY option_order
            ");
            $db->bind("group_id", $row["group_id"]);
            $db->execute();

            $options = $db->fetchAll();

            $curr_group = array(
                "group_info" => $row,
                "options"    => $options
            );
            $info[] = $curr_group;
        }

        return $info;
    }


    /**
     * Returns the number of fields that use a particular field option group.
     *
     * @param integer $group_id
     * @return integer the number of fields
     */
    public static function getNumFieldsUsingOptionList($list_id)
    {
        // technically it's possible for a single field to reference the same option list multiple times
        Core::$db->query("
            SELECT COUNT(DISTINCT field_id) as c
            FROM   {PREFIX}field_settings fs, {PREFIX}field_type_settings fts
            WHERE  fs.setting_value = :list_id AND
                   fs.setting_id = fts.setting_id AND
                   fts.field_type = 'option_list_or_form_field'
        ");
        Core::$db->bind("list_id", $list_id);
        Core::$db->execute();
        $result = Core::$db->fetch();

        return $result["c"];
    }


    /**
     * This returns the IDs of the previous and next Option Lists, as determined by the administrators current
     * sort.
     *
     * Not happy with this function! Getting this info is surprisingly tricky once you throw in the sort clause.
     * Still, the number of client accounts are liable to be quite small, so it's not such a sin.
     *
     * @param integer $list_id
     * @param array $search_criteria
     * @return array prev_option_list_id => the previous account ID (or empty string)
     *               next_option_list_id => the next account ID (or empty string)
     */
    public static function getOptionListPrevNextLinks($list_id, $order = "")
    {
        $db = Core::$db;

        $order_clause = self::getOptionListOrderClause($order);

        $db->query("
            SELECT list_id
            FROM   {PREFIX}option_lists
            $order_clause
        ");
        $db->execute();

        $sorted_list_ids = $db->fetchAll(PDO::FETCH_COLUMN);
        $current_index = array_search($list_id, $sorted_list_ids);

        $return_info = array(
            "prev_option_list_id" => "",
            "next_option_list_id" => ""
        );
        if ($current_index === 0) {
            if (count($sorted_list_ids) > 1) {
                $return_info["next_option_list_id"] = $sorted_list_ids[$current_index+1];
            }
        } else if ($current_index === count($sorted_list_ids)-1) {
            if (count($sorted_list_ids) > 1) {
                $return_info["prev_option_list_id"] = $sorted_list_ids[$current_index - 1];
            }
        } else {
            $return_info["prev_option_list_id"] = $sorted_list_ids[$current_index-1];
            $return_info["next_option_list_id"] = $sorted_list_ids[$current_index+1];
        }

        return $return_info;
    }


    /**
     * Updates an option list.
     */
    public static function updateOptionList($list_id, $info)
    {
        $db = Core::$db;
        $LANG = Core::$L;

        $is_grouped = isset($info["is_grouped"]) ? $info["is_grouped"] : "no";

        $db->query("
            UPDATE {PREFIX}option_lists
            SET    option_list_name = :option_list_name,
                   is_grouped = :is_grouped
            WHERE  list_id = :list_id
        ");
        $db->bindAll(array(
            "option_list_name" => $info["option_list_name"],
            "is_grouped" => $is_grouped,
            "list_id" => $list_id
        ));
        $db->execute();

        // remove the old field options & list groups, we're going to insert new ones
        FieldOptions::deleteByListId($list_id);
        ListGroups::deleteByGroupType("option_list_{$list_id}");

        // some ugliness to find out how our data is grouped and what's been removed
        $sortable_id = $info["sortable_id"];
        $new_groups    = explode(",", $info["{$sortable_id}_sortable__new_groups"]);
        $grouped_rows  = explode("~", $info["{$sortable_id}_sortable__rows"]);
        $deleted_group = isset($info["{$sortable_id}_sortable__delete_group"]) ? $info["{$sortable_id}_sortable__delete_group"] : "";

        // the logic here is a bit complex, but the general idea is that this code works for both grouped and
        // ungrouped option lists. Ungrouped option lists are still grouped in a single group behind the scenes
        $new_group_order = 1;

        if ($is_grouped == "no") {
            $empty_group_info = ListGroups::addListGroup("option_list_{$list_id}", "", 1);
            $empty_group_id = $empty_group_info["group_id"];
        }

        $order = 1;
        foreach ($grouped_rows as $curr_grouped_info) {
            list($curr_group_id, $ordered_row_ids_str) = explode("|", $curr_grouped_info);

            // if the user's deleting a group, we just ignore the group info so it's not re-created
            if ($curr_group_id == $deleted_group) {
                continue;
            }

            $ordered_row_ids = explode(",", $ordered_row_ids_str);

            if ($is_grouped == "yes") {
                $group_name = $info["group_name_{$curr_group_id}"];
                $new_group_info = ListGroups::addListGroup("option_list_{$list_id}", $group_name, $new_group_order);
                $curr_group_id = $new_group_info["group_id"];
                $new_group_order++;
            } else {
                $curr_group_id = $empty_group_id;
            }

            // now add the rows in this group
            foreach ($ordered_row_ids as $i) {
                if (!isset($info["field_option_value_{$i}"])) {
                    continue;
                }

                $value = $info["field_option_value_{$i}"];
                $text  = $info["field_option_text_{$i}"];
                $is_new_sort_group = (in_array($i, $new_groups)) ? "yes" : "no";

                FieldOptions::addFieldOption($list_id, $curr_group_id, $order, $value, $text, $is_new_sort_group);
                $order++;
            }
        }

        $success = true;
        $message = $LANG["notify_option_list_updated"];
        extract(Hooks::processHookCalls("end", compact("list_id", "info"), array("success", "message")), EXTR_OVERWRITE);

        return array($success, $message);
    }


    /**
     * Deletes an option list from the database. Note: it only deletes lists that don't have any
     * form fields assigned to them; generally this is prevented from being called unless that condition is
     * met, but it also checks here just in case.
     *
     * @param integer $list_id
     * @return array [0] T/F<br />
     *               [1] error/success message
     */
    public static function deleteOptionList($list_id)
    {
        $db = Core::$db;
        $LANG = Core::$L;

        // slight behavioural change in 2.1.0. Now you CAN delete Option Lists that are used by one or more fields.
        // It just clears any references, thus leaving those fields incompletely configured (which isn't the end of
        // the world!)
        $fields = OptionLists::getFieldsUsingOptionList($list_id);

        foreach ($fields as $field_info) {
            $field_id      = $field_info["field_id"];
            $field_type_id = $field_info["field_type_id"];
            $settings = FieldTypes::getFieldTypeSettings($field_type_id);

            $setting_ids = array();
            foreach ($settings as $setting_info) {
                if ($setting_info["field_type"] == "option_list_or_form_field") {
                    $setting_ids[] = $setting_info["setting_id"];
                }
            }
            if (empty($setting_ids)) {
                continue;
            }

            $setting_id_str = implode(",", $setting_ids);

            // now we delete any entries in the field_settings table with field_id, setting_id and a NUMERIC value for the
            // setting_value column. That column is also
            $db->query("
                DELETE FROM {PREFIX}field_settings
                WHERE field_id = :field_id AND
                      setting_id IN ($setting_id_str) AND
                      setting_value NOT LIKE 'form_field%'
            ");
            $db->bind("field_id", $field_id);
            $db->execute();
        }

        $db->query("DELETE FROM {PREFIX}field_options WHERE list_id = :list_id");
        $db->bind("list_id", $list_id);
        $db->execute();

        $db->query("DELETE FROM {PREFIX}option_lists WHERE list_id = :list_id");
        $db->bind("list_id", $list_id);
        $db->execute();

        ListGroups::deleteByGroupType("option_list_{$list_id}");

        $success = true;
        $message = $LANG["notify_option_list_deleted"];

        extract(Hooks::processHookCalls("end", compact("list_id"), array("success", "message")), EXTR_OVERWRITE);

        return array($success, $message);
    }


    /**
     * Returns information about fields that use a particular option list. If the second parameter is set,
     * it returns the information grouped by form instead.
     *
     * @param integer $list_id
     * @param array
     * @return array
     */
    public static function getFieldsUsingOptionList($list_id, $custom_params = array())
    {
        $db = Core::$db;

        $params = array(
            "group_by_form" => (isset($custom_params["group_by_form"])) ? $custom_params["group_by_form"] : false
        );

        $db->query("
            SELECT field_id
            FROM   {PREFIX}field_settings fs, {PREFIX}field_type_settings fts
            WHERE  fs.setting_value = :list_id AND
                fs.setting_id = fts.setting_id AND
                fts.field_type = 'option_list_or_form_field'
        ");
        $db->bind("list_id", $list_id);
        $db->execute();

        $field_ids = array();
        foreach ($db->fetchAll() as $row) {
            $field_ids[] = $row["field_id"];
        }

        if (empty($field_ids)) {
            return array();
        }

        $field_id_str = implode(",", $field_ids);
        $db->query("
            SELECT f.*, ff.*
            FROM   {PREFIX}form_fields ff, {PREFIX}forms f
            WHERE  field_id IN ($field_id_str) AND
                   f.form_id = ff.form_id
            ORDER BY f.form_name, ff.field_title
        ");
        $db->execute();
        $results = $db->fetchAll();

        if ($params["group_by_form"]) {
            $grouped_results = array();
            foreach ($results as $row) {
                if (!array_key_exists($row["form_id"], $grouped_results)) {
                    $grouped_results[$row["form_id"]] = array(
                        "form_name" => $row["form_name"],
                        "form_id"   => $row["form_id"],
                        "fields"    => array()
                    );
                }
                $grouped_results[$row["form_id"]]["fields"][] = $row;
            }
            $results = $grouped_results;
        }

        return $results;
    }


    /**
     * This function is called whenever the user adds an option list through the Add External form process. It checks
     * all existing option lists to see if an identical set already exists. If it does, it returns the existing
     * option list ID and if not, creates a new one and returns that ID.
     *
     * @param integer $form_id
     * @param array $option_list_info
     * @return integer $list_id the new or existing option list ID
     */
    public static function createUniqueOptionList($form_id, $option_list_info)
    {
        $db = Core::$db;

        $existing_option_lists = OptionLists::getList();

        $already_exists = false;
        $list_id = "";
        foreach ($existing_option_lists["results"] as $existing_option_list) {
            $curr_list_id = $existing_option_list["list_id"];

            // when comparing field groups, just compare the actual field options. The option list name & original
            // form aren't considered. This may lead to a little head-shaking in the UI when they see an inappropriate
            // option list name, but it's easily changed
            $grouped_option_list_info = self::getOptionListOptions($curr_list_id);

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
        if (!$already_exists) {
            $option_list_name = $option_list_info["option_list_name"];

            $db->query("
                INSERT INTO {PREFIX}option_lists (option_list_name, is_grouped, original_form_id)
                VALUES (:option_list_name, 'no', :original_form_id)
            ");
            $db->bindAll(array(
                "option_list_name" => $option_list_name,
                "original_form_id" => $form_id
            ));
            $db->execute();
            $list_id = $db->getInsertId();

            // now add the list group entry
            $db->query("
                INSERT INTO {PREFIX}list_groups (group_type, list_order)
                VALUES (:group_type, 1)
            ");
            $db->bind("group_type", "option_list_{$list_id}");
            $db->execute();
            $list_group_id = $db->getInsertId();

            // add the options
            $order = 1;
            foreach ($option_list_info["options"] as $option) {
                $value = $option["value"];
                $text  = $option["text"];

                $db->query("
                    INSERT INTO {PREFIX}field_options (list_id, list_group_id, option_value, option_name, option_order)
                    VALUES (:list_id, :list_group_id, :option_value, :option_name, :option_order)
                ");
                $db->bindAll(array(
                    "list_id" => $list_id,
                    "list_group_id" => $list_group_id,
                    "option_value" => $value,
                    "option_name" => $text,
                    "option_order" => $order
                ));
                $db->execute();
                $order++;
            }
        }

        return $list_id;
    }


    // --------------------------------------------------------------------------------------------

    /**
     * @param string $order
     */
    private static function getOptionListOrderClause($order)
    {
        $order_clause = "option_list_name ASC";
        $map = array(
            "list_id-DESC" => "list_id DESC",
            "option_list_name-ASC" => "option_list_name ASC",
            "option_list_name-DESC" => "option_list_name DESC"
        );
        if (isset($map[$order])) {
            $order_clause = $map[$order];
        }

        return "ORDER BY $order_clause";
    }

}
