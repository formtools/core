<?php

/**
 * This file contains all functions relating to form Views.
 *
 * @copyright Benjamin Keen 2012
 * @author Benjamin Keen <ben.keen@gmail.com>
 * @package 2-2-x
 * @subpackage Views
 */


// -------------------------------------------------------------------------------------------------


/**
 * This function is called after creating a new form (ft_finalize_form), and creates a default
 * View - one containing all fields and assigned to all clients that are assigned to the form.
 *
 * Notes: I'm not terribly happy about the relationship between the list_groups table and whatever
 * they're grouping - here, Views. The issue is that to make the entries in the list_groups table
 * have additional meaning, I customize the group_type value to something like "form_X_view_group"
 * where "X" is the form name. ...
 *
 * @param integer $form_id
 */
function ft_add_default_view($form_id)
{
  global $g_table_prefix, $LANG;

  // 1. create the new View
  $form_info = ft_get_form($form_id);
  $num_submissions_per_page = isset($_SESSION["ft"]["settings"]["num_submissions_per_page"]) ? $_SESSION["ft"]["settings"]["num_submissions_per_page"] : 10;

  mysql_query("
    INSERT INTO {$g_table_prefix}views (form_id, view_name, view_order, num_submissions_per_page,
      default_sort_field, default_sort_field_order)
    VALUES ($form_id, '{$LANG["phrase_all_submissions"]}', '1', $num_submissions_per_page, 'submission_date', 'desc')
      ");
  $view_id = mysql_insert_id();

  // 2. create the View group and update the view record we just created (blurgh!)
  mysql_query("
    INSERT INTO {$g_table_prefix}list_groups (group_type, group_name, list_order)
    VALUES ('form_{$form_id}_view_group', '{$LANG["word_views"]}', 1)
  ");
  $group_id = mysql_insert_id();
  mysql_query("UPDATE {$g_table_prefix}views SET group_id = $group_id WHERE view_id = $view_id");

  // 3. add the default tabs [N.B. this table should eventually be dropped altogether and data moved to list_groups]
  $view_tab_inserts = array(
    "($view_id, 1, '{$LANG["phrase_default_tab_label"]}')",
    "($view_id, 2, '')",
    "($view_id, 3, '')",
    "($view_id, 4, '')",
    "($view_id, 5, '')",
    "($view_id, 6, '')"
  );
  $view_tab_insert_str = implode(",\n", $view_tab_inserts);
  mysql_query("INSERT INTO {$g_table_prefix}view_tabs VALUES $view_tab_insert_str");

  // now populate the new View fields and the View columns
  _ft_populate_new_view_fields($form_id, $view_id);

  // assign the View to all clients attached to this form
  $client_info = $form_info["client_info"];
  foreach ($client_info as $user)
  {
    $account_id = $user["account_id"];
    mysql_query("
      INSERT INTO {$g_table_prefix}client_views (account_id, view_id)
      VALUES ($account_id, $view_id)
        ");
  }

  return array(true, $LANG["notify_new_default_view_created"]);
}


/**
 * This checks to see if a View exists in the database.
 *
 * @param integer $view_id
 * @param boolean
 * @return boolean
 */
function ft_check_view_exists($view_id, $ignore_hidden_views = false)
{
  global $g_table_prefix;

  $view_clause = ($ignore_hidden_views) ? " AND access_type != 'hidden' " : "";

  $query = mysql_query("
    SELECT count(*) as c
    FROM {$g_table_prefix}views
    WHERE view_id = $view_id
          $view_clause
      ");

  $results = mysql_fetch_assoc($query);

  return $results["c"] > 0;
}


/**
 * Retrieves all information about a View, including associated user and filter info.
 *
 * @param integer $view_id the unique view ID
 * @return array a hash of view information
 */
function ft_get_view($view_id, $custom_params = array())
{
  global $g_table_prefix;

  $params = array(
    "include_field_settings" => (isset($custom_params["include_field_settings"])) ? $custom_params["include_field_settings"] : false
  );

  $query = "SELECT * FROM {$g_table_prefix}views WHERE view_id = $view_id";
  $result = mysql_query($query);

  $view_info = mysql_fetch_assoc($result);
  $view_info["client_info"] = ft_get_view_clients($view_id);
  $view_info["columns"]     = ft_get_view_columns($view_id);
  $view_info["fields"]      = ft_get_view_fields($view_id, $params);
  $view_info["filters"]     = ft_get_view_filters($view_id);
  $view_info["tabs"]        = ft_get_view_tabs($view_id);
  $view_info["client_omit_list"] = (isset($view_info["access_type"]) && $view_info["access_type"] == "public") ?
    ft_get_public_view_omit_list($view_id) : array();

  extract(ft_process_hook_calls("end", compact("view_id", "view_info"), array("view_info")), EXTR_OVERWRITE);

  return $view_info;
}


/**
 * Retrieves a list of all views for a form. As of 2.0.5 this function now always returns ALL Views,
 * instead of the option of a single page.
 *
 * @param integer $form_id the unique form ID
 * @return array a hash of view information
 */
function ft_get_views($form_id)
{
  global $g_table_prefix;

  $result = mysql_query("
    SELECT view_id
    FROM 	 {$g_table_prefix}views
    WHERE  form_id = $form_id
    ORDER BY view_order
      ");

  $view_info = array();
  while ($row = mysql_fetch_assoc($result))
  {
    $view_id = $row["view_id"];
    $view_info[] = ft_get_view($view_id);
  }

  $return_hash["results"] = $view_info;
  $return_hash["num_results"]  = count($view_info);

  extract(ft_process_hook_calls("end", compact("return_hash"), array("return_hash")), EXTR_OVERWRITE);

  return $return_hash;
}


/**
 * A simple, fast, no-frills function to return an array of all View IDs for a form. If you need it ordered,
 * include the second parameter. The second param makes it slower, so only use when needed.
 *
 * @param integer $form_id
 * @param boolean $order_results whether or not the results should be ordered. If so, it orders by view group,
 *    then view_order.
 * @return array
 */
function ft_get_view_ids($form_id, $order_results = false)
{
  global $g_table_prefix;

  if ($order_results)
  {
  	$query = mysql_query("
  	  SELECT view_id
  	  FROM   {$g_table_prefix}views v, {$g_table_prefix}list_groups lg
  	  WHERE  v.group_id = lg.group_id AND
  	         form_id = $form_id
  	  ORDER BY lg.list_order, v.view_order
  	");
  }
  else
  {
    $query = mysql_query("SELECT view_id FROM {$g_table_prefix}views WHERE form_id = $form_id");
  }

  $view_ids = array();
  while ($row = mysql_fetch_assoc($query))
    $view_ids[] = $row["view_id"];

  extract(ft_process_hook_calls("end", compact("view_ids"), array("view_ids")), EXTR_OVERWRITE);

  return $view_ids;
}


/**
 * This returns the database column names of all searchable fields in this View. To reduce the number of
 * DB queries, this function allows you to pass in all field info to just extract the information from that.
 *
 * @param integer $view_id optional, but if not supplied, the second $fields parameter is require
 * @param array $fields optional, but if not supplied, the first $view_id param is required. This should
 *   be the $view_info["fields"] key, returned from $view_info = ft_get_view($view_id), which contains all
 *   View field info
 *
 * @return array an array of searchable database column names
 */
function ft_get_view_searchable_fields($view_id = "", $fields = array())
{
  if (!empty($view_id) && is_numeric($view_id))
  {
    $view_info = ft_get_view($view_id);
    $fields    = $view_info["fields"];
  }
  $searchable_columns = array();
  foreach ($fields as $field_info)
  {
    if ($field_info["is_searchable"] == "yes")
      $searchable_columns[] = $field_info["col_name"];
  }

  return $searchable_columns;
}


/**
 * Returns all tab information for a particular form view. If the second parameter is
 * set to true.
 *
 * @param integer $view_id the unique view ID
 * @return array the array of tab info, ordered by tab_order
 */
function ft_get_view_tabs($view_id, $return_non_empty_tabs_only = false)
{
  global $g_table_prefix;

  $result = mysql_query("
    SELECT *
    FROM   {$g_table_prefix}view_tabs
    WHERE  view_id = $view_id
    ORDER BY tab_number
      ");

  $tab_info = array();
  while ($row = mysql_fetch_assoc($result))
  {
    if ($return_non_empty_tabs_only && empty($row["tab_label"]))
      continue;

    $tab_info[$row["tab_number"]] = array("tab_label" => $row["tab_label"]);
  }

  extract(ft_process_hook_calls("end", compact("view_id", "tab_info"), array("tab_info")), EXTR_OVERWRITE);

  return $tab_info;
}


/**
 * Creates a new form View. If the $view_id parameter is set, it makes a copy of that View.
 * Otherwise, it creates a new blank view has *all* fields associated with it by default, a single tab
 * that is not enabled by default, no filters, and no clients assigned to it.
 *
 * @param integer $form_id the unique form ID
 * @param integer $group_id the view group ID that we're adding this View to
 * @param integer $create_from_view_id (optional) either the ID of the View from which to base this new View on,
 *                or "blank_view_no_fields" or "blank_view_all_fields"
 * @return integer the new view ID
 */
function ft_create_new_view($form_id, $group_id, $view_name = "", $create_from_view_id = "")
{
  global $g_table_prefix, $LANG;

  // figure out the next View order number
  $count_query = mysql_query("SELECT count(*) as c FROM {$g_table_prefix}views WHERE form_id = $form_id");
  $count_hash = mysql_fetch_assoc($count_query);
  $num_form_views = $count_hash["c"];
  $next_order = $num_form_views + 1;
  $view_name = (empty($view_name)) ? $LANG["phrase_new_view"] : ft_sanitize($view_name);

  if ($create_from_view_id == "blank_view_no_fields" || $create_from_view_id == "blank_view_all_fields")
  {
    // add the View with default values
    mysql_query("
      INSERT INTO {$g_table_prefix}views (form_id, view_name, view_order, is_new_sort_group, group_id)
      VALUES ($form_id, '$view_name', $next_order, 'yes', $group_id)
        ");
    $view_id = mysql_insert_id();

    // add the default tab
    mysql_query("INSERT INTO {$g_table_prefix}view_tabs (view_id, tab_number, tab_label) VALUES ($view_id, 1, '{$LANG["phrase_default_tab_label"]}')");
    mysql_query("INSERT INTO {$g_table_prefix}view_tabs (view_id, tab_number, tab_label) VALUES ($view_id, 2, '')");
    mysql_query("INSERT INTO {$g_table_prefix}view_tabs (view_id, tab_number, tab_label) VALUES ($view_id, 3, '')");
    mysql_query("INSERT INTO {$g_table_prefix}view_tabs (view_id, tab_number, tab_label) VALUES ($view_id, 4, '')");
    mysql_query("INSERT INTO {$g_table_prefix}view_tabs (view_id, tab_number, tab_label) VALUES ($view_id, 5, '')");
    mysql_query("INSERT INTO {$g_table_prefix}view_tabs (view_id, tab_number, tab_label) VALUES ($view_id, 6, '')");

    if ($create_from_view_id == "blank_view_all_fields")
    {
      _ft_populate_new_view_fields($form_id, $view_id);
    }
  }
  else
  {
    $view_info = ft_get_view($create_from_view_id);
    $view_info = ft_sanitize($view_info);

    // Main View Settings
    $view_order = $view_info["view_order"];
    $access_type              = $view_info["access_type"];
    $num_submissions_per_page = $view_info["num_submissions_per_page"];
    $default_sort_field       = $view_info["default_sort_field"];
    $default_sort_field_order = $view_info["default_sort_field_order"];
    $may_add_submissions      = $view_info["may_add_submissions"];
    $may_edit_submissions     = $view_info["may_edit_submissions"];
    $may_delete_submissions   = $view_info["may_delete_submissions"];
    $has_standard_filter      = $view_info["has_standard_filter"];
    $has_client_map_filter    = $view_info["has_client_map_filter"];

    mysql_query("
      INSERT INTO {$g_table_prefix}views (form_id, access_type, view_name, view_order, is_new_sort_group, group_id,
        num_submissions_per_page, default_sort_field, default_sort_field_order, may_add_submissions, may_edit_submissions,
        may_delete_submissions, has_client_map_filter, has_standard_filter)
      VALUES ($form_id, '$access_type', '$view_name', $next_order, 'yes', $group_id, $num_submissions_per_page,
        '$default_sort_field', '$default_sort_field_order', '$may_add_submissions', '$may_edit_submissions',
        '$may_delete_submissions', '$has_client_map_filter', '$has_standard_filter')
        ");
    $view_id = mysql_insert_id();

    foreach ($view_info["client_info"] as $client_info)
    {
      $account_id = $client_info["account_id"];
      mysql_query("INSERT INTO {$g_table_prefix}client_views (account_id, view_id) VALUES ($account_id, $view_id)");
    }

    // View Tabs
    $tabs = $view_info["tabs"];
    $tab1 = $tabs[1]["tab_label"];
    $tab2 = $tabs[2]["tab_label"];
    $tab3 = $tabs[3]["tab_label"];
    $tab4 = $tabs[4]["tab_label"];
    $tab5 = $tabs[5]["tab_label"];
    $tab6 = $tabs[6]["tab_label"];
    mysql_query("INSERT INTO {$g_table_prefix}view_tabs (view_id, tab_number, tab_label) VALUES ($view_id, 1, '$tab1')");
    mysql_query("INSERT INTO {$g_table_prefix}view_tabs (view_id, tab_number, tab_label) VALUES ($view_id, 2, '$tab2')");
    mysql_query("INSERT INTO {$g_table_prefix}view_tabs (view_id, tab_number, tab_label) VALUES ($view_id, 3, '$tab3')");
    mysql_query("INSERT INTO {$g_table_prefix}view_tabs (view_id, tab_number, tab_label) VALUES ($view_id, 4, '$tab4')");
    mysql_query("INSERT INTO {$g_table_prefix}view_tabs (view_id, tab_number, tab_label) VALUES ($view_id, 5, '$tab5')");
    mysql_query("INSERT INTO {$g_table_prefix}view_tabs (view_id, tab_number, tab_label) VALUES ($view_id, 6, '$tab6')");


    // with 2.1.0, all View fields are now grouped. We need to duplicate all the groups as well as the fields
    $group_id_map = ft_duplicate_view_field_groups($create_from_view_id, $view_id);

    $field_view_inserts = array();
    foreach ($view_info["fields"] as $field_info)
    {
      $field_id      = $field_info["field_id"];
      $new_group_id  = $group_id_map[$field_info["group_id"]];
      $is_editable   = $field_info["is_editable"];
      $is_searchable = $field_info["is_searchable"];
      $list_order    = $field_info["list_order"];
      $is_new_sort_group = $field_info["is_new_sort_group"];
      $field_view_inserts[] = "($view_id, $field_id, $new_group_id, '$is_editable', '$is_searchable', $list_order, '$is_new_sort_group')";
    }
    if (!empty($field_view_inserts))
    {
      $field_view_inserts_str = implode(",\n", $field_view_inserts);
      mysql_query("
        INSERT INTO {$g_table_prefix}view_fields (view_id, field_id, group_id, is_editable,
          is_searchable, list_order, is_new_sort_group)
        VALUES $field_view_inserts_str
      ");
    }

    $view_column_inserts = array();
    foreach ($view_info["columns"] as $field_info)
    {
      $field_id     = $field_info["field_id"];
      $list_order   = $field_info["list_order"];
      $is_sortable  = $field_info["is_sortable"];
      $auto_size    = $field_info["auto_size"];
      $custom_width = $field_info["custom_width"];
      $truncate     = $field_info["truncate"];
      $view_column_inserts[] = "($view_id, $field_id, $list_order, '$is_sortable', '$auto_size', '$custom_width', '$truncate')";
    }
    if (!empty($view_column_inserts))
    {
      $view_column_insert_str = implode(",\n", $view_column_inserts);
      mysql_query("
        INSERT INTO {$g_table_prefix}view_columns (view_id, field_id, list_order, is_sortable, auto_size, custom_width, truncate)
        VALUES $view_column_insert_str
      ");
    }

    // View Filters
    foreach ($view_info["filters"] as $filter_info)
    {
      $field_id      = $filter_info["field_id"];
      $filter_type   = $filter_info["filter_type"];
      $operator      = $filter_info["operator"];
      $filter_values = $filter_info["filter_values"];
      $filter_sql    = $filter_info["filter_sql"];

      mysql_query("
        INSERT INTO {$g_table_prefix}view_filters (view_id, filter_type, field_id, operator, filter_values, filter_sql)
        VALUES ($view_id, '$filter_type', $field_id, '$operator', '$filter_values', '$filter_sql')
          ");
    }

    // default submission values
    $submission_defaults = ft_get_new_view_submission_defaults($create_from_view_id);
    foreach ($submission_defaults as $row)
    {
    	$field_id      = $row["field_id"];
    	$default_value = ft_sanitize($row["default_value"]);
    	$list_order    = $row["list_order"];

    	mysql_query("
    	  INSERT INTO {$g_table_prefix}new_view_submission_defaults (view_id, field_id, default_value, list_order)
    	  VALUES ($view_id, $field_id, '$default_value', $list_order)
    	");
    }

    // public View omit list
    $client_ids = ft_get_public_view_omit_list($create_from_view_id);
    foreach ($client_ids as $client_id)
    {
      mysql_query("
        INSERT INTO {$g_table_prefix}public_view_omit_list (view_id, account_id)
        VALUES ($view_id, $client_id)
      ");
    }
  }

  extract(ft_process_hook_calls("end", compact("view_id"), array()), EXTR_OVERWRITE);

  return $view_id;
}


/**
 * Returns the View field values from the view_fields table, as well as a few values
 * from the corresponding form_fields table.
 *
 * @param integer $view_id the unique View ID
 * @param integer $field_id the unique field ID
 * @return array a hash containing the various view field values
 */
function ft_get_view_field($view_id, $field_id, $custom_params = array())
{
  global $g_table_prefix;

  $params = array(
    "include_field_settings" => (isset($custom_params["include_field_settings"])) ? $custom_params["include_field_settings"] : false
  );

  $query = mysql_query("
    SELECT vf.*, ft.field_title, ft.col_name, ft.field_type_id, ft.field_name
    FROM   {$g_table_prefix}view_fields vf, {$g_table_prefix}form_fields ft
    WHERE  view_id = $view_id AND
           vf.field_id = ft.field_id AND
           vf.field_id = $field_id
      ");

  $result = mysql_fetch_assoc($query);

  if ($params["include_field_settings"])
  {
    $result["field_settings"] = ft_get_form_field_settings($field_id);
  }

  return $result;
}


/**
 * Returns all information about a View columns.
 *
 * @param integer $view_id
 */
function ft_get_view_columns($view_id)
{
  global $g_table_prefix;

  $query = mysql_query("
    SELECT *
    FROM   {$g_table_prefix}view_columns
    WHERE  view_id = $view_id
    ORDER BY list_order
  ");

  $info = array();
  while ($row = mysql_fetch_assoc($query))
    $info[] = $row;

  return $info;
}


/**
 * Returns all fields in a View.
 *
 * @param integer $view_id the unique View ID
 * @return array $info an array of hashes containing the various view field values.
 */
function ft_get_view_fields($view_id, $custom_params = array())
{
  global $g_table_prefix;

  $params = array(
    "include_field_settings" => (isset($custom_params["include_field_settings"])) ? $custom_params["include_field_settings"] : false
  );

  $result = mysql_query("
    SELECT vf.field_id
    FROM   {$g_table_prefix}list_groups lg, {$g_table_prefix}view_fields vf
    WHERE  lg.group_type = 'view_fields_$view_id' AND
           lg.group_id = vf.group_id
    ORDER BY lg.list_order ASC, vf.list_order ASC
  ");

  $fields_info = array();
  while ($field_info = mysql_fetch_assoc($result))
  {
    $field_id = $field_info["field_id"];
    $fields_info[] = ft_get_view_field($view_id, $field_id, $params);
  }

  return $fields_info;
}


/**
 * Verbose name, but this function returns a hash of group_id => tab number for a particular View. In
 * other words, it looks at the View field groups to find out which tab each one belongs to.
 *
 * @return array
 */
function ft_get_view_field_group_tabs($view_id)
{
  global $g_table_prefix;

  $query = mysql_query("
    SELECT group_id, custom_data
    FROM   {$g_table_prefix}list_groups
    WHERE  group_type = 'view_fields_{$view_id}'
  ");

  $map = array();
  while ($row = mysql_fetch_assoc($query))
  {
    $map[$row["group_id"]] = $row["custom_data"];
  }

  return $map;
}


/**
 * Return all fields in a View. If this is being on the edit submission page, the second optional
 * parameter is used to limit the results to ONLY those groups on the appropriate tab.
 *
 * @param integer $view_id
 * @param integer $tab_number
 * @param integer $form_id       - this is optional. If this and the next $submission_id param is defined,
 *                                 details about the actual form submission is returned as well
 * @param integer $submission_id
 * @return array
 */
function ft_get_grouped_view_fields($view_id, $tab_number = "", $form_id = "", $submission_id = "")
{
  global $g_table_prefix;

  $tab_clause = (!empty($tab_number)) ? "AND custom_data = $tab_number" : "";

  $group_query = mysql_query("
    SELECT *
    FROM  {$g_table_prefix}list_groups
    WHERE  group_type = 'view_fields_{$view_id}'
           $tab_clause
    ORDER BY list_order
      ");

  if (!empty($submission_id))
  {
    $submission_info = ft_get_submission_info($form_id, $submission_id);
  }

  $grouped_info = array();
  while ($group_info = mysql_fetch_assoc($group_query))
  {
    $group_id = $group_info["group_id"];

    $field_query = mysql_query("
      SELECT *, vf.list_order as list_order, vf.is_new_sort_group as view_field_is_new_sort_group
      FROM   {$g_table_prefix}view_fields vf, {$g_table_prefix}form_fields ff
      WHERE  group_id = $group_id AND
             vf.field_id = ff.field_id
      ORDER BY vf.list_order
    ");

    $fields_info = array();
    $field_ids   = array();
    while ($row = mysql_fetch_assoc($field_query))
    {
      $field_ids[]   = $row["field_id"];
      $fields_info[] = $row;
    }

    // for efficiency reasons, we just do a single query to find all validation rules for the all relevant fields
    if (!empty($field_ids))
    {
      $field_ids_str = implode(",", $field_ids);
      $validation_query = mysql_query("
        SELECT *
        FROM   {$g_table_prefix}field_validation fv, {$g_table_prefix}field_type_validation_rules ftvr
        WHERE  fv.field_id IN ($field_ids_str) AND
               fv.rule_id = ftvr.rule_id
      ");

      $rules_by_field_id = array();
      while ($rule_info = mysql_fetch_assoc($validation_query))
      {
    	  $field_id = $rule_info["field_id"];
      	if (!array_key_exists($field_id, $rules_by_field_id))
      	{
      	  $rules_by_field_id[$field_id]["is_required"] = false;
    	    $rules_by_field_id[$field_id]["rules"] = array();
      	}

        $rules_by_field_id[$field_id]["rules"][] = $rule_info;
        if ($rule_info["rsv_rule"] == "required" || ($rule_info["rsv_rule"] == "function" && $rule_info["custom_function_required"] == "yes"))
        {
      	  $rules_by_field_id[$field_id]["is_required"] = true;
        }
      }
    }

    // now merge the original field info with the new validation rules. "required" is a special validation rule: that's
    // used to determine whether or not an asterix should appear next to the field. As such, we pass along a
    // custom "is_required" key
    $updated_field_info = array();
    foreach ($fields_info as $field_info)
    {
    	$curr_field_id = $field_info["field_id"];
    	$field_info["validation"] = array_key_exists($curr_field_id, $rules_by_field_id) ? $rules_by_field_id[$curr_field_id]["rules"] : array();
    	$field_info["is_required"] = array_key_exists($curr_field_id, $rules_by_field_id) ? $rules_by_field_id[$curr_field_id]["is_required"] : false;
      $updated_field_info[] = $field_info;
    }
    $fields_info = $updated_field_info;

    // now, if the submission ID is set it returns an additional submission_value key
    if (!empty($field_ids))
    {
      // do a single query to get a list of ALL settings for any of the field IDs we're dealing with
      $field_id_str = implode(",", $field_ids);
      $field_settings_query = mysql_query("
        SELECT *
        FROM   {$g_table_prefix}field_settings
        WHERE  field_id IN ($field_id_str)
      ");

      $field_settings = array();
      while ($row = mysql_fetch_assoc($field_settings_query))
      {
        $field_id = $row["field_id"];
        if (!array_key_exists($field_id, $field_settings))
          $field_settings[$field_id] = array();

        $field_settings[$field_id][] = array($row["setting_id"] => $row["setting_value"]);
      }

      // now append the submission info to the field info that we already have stored
      $updated_fields_info = array();
      foreach ($fields_info as $curr_field_info)
      {
        $curr_col_name = $curr_field_info["col_name"];
        $curr_field_id = $curr_field_info["field_id"];
        $curr_field_info["field_settings"] = (array_key_exists($curr_field_id, $field_settings)) ? $field_settings[$curr_field_id] : array();

        if (!empty($submission_id))
          $curr_field_info["submission_value"] = $submission_info[$curr_col_name];

        $updated_fields_info[] = $curr_field_info;
      }
      $fields_info = $updated_fields_info;
    }

    $grouped_info[] = array(
      "group"  => $group_info,
      "fields" => $fields_info
    );
  }

  return $grouped_info;
}


/**
 * Finds out what Views are associated with a particular form field. Used when deleting
 * a field.
 *
 * @param integer $field_id
 * @return array $view_ids
 */
function ft_get_field_views($field_id)
{
  global $g_table_prefix;

  $query = mysql_query("SELECT view_id FROM {$g_table_prefix}view_fields WHERE field_id = $field_id");

  $view_ids = array();
  while ($row = mysql_fetch_assoc($query))
    $view_ids[] = $row["view_id"];

  return $view_ids;
}


/**
 * This is called by the administrator on the main Views tab. It lets them delete an entire group of Views
 * in one go.
 *
 * @param integer $group_id
 */
function ft_delete_view_group($group_id)
{
  global $g_table_prefix, $LANG;

  $query = mysql_query("
    SELECT view_id
    FROM   {$g_table_prefix}views
    WHERE  group_id = $group_id
  ");

  // first, delete all the Views
  while ($view_info = mysql_fetch_assoc($query))
  {
    $view_id = $view_info["view_id"];
    ft_delete_view($view_id);
  }

  // next, delete the group
  mysql_query("DELETE FROM {$g_table_prefix}list_groups WHERE group_id = $group_id");

  // TODO: should update order of other View groups

  return array(true, $LANG["notify_view_group_deleted"]);
}


/**
 * Deletes a View and updates the list order of the Views in the same View group.
 *
 * @param integer $view_id the unique view ID
 * @return array Returns array with indexes:<br/>
 *               [0]: true/false (success / failure)<br/>
 *               [1]: message string<br/>
 */
function ft_delete_view($view_id)
{
  global $g_table_prefix, $LANG;

  $view_info = ft_get_view($view_id);

  mysql_query("DELETE FROM {$g_table_prefix}client_views WHERE view_id = $view_id");
  mysql_query("DELETE FROM {$g_table_prefix}view_columns WHERE view_id = $view_id");
  mysql_query("DELETE FROM {$g_table_prefix}view_fields WHERE view_id = $view_id");
  mysql_query("DELETE FROM {$g_table_prefix}view_filters WHERE view_id = $view_id");
  mysql_query("DELETE FROM {$g_table_prefix}view_tabs WHERE view_id = $view_id");
  mysql_query("DELETE FROM {$g_table_prefix}public_view_omit_list WHERE view_id = $view_id");
  mysql_query("DELETE FROM {$g_table_prefix}list_groups WHERE group_type = 'view_fields_$view_id'");
  mysql_query("DELETE FROM {$g_table_prefix}email_template_edit_submission_views WHERE view_id = $view_id");
  mysql_query("DELETE FROM {$g_table_prefix}email_template_when_sent_views WHERE view_id = $view_id");
  mysql_query("DELETE FROM {$g_table_prefix}new_view_submission_defaults WHERE view_id = $view_id");
  mysql_query("DELETE FROM {$g_table_prefix}views WHERE view_id = $view_id");

  // hmm... This should be handled better: the user needs to be notified prior to deleting a View to describe all the dependencies
  mysql_query("UPDATE {$g_table_prefix}email_templates SET limit_email_content_to_fields_in_view = NULL WHERE limit_email_content_to_fields_in_view = $view_id");

  $success = true;
  $message = $LANG["notify_view_deleted"];
  extract(ft_process_hook_calls("end", compact("view_id"), array("success", "message")), EXTR_OVERWRITE);

  return array($success, $message);
}


/**
 * Deletes an individual View field. Called when a field is deleted.
 *
 * @param integer $view_id
 * @param integer $field_id
 */
function ft_delete_view_field($view_id, $field_id)
{
  global $g_table_prefix;

  mysql_query("DELETE FROM {$g_table_prefix}view_columns WHERE view_id = $view_id AND field_id = $field_id");
  mysql_query("DELETE FROM {$g_table_prefix}view_fields WHERE view_id = $view_id AND field_id = $field_id");
  mysql_query("DELETE FROM {$g_table_prefix}view_filters WHERE view_id = $view_id AND field_id = $field_id");

  // now update the view field order to ensure there are no gaps
  ft_auto_update_view_field_order($view_id);
}


/**
 * This function is called any time a form field is deleted, or unassigned to the View. It ensures
 * there are no gaps in the view_order
 *
 * @param integer $view_id
 */
function ft_auto_update_view_field_order($view_id)
{
  global $g_table_prefix;

  // we rely on this function returning the field by list_order
  $view_fields = ft_get_view_fields($view_id);

  $count = 1;
  foreach ($view_fields as $field_info)
  {
    $field_id = $field_info["field_id"];

    mysql_query("
      UPDATE {$g_table_prefix}view_fields
      SET    list_order = $count
      WHERE  view_id = $view_id AND
             field_id = $field_id
        ");
    $count++;
  }
}


/**
 * Returns a list of all clients associated with a particular View.
 *
 * @param integer $view_id the unique View ID
 * @return array $info an array of arrays, each containing the user information.
 */
function ft_get_view_clients($view_id)
{
  global $g_table_prefix;

  $account_query = mysql_query("
    SELECT *
    FROM   {$g_table_prefix}client_views cv, {$g_table_prefix}accounts a
    WHERE  cv.view_id = $view_id
    AND    cv.account_id = a.account_id
           ");

  $account_info = array();
  while ($account = mysql_fetch_assoc($account_query))
    $account_info[] = $account;

  extract(ft_process_hook_calls("end", compact("account_info"), array("account_info")), EXTR_OVERWRITE);

  return $account_info;
}


/**
 * Called by administrators on the main View tab. This updates the orders and the grouping
 * of the all form Views.
 *
 * @param integer $form_id the form ID
 * @param array $info the form contents
 * @return array Returns array with indexes:<br/>
 *               [0]: true/false (success / failure)<br/>
 *               [1]: message string<br/>
 */
function ft_update_views($form_id, $info)
{
  global $g_table_prefix, $LANG;

  $sortable_id = $info["sortable_id"];
  $grouped_info = explode("~", $info["{$sortable_id}_sortable__rows"]);
  $new_groups   = explode(",", $info["{$sortable_id}_sortable__new_groups"]);

  $ordered_group_ids = array();
  $new_group_order = 1;
  foreach ($grouped_info as $curr_grouped_info)
  {
    list($curr_group_id, $ordered_view_ids_str) = explode("|", $curr_grouped_info);
    $ordered_view_ids = explode(",", $ordered_view_ids_str);
    $group_name = $info["group_name_{$curr_group_id}"];

    @mysql_query("
      UPDATE {$g_table_prefix}list_groups
      SET    group_name = '$group_name',
             list_order = $new_group_order
      WHERE  group_id = $curr_group_id
        ");
    $new_group_order++;

    $order = 1;
    foreach ($ordered_view_ids as $view_id)
    {
      $is_new_sort_group = (in_array($view_id, $new_groups)) ? "yes" : "no";
      mysql_query("
        UPDATE {$g_table_prefix}views
        SET	   view_order = $order,
               group_id = $curr_group_id,
               is_new_sort_group = '$is_new_sort_group'
        WHERE  view_id = $view_id
          ");

      $order++;
    }
  }

  // return success
  return array(true, $LANG["notify_form_views_updated"]);
}


/**
 * Updates a single View, called from the Edit View page. This function updates all aspects of the
 * View from the overall settings, field list and custom filters.
 *
 * @param integer $view_id the unique View ID
 * @param array $infohash a hash containing the contents of the Edit View page
 * @return array Returns array with indexes:<br/>
 *               [0]: true/false (success / failure)<br/>
 *               [1]: message string<br/>
 */
function ft_update_view($view_id, $info)
{
  global $LANG;

  // update each of the tabs. Be nice to only update the changed ones...
  _ft_update_view_main_settings($view_id, $info);
  _ft_update_view_columns_settings($view_id, $info);
  _ft_update_view_field_settings($view_id, $info);
  _ft_update_view_tab_settings($view_id, $info);
  _ft_update_view_filter_settings($view_id, $info);

  $success = true;
  $message = $LANG["notify_view_updated"];
  extract(ft_process_hook_calls("end", compact("view_id", "info"), array("success", "message")), EXTR_OVERWRITE);

  return array($success, $message);
}


/**
 * Retrieves all filters for a View. If you just want the SQL, use ft_get_view_filter_sql instead, which
 * returns an array of the SQL needed to query the form table. This function returns all info about the
 * filter.
 *
 * @param integer $client_id The unique user ID
 * @param string $filter_type "standard" or "client_map". If left blank (or set to "all") it returns all
 *      View filters.
 * @return array This function returns an array of multi-dimensional arrays of hashes.
 *      Each index of the main array contains the filters for
 */
function ft_get_view_filters($view_id, $filter_type = "all")
{
  global $g_table_prefix;

  $filter_type_clause = "";
  if ($filter_type == "standard")
    $filter_type_clause = "AND filter_type = 'standard'";
  else if ($filter_type == "client_map")
    $filter_type_clause = "AND filter_type = 'client_map'";

  $result = mysql_query("
    SELECT *
    FROM   {$g_table_prefix}view_filters
    WHERE  view_id = $view_id
           $filter_type_clause
    ORDER BY filter_id
      ");

  $infohash = array();
  while ($filter = mysql_fetch_assoc($result))
    $infohash[] = $filter;

  return $infohash;
}


/**
 * Returns an array of SQL filters for a View.
 *
 * @param integer $view_id
 * @return array
 */
function ft_get_view_filter_sql($view_id)
{
  global $g_table_prefix;

  $is_client_account = (isset($_SESSION["ft"]["account"]["account_type"]) &&
    $_SESSION["ft"]["account"]["account_type"] == "client") ? true : false;

  $placeholders = array();
  if ($is_client_account)
  {
    $account_info = $_SESSION["ft"]["account"];

    $placeholders = array(
      "account_id"   => $account_info["account_id"],
      "first_name"   => $account_info["first_name"],
      "last_name"    => $account_info["last_name"],
      "email"        => $account_info["email"],
      "settings__company_name" => $account_info["settings"]["company_name"]
    );
  }

  extract(ft_process_hook_calls("start", compact("placeholders", "is_client_account"), array("placeholders", "is_client_account")), EXTR_OVERWRITE);

  $result = mysql_query("
    SELECT filter_type, filter_sql
    FROM   {$g_table_prefix}view_filters
    WHERE  view_id = $view_id
    ORDER BY filter_id
      ");

  $infohash = array();
  while ($filter = mysql_fetch_assoc($result))
  {
    if ($filter["filter_type"] == "standard")
      $infohash[] = $filter["filter_sql"];
    else
    {
      // if this is a client account, evaluate the Client Map placeholders
      if ($is_client_account)
      {
        $infohash[] = ft_eval_smarty_string($filter["filter_sql"], $placeholders);
      }
    }
  }

  return $infohash;
}


/**
 * Returns an ordered hash of view_id => view name, for a particular form. NOT paginated. If the
 * second account ID is left blank, it's assumed that this is an administrator account doing the
 * calling, and all Views are returned.
 *
 * @param integer $form_id the unique form ID
 * @param integer $user_id the unique user ID (or empty, for administrators)
 * @return array an ordered hash of view_id => view name
 */
function ft_get_form_views($form_id, $account_id = "")
{
  global $g_table_prefix;

  $view_hash = array();

  if (!empty($account_id))
  {
    $query = mysql_query("
      SELECT v.*
      FROM   {$g_table_prefix}views v, {$g_table_prefix}list_groups lg
      WHERE  v.form_id = $form_id AND
             v.group_id = lg.group_id AND
             (v.access_type = 'public' OR
              v.view_id IN (SELECT cv.view_id FROM {$g_table_prefix}client_views cv WHERE account_id = '$account_id'))
      ORDER BY lg.list_order, v.view_order
    ");

    // now run through the omit list, just to confirm this client isn't on it!
    while ($row = mysql_fetch_assoc($query))
    {
      $view_id = $row["view_id"];

      if ($row["access_type"] == "public")
      {
        $omit_list = ft_get_public_view_omit_list($view_id);
        if (in_array($account_id, $omit_list))
          continue;
      }

      $view_hash[] = $row;
    }
  }
  else
  {
    $query = mysql_query("
      SELECT *
      FROM   {$g_table_prefix}views v, {$g_table_prefix}list_groups lg
      WHERE  v.form_id = $form_id AND
             v.group_id = lg.group_id
      ORDER BY lg.list_order, v.view_order
        ");

    while ($row = mysql_fetch_assoc($query))
      $view_hash[] = $row;
  }

  extract(ft_process_hook_calls("end", compact("view_hash"), array("view_hash")), EXTR_OVERWRITE);

  return $view_hash;
}


/**
 * Returns all Views for a form, grouped appropriately. This function introduces a new way of handling
 * loads of optional params (should have implemented this a long time ago!). The second $custom_params
 *
 * @param integer $form_id
 * @param array a hash with any of the following keys:
 *                       account_id => if this is specified, the results will only return View groups
 *                                     that have Views that a client account has access to
 *                       omit_empty_groups => (default: false)
 *                       omit_hidden_views => (default: false)
 *                       include_client => (default: false). If yes, returns assorted client information
 *                             for those that are mapped to the View
 * @param boolean $omit_empty_groups
 */
function ft_get_grouped_views($form_id, $custom_params = array())
{
  global $g_table_prefix;

  // figure out what settings
  $params = array(
    "account_id"        => (isset($custom_params["account_id"])) ? $custom_params["account_id"] : "",
    "omit_empty_groups" => (isset($custom_params["omit_empty_groups"])) ? $custom_params["omit_empty_groups"] : true,
    "omit_hidden_views" => (isset($custom_params["omit_hidden_views"])) ? $custom_params["omit_hidden_views"] : false,
    "include_clients"   => (isset($custom_params["include_clients"])) ? $custom_params["include_clients"] : false
  );

  $group_query = mysql_query("
    SELECT group_id, group_name
    FROM   {$g_table_prefix}list_groups lg
    WHERE  group_type = 'form_{$form_id}_view_group'
    ORDER BY lg.list_order
  ");

  $info = array();
  while ($row = mysql_fetch_assoc($group_query))
  {
    $group_id = $row["group_id"];

    $hidden_views_clause = ($params["omit_hidden_views"]) ? " AND v.access_type != 'hidden'" : "";
    if (empty($params["account_id"]))
    {
      $view_query = mysql_query("
        SELECT *
        FROM   {$g_table_prefix}views v
        WHERE  v.group_id = $group_id
               $hidden_views_clause
        ORDER BY v.view_order
      ");
    }
    else
    {
      $view_query = mysql_query("
        SELECT v.*
        FROM   {$g_table_prefix}views v
        WHERE  v.form_id = $form_id AND
               v.group_id = $group_id AND
               (v.access_type = 'public' OR v.view_id IN (
                  SELECT cv.view_id
                  FROM   {$g_table_prefix}client_views cv
                  WHERE  account_id = {$params["account_id"]}
               )) AND
               v.view_id NOT IN (
                  SELECT view_id
                  FROM   {$g_table_prefix}public_view_omit_list
                  WHERE  account_id = {$params["account_id"]}
               )
               $hidden_views_clause
        ORDER BY v.view_order
          ");
    }

    $views = array();
    while ($view_info = mysql_fetch_assoc($view_query))
    {
      $view_id = $view_info["view_id"];
      if ($params["include_clients"])
      {
        $view_info["client_info"]      = ft_get_view_clients($view_id);
        $view_info["client_omit_list"] = ft_get_public_view_omit_list($view_id);
      }

      $view_info["columns"] = ft_get_view_columns($view_id);
      $view_info["fields"]  = ft_get_view_fields($view_id);
      $view_info["tabs"]    = ft_get_view_tabs($view_id, true);
      $view_info["filters"] = ft_get_view_filters($view_id, "all");
      $views[] = $view_info;
    }

    if (count($views) > 0 || !$params["omit_empty_groups"])
    {
      $curr_group = array(
        "group" => $row,
        "views" => $views
      );
      $info[] = $curr_group;
    }
  }

  return $info;
}


// -----------------------------------------------------------------------------------------------------

/**
 * Helper function to return all editable field IDs in a View. This is used for security purposes
 * to ensure that anyone editing a form submission can't hack it and send along fake values for
 * fields that don't appear in the form.
 *
 * @param integer $view_id
 * @return array a list of field IDs
 */
function _ft_get_editable_view_fields($view_id)
{
  global $g_table_prefix;

  $query = mysql_query("
    SELECT field_id
    FROM   {$g_table_prefix}view_fields
    WHERE  is_editable = 'yes' AND
           view_id = $view_id
      ");

  $field_ids = array();
  while ($row = mysql_fetch_assoc($query))
    $field_ids[] = $row["field_id"];

  return $field_ids;
}


/**
 * Called by the ft_update_view function; updates the main settings of the View (found on the
 * first tab). Also updates the may_edit_submissions setting found on the second tab.
 *
 * @param integer $view_id
 * @param array $info
 */
function _ft_update_view_main_settings($view_id, $info)
{
  global $g_table_prefix;

  $view_name = ft_sanitize($info["view_name"]);

  $num_submissions_per_page = isset($info["num_submissions_per_page"]) ? $info["num_submissions_per_page"] : 10;
  $default_sort_field       = $info["default_sort_field"];
  $default_sort_field_order = $info["default_sort_field_order"];
  $access_type              = $info["access_type"];
  $may_delete_submissions   = $info["may_delete_submissions"];
  $may_add_submissions      = $info["may_add_submissions"];
  $may_edit_submissions     = isset($info["may_edit_submissions"]) ? "yes" : "no"; // (checkbox field)

  // do a little error checking on the num submissions field. If it's invalid, just set to to 10 without
  // informing them - it's not really necessary, I don't think
  if (!is_numeric($num_submissions_per_page))
    $num_submissions_per_page = 10;

  $result = mysql_query("
    UPDATE {$g_table_prefix}views
    SET 	 access_type = '$access_type',
           view_name = '$view_name',
           num_submissions_per_page = $num_submissions_per_page,
           default_sort_field = '$default_sort_field',
           default_sort_field_order = '$default_sort_field_order',
           may_delete_submissions = '$may_delete_submissions',
           may_edit_submissions = '$may_edit_submissions',
           may_add_submissions = '$may_add_submissions'
    WHERE  view_id = $view_id
      ");


  switch ($access_type)
  {
    case "admin":
      mysql_query("DELETE FROM {$g_table_prefix}client_views WHERE view_id = $view_id");
      mysql_query("DELETE FROM {$g_table_prefix}public_view_omit_list WHERE view_id = $view_id");
      break;

    case "public":
      mysql_query("DELETE FROM {$g_table_prefix}client_views WHERE view_id = $view_id");
      break;

    case "private":
      $selected_user_ids = isset($info["selected_user_ids"]) ? $info["selected_user_ids"] : array();
      mysql_query("DELETE FROM {$g_table_prefix}client_views WHERE view_id = $view_id");
      foreach ($selected_user_ids as $client_id)
        mysql_query("INSERT INTO {$g_table_prefix}client_views (account_id, view_id) VALUES ($client_id, $view_id)");

      mysql_query("DELETE FROM {$g_table_prefix}public_view_omit_list WHERE view_id = $view_id");
      break;

    case "hidden":
      mysql_query("DELETE FROM {$g_table_prefix}client_views WHERE view_id = $view_id");
      mysql_query("DELETE FROM {$g_table_prefix}public_view_omit_list WHERE view_id = $view_id");
      break;
  }

  // lastly, add in any default values for new submissions
  mysql_query("DELETE FROM {$g_table_prefix}new_view_submission_defaults WHERE view_id = $view_id");

  if (!empty($info["new_submissions"]) && $may_add_submissions == "yes")
  {
    $default_values = array_combine($info["new_submissions"], $info["new_submissions_vals"]);

    $insert_statements = array();
    $order = 1;
    while (list($field_id, $value) = each($default_values))
    {
      $value = ft_sanitize($value);
      $insert_statements[] = "($view_id, $field_id, '$value', $order)";
      $order++;
    }
    $insert_statement_str = implode(",\n", $insert_statements);

    $query = mysql_query("
      INSERT INTO {$g_table_prefix}new_view_submission_defaults (view_id, field_id, default_value, list_order)
      VALUES $insert_statement_str
    ");
  }
}


function _ft_update_view_columns_settings($view_id, $info)
{
  global $g_table_prefix;

  $sortable_id  = $info["submission_list_sortable_id"];
  $sortable_rows = explode(",", $info["{$sortable_id}_sortable__rows"]);

  mysql_query("DELETE FROM {$g_table_prefix}view_columns WHERE view_id = $view_id");

  $insert_statements = array();
  $list_order = 1;
  foreach ($sortable_rows as $row_id)
  {
    // if the user didn't select a field for this row, ignore it
    if (empty($info["field_id_{$row_id}"]))
      continue;

    $field_id     = $info["field_id_{$row_id}"];
    $is_sortable  = (isset($info["is_sortable_{$row_id}"])) ? "yes" : "no";

    $auto_size    = "";
    $custom_width = "";
    if (isset($info["auto_size_{$row_id}"]))
    {
      $auto_size = "yes";
    }
    else
    {
      $auto_size = "no";

      // validate the custom width field
      if (!isset($info["custom_width_{$row_id}"]))
        $auto_size = "yes";
      else
      {
        $custom_width = trim($info["custom_width_{$row_id}"]);
        if (!is_numeric($custom_width))
        {
          $auto_size = "yes";
          $custom_width = "";
        }
      }
    }

    $truncate = $info["truncate_{$row_id}"];

    $insert_statements[] = "($view_id, $field_id, $list_order, '$is_sortable', '$auto_size', '$custom_width', '$truncate')";
    $list_order++;
  }

  if (!empty($insert_statements))
  {
    $insert_statement_str = implode(",\n", $insert_statements);
    $query = mysql_query("
      INSERT INTO {$g_table_prefix}view_columns (view_id, field_id, list_order, is_sortable, auto_size, custom_width, truncate)
      VALUES $insert_statement_str
    ") or die(mysql_error());
  }
}


/**
 * Called by the ft_update_view function; updates the field settings of the View. This covers things like
 * which fields are included in the view, which appear as a column, which are editable and so on.
 *
 * @param integer $view_id
 * @param array $info
 */
function _ft_update_view_field_settings($view_id, $info)
{
  global $g_table_prefix;

  $sortable_id  = $info["view_fields_sortable_id"];
  $grouped_info = explode("~", $info["{$sortable_id}_sortable__rows"]);
  $new_groups   = explode(",", $info["{$sortable_id}_sortable__new_groups"]);

  // empty the old View fields; we're about to update them
  mysql_query("DELETE FROM {$g_table_prefix}view_fields WHERE view_id = $view_id");

  // if there are any deleted groups, delete 'em! (N.B. we're not interested in deleted groups
  // that were just created in the page
  if (isset($info["deleted_groups"]) && !empty($info["deleted_groups"]))
  {
    $deleted_group_ids = explode(",", $info["deleted_groups"]);
    foreach ($deleted_group_ids as $group_id)
    {
      if (preg_match("/^NEW/", $group_id))
        continue;

      mysql_query("DELETE FROM {$g_table_prefix}list_groups WHERE group_id = $group_id");
    }
  }

  $ordered_group_ids = array();
  $new_group_order = 1;
  foreach ($grouped_info as $curr_grouped_info)
  {
    if (empty($curr_grouped_info))
      continue;

    list($curr_group_id, $ordered_field_ids_str) = explode("|", $curr_grouped_info);
    $ordered_field_ids = explode(",", $ordered_field_ids_str);

    $group_name = $info["group_name_{$curr_group_id}"];
    $group_tab  = (isset($info["group_tab_{$curr_group_id}"]) && !empty($info["group_tab_{$curr_group_id}"])) ?
      $info["group_tab_{$curr_group_id}"] : "";

    if (preg_match("/^NEW/", $curr_group_id))
    {
      @mysql_query("
        INSERT INTO {$g_table_prefix}list_groups (group_type, group_name, custom_data, list_order)
        VALUES ('view_fields_{$view_id}', '$group_name', '$group_tab', $new_group_order)
          ");
      $curr_group_id = mysql_insert_id();
    }
    else
    {
      @mysql_query("
        UPDATE {$g_table_prefix}list_groups
        SET    group_name  = '$group_name',
               custom_data = '$group_tab',
               list_order  = $new_group_order
        WHERE  group_id = $curr_group_id
          ");
    }
    $new_group_order++;

    // if the user unchecked the "Allow fields to be edited" checkbox, nothing is passed for this field
    $editable_fields   = (isset($info["editable_fields"])) ? $info["editable_fields"] : array();
    $searchable_fields = (isset($info["searchable_fields"])) ? $info["searchable_fields"] : array();

    $field_order = 1;
    foreach ($ordered_field_ids as $field_id)
    {
      if (empty($field_id) || !is_numeric($field_id))
        continue;

      $is_editable   = (in_array($field_id, $editable_fields)) ? "yes" : "no";
      $is_searchable = (in_array($field_id, $searchable_fields)) ? "yes" : "no";
      $is_new_sort_group = (in_array($field_id, $new_groups)) ? "yes" : "no";

      $query = mysql_query("
        INSERT INTO {$g_table_prefix}view_fields (view_id, field_id, group_id, is_editable,
          is_searchable, list_order, is_new_sort_group)
        VALUES ($view_id, $field_id, $curr_group_id, '$is_editable', '$is_searchable',
          $field_order, '$is_new_sort_group')
          ");
      $field_order++;
    }
  }
}


/**
 * Called by the ft_update_view function; updates the tabs available in the View.
 *
 * @param integer $view_id
 * @param array $info
 * @return array [0]: true/false (success / failure)
 *               [1]: message string
 */
function _ft_update_view_tab_settings($view_id, $info)
{
  global $g_table_prefix, $LANG;

  $info = ft_sanitize($info);

  @mysql_query("UPDATE {$g_table_prefix}view_tabs SET tab_label = '{$info["tabs"][0]}' WHERE view_id = $view_id AND tab_number = 1");
  @mysql_query("UPDATE {$g_table_prefix}view_tabs SET tab_label = '{$info["tabs"][1]}' WHERE view_id = $view_id AND tab_number = 2");
  @mysql_query("UPDATE {$g_table_prefix}view_tabs SET tab_label = '{$info["tabs"][2]}' WHERE view_id = $view_id AND tab_number = 3");
  @mysql_query("UPDATE {$g_table_prefix}view_tabs SET tab_label = '{$info["tabs"][3]}' WHERE view_id = $view_id AND tab_number = 4");
  @mysql_query("UPDATE {$g_table_prefix}view_tabs SET tab_label = '{$info["tabs"][4]}' WHERE view_id = $view_id AND tab_number = 5");
  @mysql_query("UPDATE {$g_table_prefix}view_tabs SET tab_label = '{$info["tabs"][5]}' WHERE view_id = $view_id AND tab_number = 6");

  return array(true, $LANG["notify_form_tabs_updated"]);
}


/**
 * Called by the ft_update_view function; updates the filters assigned to the View.
 *
 * @param integer $view_id
 * @param array $info
 */
function _ft_update_view_filter_settings($view_id, $info)
{
  global $g_table_prefix, $g_debug, $LANG;

  $info = ft_sanitize($info);
  $form_id = $info["form_id"];

  // delete all old filters for this View. The two update view filter functions that follow re-insert
  // the most recent View info
  mysql_query("DELETE FROM {$g_table_prefix}view_filters WHERE view_id = $view_id");

  // get a hash of field_id => col name for use in building the SQL statements
  $form_fields = ft_get_form_fields($form_id, array("include_field_type_info" => true));
  $field_columns = array();
  for ($i=0; $i<count($form_fields); $i++)
  {
    $field_columns[$form_fields[$i]["field_id"]] = array(
      "col_name"      => $form_fields[$i]["col_name"],
      "is_date_field" => $form_fields[$i]["is_date_field"]
    );
  }

  $standard_filter_errors   = _ft_update_view_standard_filters($view_id, $info, $field_columns);
  $client_map_filter_errors = _ft_update_view_client_map_filters($view_id, $info, $field_columns);

  if (empty($standard_filter_errors) && empty($client_map_filter_errors))
    return array(true, $LANG["notify_filters_updated"]);
  else
  {
    $success = false;
    $message = $LANG["notify_filters_not_updated"];

    $errors = array_merge($standard_filter_errors, $client_map_filter_errors);

    if ($g_debug)
    {
      array_walk($errors, create_function('&$el','$el = "&bull;&nbsp; " . $el;'));
      $message .= "<br /><br />" . join("<br />", $errors);
    }

    return array($success, $message);
  }
}


/**
 * A helper function, called by _ft_update_view_filter_settings. This updates the standard filters for a View.
 */
function _ft_update_view_standard_filters($view_id, $info, $field_columns)
{
  global $g_table_prefix;

  // note that we call this MAX_standard_filters, not num_standard_filters. This is because
  // the value passed from the page may not be accurate. The JS doesn't reorder everything when
  // the user deletes a row, so the value passed is the total number of rows that CAN be passed. Some rows
  // may be empty, though
  $max_standard_filters = $info["num_standard_filters"];
  $errors = array();

  // stores the actual number of standard filters added
  $num_standard_filters = 0;

  // loop through all standard filters and add each to the database
  for ($i=1; $i<=$max_standard_filters; $i++)
  {
    // if this filter doesn't have a field specified, just ignore the row
    if (!isset($info["standard_filter_{$i}_field_id"]) || empty($info["standard_filter_{$i}_field_id"]))
      continue;

    $field_id = $info["standard_filter_{$i}_field_id"];
    $col_name = $field_columns[$field_id]["col_name"];
    $values   = "";

    // date field
    if ($field_columns[$field_id]["is_date_field"] == "yes")
    {
      $values   = $info["standard_filter_{$i}_filter_date_values"];
      $operator = $info["standard_filter_{$i}_operator_date"];

      // build the SQL statement
      $sql_operator = ($operator == "after") ? ">" : "<";
      $sql = "$col_name $sql_operator '$values'";
    }
    else
    {
      $values   = $info["standard_filter_{$i}_filter_values"];
      $operator = $info["standard_filter_{$i}_operator"];

      // build the SQL statement(s)
      $sql_operator = "";
      switch ($operator)
      {
        case "equals":
          $sql_operator = "=";
          $null_test = "IS NULL";
          $join = " OR ";
          break;
        case "not_equals":
          $sql_operator = "!=";
          $null_test = "IS NOT NULL";
          $join = " AND ";
          break;
        case "like":
          $sql_operator = "LIKE";
          $null_test = "IS NULL";
          $join = " OR ";
          break;
        case "not_like":
          $sql_operator = "NOT LIKE";
          $null_test = "IS NOT NULL";
          $join = " AND ";
          break;
      }

      $sql_statements_arr = array();
      $values_arr = explode("|", $values);
      foreach ($values_arr as $value)
      {
        // if this is a LIKE operator (not_like, like), wrap the value in %..%
        $escaped_value = $value;
        if ($operator == "like" || $operator == "not_like")
          $escaped_value = "%$value%";

        $trimmed_value = trim($value);

        // NOT LIKE and != need to be handled separately. By default, Form Tools sets new blank field values to NULL.
        // But SQL queries that test for != "Yes" or NOT LIKE "Yes" should intuitively return ALL results without
        // "Yes" - and that includes NULL values. So, we need to add an additional check to also return null values
        if ($operator == "not_like" || $operator == "not_equals")
        {
          // empty string being searched AGAINST; i.e. checking the field is NOT empty or LIKE empty
          if (empty($trimmed_value))
            $sql_statements_arr[] = "$col_name $sql_operator '$escaped_value' AND $col_name IS NOT NULL";
          else
            $sql_statements_arr[] = "$col_name $sql_operator '$escaped_value' OR $col_name IS NULL";
        }
        else
        {
          // if the value is EMPTY, we need to add an additional IS NULL / IS NOT NULL check
          if (empty($trimmed_value))
            $sql_statements_arr[] = "$col_name $sql_operator '$escaped_value' OR $col_name $null_test";
          else
            $sql_statements_arr[] = "$col_name $sql_operator '$escaped_value'";
        }
      }

      $sql = join($join, $sql_statements_arr);
    }
    $sql = "(" . addslashes($sql) . ")";

    $query = mysql_query("
      INSERT INTO {$g_table_prefix}view_filters (view_id, filter_type, field_id, operator, filter_values, filter_sql)
      VALUES      ($view_id, 'standard', $field_id, '$operator', '$values', '$sql')
        ");

    if (!$query)
      $errors[] = mysql_error();
    else
      $num_standard_filters++;
  }

  // keep track of whether this View has a standard filter or not
  $has_standard_filter = "no";
  if ($num_standard_filters > 0)
    $has_standard_filter = "yes";

  @mysql_query("UPDATE {$g_table_prefix}views SET has_standard_filter = '$has_standard_filter' WHERE view_id = $view_id");

  return $errors;
}


function _ft_update_view_client_map_filters($view_id, $info, $field_columns)
{
  global $g_table_prefix;

  // note that we call this MAX_client_map_filters, not num_client_map_filters. This is because
  // the value passed from the page may not be accurate. The JS doesn't reorder everything when
  // the user deletes a row, so the value passed is the total number of rows that CAN be passed. Some rows
  // may be empty, though
  $max_client_map_filters = $info["num_client_map_filters"];
  $errors = array();

  // stores the actual number of client map filters added
  $num_client_map_filters = 0;

  // loop through all client map filters and add each to the database
  for ($i=1; $i<=$max_client_map_filters; $i++)
  {
    // if this filter doesn't have a field or a client field specified,
    if (!isset($info["client_map_filter_{$i}_field_id"]) || empty($info["client_map_filter_{$i}_field_id"]) ||
        !isset($info["client_map_filter_{$i}_client_field"]) || empty($info["client_map_filter_{$i}_client_field"]))
      continue;

    $field_id     = $info["client_map_filter_{$i}_field_id"];
    $operator     = $info["client_map_filter_{$i}_operator"];
    $client_field = $info["client_map_filter_{$i}_client_field"];

    // build the SQL statement(s)
    $sql_operator = "";
    switch ($operator)
    {
      case "equals":
        $sql_operator = "=";
        $null_test = "IS NULL";
        $join = " OR ";
        break;
      case "not_equals":
        $sql_operator = "!=";
        $null_test = "IS NOT NULL";
        $join = " AND ";
        break;
      case "like":
        $sql_operator = "LIKE";
        $null_test = "IS NULL";
        $join = " OR ";
        break;
      case "not_like":
        $sql_operator = "NOT LIKE";
        $null_test = "IS NOT NULL";
        $join = " AND ";
        break;
    }

    $col_name = $field_columns[$field_id]["col_name"];
    $original_client_field = $client_field;

    // now we're going to build the actual SQL query that contains the Smarty placeholders for the account info.
    // first, convert the client field name to a Smarty variable.
    $sql_client_field = "{\$$client_field}";

    // second, if this is a LIKE operator (not_like, like), wrap the value even further with a %...%
    if ($operator == "like" || $operator == "not_like")
      $sql_client_field = "%$sql_client_field%";

    $sql = addslashes("($col_name $sql_operator '$sql_client_field')");

    $query = mysql_query("
      INSERT INTO {$g_table_prefix}view_filters (view_id, filter_type, field_id, operator, filter_values, filter_sql)
      VALUES      ($view_id, 'client_map', $field_id, '$operator', '$original_client_field', '$sql')
        ");

    if (!$query)
      $errors[] = mysql_error();
    else
      $num_client_map_filters++;
  }

  // keep track of whether this View has a client map filter or not
  $has_client_map_filter = "no";
  if ($num_client_map_filters > 0)
    $has_client_map_filter = "yes";

  @mysql_query("UPDATE {$g_table_prefix}views SET has_client_map_filter = '$has_client_map_filter' WHERE view_id = $view_id");

  return $errors;
}


/**
 * Returns an array of account IDs of those clients in the omit list for this public View.
 *
 * @param integer $view_id
 * @return array
 */
function ft_get_public_view_omit_list($view_id)
{
  global $g_table_prefix;

  $query = mysql_query("
    SELECT account_id
    FROM   {$g_table_prefix}public_view_omit_list
    WHERE  view_id = $view_id
      ");

  $client_ids = array();
  while ($row = mysql_fetch_assoc($query))
    $client_ids[] = $row["account_id"];

  return $client_ids;
}


/**
 * Called by the administrator only. Updates the list of clients on a public View's omit list.
 *
 * @param array $info
 * @param integer $view_id
 * @return array [0] T/F, [1] message
 */
function ft_update_public_view_omit_list($info, $view_id)
{
  global $g_table_prefix, $LANG;

  mysql_query("DELETE FROM {$g_table_prefix}public_view_omit_list WHERE view_id = $view_id");

  $client_ids = (isset($info["selected_client_ids"])) ? $info["selected_client_ids"] : array();
  foreach ($client_ids as $account_id)
  {
    mysql_query("INSERT INTO {$g_table_prefix}public_view_omit_list (view_id, account_id) VALUES ($view_id, $account_id)");
  }

  return array(true, $LANG["notify_public_view_omit_list_updated"]);
}


/**
 * Caches the total number of (finalized) submissions in a particular form - or all forms -
 * in the $_SESSION["ft"]["form_{$form_id}_num_submissions"] key. That value is used on the administrators
 * main Forms page to list the form submission count.
 *
 * @param integer $form_id
 */
function _ft_cache_view_stats($form_id, $view_id = "")
{
  global $g_table_prefix;

  $view_ids = array();
  if (empty($view_id))
    $view_ids = ft_get_view_ids($form_id);
  else
    $view_ids[] = $view_id;

  foreach ($view_ids as $view_id)
  {
    $filters = ft_get_view_filter_sql($view_id);

    // if there aren't any filters, just set the submission count & first submission date to the same
    // as the parent form
    if (empty($filters))
    {
      $_SESSION["ft"]["view_{$view_id}_num_submissions"] = $_SESSION["ft"]["form_{$form_id}_num_submissions"];
    }
    else
    {
      $filter_clause = join(" AND ", $filters);

      $count_query = mysql_query("
        SELECT count(*) as c
        FROM   {$g_table_prefix}form_$form_id
        WHERE  is_finalized = 'yes' AND
        $filter_clause
          ")
          or ft_handle_error("Failed query in <b>" . __FUNCTION__ . "</b>, line " . __LINE__, mysql_error());

      $info = mysql_fetch_assoc($count_query);
      $_SESSION["ft"]["view_{$view_id}_num_submissions"] = $info["c"];
    }
  }
}


/**
 * A very simple getter function that retrieves an an ordered array of view_id => view name hashes for a
 * particular form.
 *
 * @param integer $form_id
 * @return array
 */
function ft_get_view_list($form_id)
{
  global $g_table_prefix;

  $query = mysql_query("
    SELECT view_id, view_name
    FROM   {$g_table_prefix}views
    WHERE  form_id = $form_id
    ORDER BY view_order
      ") or dir(mysql_error());

  $result = array();
  while ($row = mysql_fetch_assoc($query))
    $result[] = $row;

  extract(ft_process_hook_calls("end", compact("form_id", "result"), array("result")), EXTR_OVERWRITE);

  return $result;
}


/**
 * Used internally. This is called to figure out which View should be used by default. It actually
 * just picks the first on in the list of Views.
 *
 * @param integer $form_id
 * @return mixed $view_id the View ID or the empty string if no Views associated with form.
 */
function ft_get_default_view($form_id)
{
  global $g_table_prefix;

  $query = mysql_query("
    SELECT view_id
    FROM   {$g_table_prefix}views
    WHERE  form_id = $form_id
    ORDER BY view_order
    LIMIT 1
      ");

  $view_id = "";
  $view_info = mysql_fetch_assoc($query);

  if (!empty($view_info))
    $view_id = $view_info["view_id"];

  return $view_id;
}


/**
 * This feature was added in 2.1.0 - it lets administrators define default values for all new submissions
 * created with the View. This was added to solve a problem where submissions were created in a View where
 * that new submission wouldn't meet the criteria for inclusion. But beyond that, this is a handy feature to
 * cut down on configuration time for new data sets.
 *
 * @param $view_id
 * @return array
 */
function ft_get_new_view_submission_defaults($view_id)
{
  global $g_table_prefix;

  $query = mysql_query("
    SELECT *
    FROM {$g_table_prefix}new_view_submission_defaults
    WHERE view_id = $view_id
    ORDER BY list_order
  ");

  $info = array();
  while ($row = mysql_fetch_assoc($query))
  {
    $info[] = $row;
  }

  return $info;
}


/**
 * This makes a copy of all field groups for a View and returns a hash of old group IDs to new group IDs.
 * It's used in the create View functionality when the user wants to base the new View on an existing
 * one.
 *
 * @param integer $source_view_id
 * @param integer $target_view_id
 * @return array
 */
function ft_duplicate_view_field_groups($source_view_id, $target_view_id)
{
  global $g_table_prefix;

  $query = mysql_query("
    SELECT *
    FROM   {$g_table_prefix}list_groups
    WHERE group_type = 'view_fields_{$source_view_id}'
    ORDER BY list_order
  ");

  $map = array();
  while ($row = mysql_fetch_assoc($query))
  {
    $row = ft_sanitize($row);
    $group_id    = $row["group_id"];
    $group_type  = "view_fields_{$target_view_id}";
    $group_name  = $row["group_name"];
    $custom_data = $row["custom_data"];
    $list_order  = $row["list_order"];

    mysql_query("
      INSERT INTO {$g_table_prefix}list_groups (group_type, group_name, custom_data, list_order)
      VALUES ('$group_type', '$group_name', '$custom_data', $list_order)
    ") or die(mysql_error());
    $map[$group_id] = mysql_insert_id();
  }

  return $map;
}


/**
 * Helper function that's called when creating new Views. It populates the View fields and View column
 * with ALL form fields and 5 columns (Submission ID, Submission Date + 3 others).
 *
 * @param integer $form_id
 * @param integer $view_id
 */
function _ft_populate_new_view_fields($form_id, $view_id)
{
  global $g_table_prefix, $LANG;

  mysql_query("
    INSERT INTO {$g_table_prefix}list_groups (group_type, group_name, custom_data, list_order)
    VALUES ('view_fields_$view_id', '{$LANG["phrase_default_tab_label"]}', 1, 1)
  ");
  $view_fields_group_id = mysql_insert_id();

  $count = 1;
  $num_custom_fields_added = 0;
  $form_fields = ft_get_form_fields($form_id);

  $form_field_view_inserts = array();
  $view_column_inserts     = array();
  $view_column_order = 1;
  foreach ($form_fields as $field)
  {
    $field_id = $field["field_id"];

    // make the submission ID, submission date and the 1st 3 columns visible by default
    $is_column   = "no";
    $is_sortable = "no";
    if ($field["col_name"] == "submission_id" || $field["col_name"] == "submission_date")
    {
      $is_column   = "yes";
      $is_sortable = "yes";
    }
    else
    {
      if ($num_custom_fields_added < 3)
      {
        $is_column   = "yes";
        $is_sortable = "yes";
        $num_custom_fields_added++;
      }
    }

    // by default, make every field editable except the system fields
    $is_editable = ($field["is_system_field"] == "yes") ? "no" : "yes";
    $is_new_sort_group = $field["is_new_sort_group"];

    $form_field_view_inserts[] = "($view_id, $field_id, $view_fields_group_id, '$is_editable', $count, '$is_new_sort_group')";
    $count++;

    // if this is a column field, add the view_columns record
    if ($is_column == "yes")
    {
      $auto_size = "yes";
      $custom_width = "";
      if ($field["col_name"] == "submission_id")
      {
        $auto_size    = "no";
        $custom_width = 50;
      }
      else if ($field["col_name"] == "submission_date")
      {
        $auto_size    = "no";
        $custom_width = 160;
      }
      $view_column_inserts[] = "($view_id, $field_id, $view_column_order, 'yes', '$auto_size', '$custom_width', 'truncate')";
      $view_column_order++;
    }
  }

  // should NEVER be empty, but check anyway
  if (!empty($form_field_view_inserts))
  {
    $form_field_view_insert_str = implode(",\n", $form_field_view_inserts);
    mysql_query("
      INSERT INTO {$g_table_prefix}view_fields (view_id, field_id, group_id, is_editable, list_order, is_new_sort_group)
      VALUES $form_field_view_insert_str
    ");
  }
  if (!empty($view_column_inserts))
  {
    $view_columns_insert_str = implode(",\n", $view_column_inserts);
    mysql_query("
      INSERT INTO {$g_table_prefix}view_columns (view_id, field_id, list_order, is_sortable, auto_size, custom_width, truncate)
      VALUES $view_columns_insert_str
    ");
  }
}
