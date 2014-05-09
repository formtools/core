<?php

/**
 * This file contains all functions relating to form Views.
 *
 * @copyright Encore Web Studios 2011
 * @author Encore Web Studios <formtools@encorewebstudios.com>
 * @package 2-0-6
 * @subpackage Views
 */


// -------------------------------------------------------------------------------------------------


/**
 * This function is called after creating a new form (ft_finalize_form), and creates a default
 * View - one containing all fields and assigned to all clients that are assigned to the form.
 *
 * @param integer $form_id
 */
function ft_add_default_view($form_id)
{
  global $g_table_prefix, $LANG;

  // first, create the new View
  $form_info   = ft_get_form($form_id);
  $form_fields = ft_get_form_fields($form_id);
  $form_name   = $form_info["form_name"];
  $num_submissions_per_page = isset($_SESSION["ft"]["settings"]["num_submissions_per_page"]) ? $_SESSION["ft"]["settings"]["num_submissions_per_page"] : 10;

  mysql_query("
    INSERT INTO {$g_table_prefix}views (form_id, view_name, view_order, num_submissions_per_page,
      default_sort_field, default_sort_field_order)
    VALUES ($form_id, '{$LANG["phrase_all_submissions"]}', '1', $num_submissions_per_page, 'submission_date', 'asc')
      ");
  $view_id = mysql_insert_id();

  // add the default tab
  mysql_query("INSERT INTO {$g_table_prefix}view_tabs (view_id, tab_number, tab_label) VALUES ($view_id, 1, '{$LANG["phrase_default_tab_label"]}')");
  mysql_query("INSERT INTO {$g_table_prefix}view_tabs (view_id, tab_number, tab_label) VALUES ($view_id, 2, '')");
  mysql_query("INSERT INTO {$g_table_prefix}view_tabs (view_id, tab_number, tab_label) VALUES ($view_id, 3, '')");
  mysql_query("INSERT INTO {$g_table_prefix}view_tabs (view_id, tab_number, tab_label) VALUES ($view_id, 4, '')");
  mysql_query("INSERT INTO {$g_table_prefix}view_tabs (view_id, tab_number, tab_label) VALUES ($view_id, 5, '')");
  mysql_query("INSERT INTO {$g_table_prefix}view_tabs (view_id, tab_number, tab_label) VALUES ($view_id, 6, '')");

  // next, assign all the View fields. Since we don't know which fields the user will want as columns, we
  // add submission ID, 3 custom fields & submission date
  $count = 1;
  $num_custom_fields_added = 0;

  foreach ($form_fields as $field)
  {
    $field_id = $field["field_id"];

    // make the submission ID, submission date and the 1st 3 columns visible by default
    $is_column = "no";
    $is_sortable   = "no";
    if ($field["col_name"] == "submission_id" || $field["col_name"] == "submission_date")
    {
      $is_column = "yes";
      $is_sortable   = "yes";
    }
    else
    {
      if ($num_custom_fields_added < 3)
      {
        $is_column = "yes";
        $is_sortable   = "yes";
        $num_custom_fields_added++;
      }
    }

    // by default, make every field editable except Submission ID, Submission Date, Last Modified Date, IP Address.
    // The administrator can manually make them editable later (except Submission ID and Last Modified Date) if they
    // so wish
    $is_editable = "yes";
    if ($field["col_name"] == "submission_id" || $field["col_name"] == "submission_date" ||
        $field["col_name"] == "last_modified_date" || $field["col_name"] == "ip_address")
      $is_editable = "no";

    mysql_query("
      INSERT INTO {$g_table_prefix}view_fields (view_id, field_id, tab_number, is_column, is_sortable, is_editable, list_order)
      VALUES ($view_id, $field_id, 1, '$is_column', '$is_sortable', '$is_editable', $count)
        ");
    $count++;
  }

  // assign the view to all clients attached to this form
  $client_info = $form_info["client_info"];
  foreach ($client_info as $user)
  {
    $account_id = $user["account_id"];
    mysql_query("
      INSERT INTO {$g_table_prefix}client_views (account_id, view_id)
      VALUES ($account_id, $view_id)
        ");
  }
}


/**
 * This checks to see if a View exists in the database.
 *
 * @param integer $view_id
 * @return boolean
 */
function ft_check_view_exists($view_id)
{
  global $g_table_prefix;
  $query = @mysql_query("SELECT * FROM {$g_table_prefix}views WHERE view_id = $view_id");
  return (mysql_num_rows($query) > 0);
}


/**
 * Retrieves all information about a View, including associated user and filter info.
 *
 * @param integer $view_id the unique view ID
 * @return array a hash of view information
 */
function ft_get_view($view_id)
{
  global $g_table_prefix;

  $query = "SELECT * FROM {$g_table_prefix}views WHERE view_id = $view_id";
  $result = mysql_query($query);

  $view_info = mysql_fetch_assoc($result);
  $view_info["client_info"] = ft_get_view_clients($view_id);
  $view_info["fields"]    = ft_get_view_fields($view_id);
  $view_info["filters"]   = ft_get_view_filters($view_id);
  $view_info["tabs"]      = ft_get_view_tabs($view_id);
  $view_info["client_omit_list"] = (isset($view_info["access_type"]) && $view_info["access_type"] == "public") ?
    ft_get_public_view_omit_list($view_id) : array();

  extract(ft_process_hooks("end", compact("view_id", "view_info"), array("view_info")), EXTR_OVERWRITE);

  return $view_info;
}


/**
 * Retrieves a list of all views for a form. It returns either ALL results (by passing in "all" as
 * the second parameter) or a page worth by either leaving the second parameter empty (page = 1) or
 * by specifying a page. Note: for the paginated option, it expects the num Views per page value to
 * be stored in sessions - it requires it to be an ADMINISTRATOR.
 *
 * @param integer $form_id the unique form ID
 * @param mixed $page_num the current page or "all"
 * @return array a hash of view information
 */
function ft_get_views($form_id, $page_num = 1)
{
  global $g_table_prefix;

  if ($page_num == "all")
  {
    $limit_clause = "";
  }
  else
  {
    $num_views_per_page = $_SESSION["ft"]["settings"]["num_views_per_page"];

    // determine the LIMIT clause
    $limit_clause = "";
    if (empty($page_num))
      $page_num = 1;
    $first_item = ($page_num - 1) * $num_views_per_page;
    $limit_clause = "LIMIT $first_item, $num_views_per_page";
  }

  $result = mysql_query("
    SELECT view_id
    FROM 	 {$g_table_prefix}views
    WHERE  form_id = $form_id
    ORDER BY view_order
     $limit_clause
      ");
   $count_result = mysql_query("
    SELECT count(*) as c
    FROM 	 {$g_table_prefix}views
    WHERE  form_id = $form_id
      ");
   $count_hash = mysql_fetch_assoc($count_result);

  $view_info = array();
  while ($row = mysql_fetch_assoc($result))
  {
    $view_id = $row["view_id"];
    $view_info[] = ft_get_view($view_id);
  }

  $return_hash["results"] = $view_info;
  $return_hash["num_results"]  = $count_hash["c"];

  extract(ft_process_hooks("end", compact("return_hash"), array("return_hash")), EXTR_OVERWRITE);

  return $return_hash;
}


/**
 * A simple, fast, no-frills function to return an array of all View IDs for a form, ordered
 * by View Order.
 *
 * @param integer $form_id
 * @return array
 */
function ft_get_view_ids($form_id)
{
  global $g_table_prefix;

  $query = mysql_query("SELECT view_id FROM {$g_table_prefix}views WHERE form_id = $form_id ORDER BY view_order");

  $view_ids = array();
  while ($row = mysql_fetch_assoc($query))
    $view_ids[] = $row["view_id"];

  extract(ft_process_hooks("end", compact("view_ids"), array("view_ids")), EXTR_OVERWRITE);

  return $view_ids;
}


/**
 * This returns the database column names of all searchable fields in this View. To reduce the number of
 * DB queries, this function allows you to pass in all field info to just extract the information from that.
 *
 * @param integer $view_id optional, but if not supplied, the second $fields paramt
 * @param array $fields optional, but if not supplied, the first $view_id param is required. This should
 *   be the $view_info["fields"] key, returned from $view_info = ft_get_view($view_id), which contains all
 *   View field info
 *
 * @return array an array of searchable database column names
 */
function ft_get_view_searchable_fields($view_id = "", $fields = array())
{
  // this should never occur, but just in case
  if (empty($view_id) && empty($fields))
    ft_handle_error("<b>" . __FUNCTION__ . "</b> received invalid input, line " . __LINE__);

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

  extract(ft_process_hooks("end", compact("view_id", "tab_info"), array("tab_info")), EXTR_OVERWRITE);

  return $tab_info;
}


/**
 * Creates a new form View. If the $view_id parameter is set, it makes a copy of that View.
 * Otherwise, it creates a new blank view has *all* fields associated with it by default, a single tab
 * that is not enabled by default, no filters, and no clients assigned to it.
 *
 * TODO check this function works with tabs, filters etc. containing ', " and other chars. Need to re-sanitize?
 *
 * @param integer $form_id the unique form ID
 * @param integer $view_id (optional)
 * @return integer the new view ID
 */
function ft_create_new_view($form_id, $create_from_view_id = "")
{
  global $g_table_prefix, $LANG;

  // figure out the next View order number
  $count_query = mysql_query("SELECT count(*) as c FROM {$g_table_prefix}views WHERE form_id = $form_id");
  $count_hash = mysql_fetch_assoc($count_query);
  $num_form_views = $count_hash["c"];
  $next_order = $num_form_views + 1;

  if (empty($create_from_view_id))
  {
    // add the View with default values
    mysql_query("
      INSERT INTO {$g_table_prefix}views (form_id, view_name, view_order)
      VALUES ($form_id, '{$LANG["phrase_new_view"]}', $next_order)
        ");
    $view_id = mysql_insert_id();

    // add the default tab
    mysql_query("INSERT INTO {$g_table_prefix}view_tabs (view_id, tab_number, tab_label) VALUES ($view_id, 1, '{$LANG["phrase_default_tab_label"]}')");
    mysql_query("INSERT INTO {$g_table_prefix}view_tabs (view_id, tab_number, tab_label) VALUES ($view_id, 2, '')");
    mysql_query("INSERT INTO {$g_table_prefix}view_tabs (view_id, tab_number, tab_label) VALUES ($view_id, 3, '')");
    mysql_query("INSERT INTO {$g_table_prefix}view_tabs (view_id, tab_number, tab_label) VALUES ($view_id, 4, '')");
    mysql_query("INSERT INTO {$g_table_prefix}view_tabs (view_id, tab_number, tab_label) VALUES ($view_id, 5, '')");
    mysql_query("INSERT INTO {$g_table_prefix}view_tabs (view_id, tab_number, tab_label) VALUES ($view_id, 6, '')");
  }
  else
  {
    $view_info = ft_get_view($create_from_view_id);
    $view_info = ft_sanitize($view_info);

    // Main View Settings
    $view_order = $view_info["view_order"];
    $num_submissions_per_page = $view_info["num_submissions_per_page"];
    $default_sort_field       = $view_info["default_sort_field"];
    $default_sort_field_order = $view_info["default_sort_field_order"];
    $has_standard_filter      = $view_info["has_standard_filter"];
    $has_client_map_filter    = $view_info["has_client_map_filter"];

    mysql_query("
      INSERT INTO {$g_table_prefix}views (form_id, view_name, view_order, num_submissions_per_page,
        default_sort_field, default_sort_field_order, has_client_map_filter, has_standard_filter)
      VALUES ($form_id, '{$LANG["phrase_new_view"]}', $next_order, $num_submissions_per_page,
        '$default_sort_field', '$default_sort_field_order', '$has_client_map_filter', '$has_standard_filter')
        ") or die(mysql_error());
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

    // View Fields
    foreach ($view_info["fields"] as $field_info)
    {
      $field_id    = $field_info["field_id"];
      $tab_number  = (!empty($field_info["tab_number"])) ? $field_info["tab_number"] : "NULL";
      $is_column   = $field_info["is_column"];
      $is_editable = $field_info["is_editable"];
      $is_sortable = $field_info["is_sortable"];
      $list_order  = $field_info["list_order"];

      mysql_query("
        INSERT INTO {$g_table_prefix}view_fields (view_id, field_id, tab_number, is_column, is_sortable,
          is_editable, list_order)
        VALUES ($view_id, $field_id, $tab_number, '$is_column', '$is_sortable', '$is_editable', $list_order)
          ") or die(mysql_error());
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
  }

  extract(ft_process_hooks("end", compact("view_id"), array()), EXTR_OVERWRITE);

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
function ft_get_view_field($view_id, $field_id)
{
  global $g_table_prefix;

  $query = mysql_query("
    SELECT vf.*, ft.field_title, ft.col_name
    FROM   {$g_table_prefix}view_fields vf, {$g_table_prefix}form_fields ft
    WHERE  vf.field_id = ft.field_id AND
           view_id = $view_id AND
           vf.field_id = $field_id
      ");

  $result = mysql_fetch_assoc($query);

  return $result;
}


/**
 * Returns all fields in a View.
 *
 * @param integer $view_id the unique View ID
 * @return array $info an array of hashes containing the various view field values.
 */
function ft_get_view_fields($view_id)
{
  global $g_table_prefix;

  $result = mysql_query("
     SELECT field_id
    FROM	 {$g_table_prefix}view_fields
    WHERE  view_id = $view_id
    ORDER BY list_order
      ");

  $fields_info = array();
  while ($field_info = mysql_fetch_assoc($result))
  {
    $field_id = $field_info["field_id"];
    $fields_info[] = ft_get_view_field($view_id, $field_id);
  }

  return $fields_info;
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
 * Deletes a View.
 *
 * @param integer $view_id the unique view ID
 * @return array Returns array with indexes:<br/>
 *               [0]: true/false (success / failure)<br/>
 *               [1]: message string<br/>
 */
function ft_delete_view($view_id)
{
  global $g_table_prefix, $LANG;

  mysql_query("DELETE FROM {$g_table_prefix}client_views WHERE view_id = $view_id");
  mysql_query("DELETE FROM {$g_table_prefix}view_fields WHERE view_id = $view_id");
  mysql_query("DELETE FROM {$g_table_prefix}view_filters WHERE view_id = $view_id");
  mysql_query("DELETE FROM {$g_table_prefix}view_tabs WHERE view_id = $view_id");
  mysql_query("DELETE FROM {$g_table_prefix}public_view_omit_list WHERE view_id = $view_id");
  mysql_query("DELETE FROM {$g_table_prefix}views WHERE view_id = $view_id");

  // reset any email templates that are assigned to this View. We don't delete them outright because
  // it's easy for the administrator to forget about them, and it's a pain having to re-create them.
  // By resetting their View IDs, they're rendered inactive
  mysql_query("
    UPDATE {$g_table_prefix}email_templates
    SET    view_id = NULL
    WHERE  view_id = $view_id
      ");

  $success = true;
  $message = $LANG["notify_view_deleted"];
  extract(ft_process_hooks("end", compact("view_id"), array("success", "message")), EXTR_OVERWRITE);


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

  extract(ft_process_hooks("end", compact("account_info"), array("account_info")), EXTR_OVERWRITE);

  return $account_info;
}


/**
 * Called by administrators on the main View tab. This updates the orders of the entire list of
 * Views. Note: the option to sort the Views only appears if there's > 1 Views.
 *
 * @param integer $form_id the form ID
 * @param array $info the form contents
 * @return array Returns array with indexes:<br/>
 *               [0]: true/false (success / failure)<br/>
 *               [1]: message string<br/>
 */
function ft_update_view_order($form_id, $info)
{
  global $g_table_prefix, $LANG;

  // loop through all the fields in $info that are being re-sorted and compile a list of
  // view_id => order pairs.
  $new_view_orders = array();
  foreach ($info as $key => $value)
  {
    if (preg_match("/^view_(\d+)$/", $key, $match))
    {
      $view_id = $match[1];
      $new_view_orders[$view_id] = $value;
    }
  }

  // okay! Since we may have only updated a *subset* of all Views (since the Views page is
  // arranged in pages), get a list of ALL Views associated with this form, add them to
  // $new_view_orders and sort the entire lot of them in one go
  $view_info = array();
  $query = mysql_query("
    SELECT view_id, view_order
    FROM   {$g_table_prefix}views
    WHERE  form_id = $form_id
      ");
  while ($row = mysql_fetch_assoc($query))
  {
    if (!array_key_exists($row["view_id"], $new_view_orders))
      $new_view_orders[$row["view_id"]] = $row["view_order"];
  }

  // sort by the ORDER (the value - non-key - of the hash)
  asort($new_view_orders);

  $count = 1;
  foreach ($new_view_orders as $view_id => $order)
  {
    mysql_query("
      UPDATE {$g_table_prefix}views
      SET	   view_order = $count
      WHERE  view_id = $view_id
        ");
    $count++;
  }

  // return success
  return array(true, $LANG["notify_form_view_order_updated"]);
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

  // update each of the tabs
  _ft_update_view_main_settings($view_id, $info);
  _ft_update_view_field_settings($view_id, $info);
  _ft_update_view_tab_settings($view_id, $info);
  _ft_update_view_filter_settings($view_id, $info);

  $success = true;
  $message = $LANG["notify_view_updated"];
  extract(ft_process_hooks("end", compact("view_id", "info"), array("success", "message")), EXTR_OVERWRITE);

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
      "account_id" => $account_info["account_id"],
      "first_name" => $account_info["first_name"],
      "last_name" => $account_info["last_name"],
      "email" => $account_info["email"],
      "company_name" => $account_info["settings"]["company_name"]
        );
  }

  extract(ft_process_hooks("start", compact("placeholders", "is_client_account"), array("placeholders", "is_client_account")), EXTR_OVERWRITE);

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
    // TODO this could be more efficient, I think
    $query = mysql_query("
      SELECT v.*
      FROM   {$g_table_prefix}views v
      WHERE  v.form_id = $form_id AND
             (v.access_type = 'public' OR
              v.view_id IN (SELECT cv.view_id FROM {$g_table_prefix}client_views cv WHERE account_id = '$account_id'))
      ORDER BY v.view_order
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
      FROM   {$g_table_prefix}views v
      WHERE  form_id = $form_id
      ORDER BY v.view_order
        ");

    while ($row = mysql_fetch_assoc($query))
      $view_hash[] = $row;
  }

  extract(ft_process_hooks("end", compact("view_hash"), array("view_hash")), EXTR_OVERWRITE);

  return $view_hash;
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
  $may_edit_submissions     = isset($info["may_edit_submissions"]) ? "yes" : "no";
  $may_add_submissions      = $info["may_add_submissions"];

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

  $field_info = array();
  $field_ids = split(",", $info["field_ids"]);

  for ($i=0; $i<count($field_ids); $i++)
  {
    $field_id = $field_ids[$i];
    $field_order   = (isset($info["field_{$field_id}_order"])) ? $info["field_{$field_id}_order"] : "";
    $is_column     = (isset($info["field_{$field_id}_is_column"])) ? true : false;
    $is_sortable   = (isset($info["field_{$field_id}_is_sortable"])) ? true : false;
    $is_editable   = (isset($info["field_{$field_id}_is_editable"])) ? true : false;
    $is_searchable = (isset($info["field_{$field_id}_is_searchable"])) ? true : false;
    $field_tab     = (isset($info["field_{$field_id}_tab"])) ? $info["field_{$field_id}_tab"] : "";

    // check to see if a key with this field order doesn't already exist. If it does, we place it directly
    // AFTER the item. We do this by adding 0.1 to the value. After we ksort() the array, the array will be
    // ordered properly. [Note: the strval() explicitly casts the order as a string, to keep the
    // array_key_exists function happy]
    if (array_key_exists($field_order, $field_info))
    {
      while (array_key_exists("$field_order", $field_info))
        $field_order = strval($field_order + 0.1);
    }

    $field_info[$field_order] = array(
      "field_id" => $field_id,
      "is_sortable" => $is_sortable,
      "is_column" => $is_column,
      "is_editable" => $is_editable,
      "is_searchable" => $is_searchable,
      "field_tab" => $field_tab
        );
  }
  ksort($field_info);

  mysql_query("DELETE FROM {$g_table_prefix}view_fields WHERE view_id = $view_id");
  $order = 1;
  foreach ($field_info as $key => $hash)
  {
    $field_id    = $hash["field_id"];
    $is_column   = ($hash["is_column"]) ? "yes" : "no";
    $is_sortable = ($hash["is_sortable"]) ? "yes" : "no";
    $is_editable = ($hash["is_editable"]) ? "yes" : "no";
    $is_searchable = ($hash["is_searchable"]) ? "yes" : "no";
    $field_tab   = (!empty($hash["field_tab"])) ? $hash["field_tab"] : "NULL";

    mysql_query("
      INSERT INTO {$g_table_prefix}view_fields (view_id, field_id, tab_number, is_column, is_sortable, is_editable, is_searchable, list_order)
      VALUES ($view_id, $field_id, $field_tab, '$is_column', '$is_sortable', '$is_editable', '$is_searchable', $order)
        ");
    $order++;
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

  $tab_label1 = isset($info["tab_label1"]) ? $info["tab_label1"] : "";
  $tab_label2 = isset($info["tab_label2"]) ? $info["tab_label2"] : "";
  $tab_label3 = isset($info["tab_label3"]) ? $info["tab_label3"] : "";
  $tab_label4 = isset($info["tab_label4"]) ? $info["tab_label4"] : "";
  $tab_label5 = isset($info["tab_label5"]) ? $info["tab_label5"] : "";
  $tab_label6 = isset($info["tab_label6"]) ? $info["tab_label6"] : "";

  @mysql_query("UPDATE {$g_table_prefix}view_tabs SET tab_label = '$tab_label1' WHERE view_id = $view_id AND tab_number = 1");
  @mysql_query("UPDATE {$g_table_prefix}view_tabs SET tab_label = '$tab_label2' WHERE view_id = $view_id AND tab_number = 2");
  @mysql_query("UPDATE {$g_table_prefix}view_tabs SET tab_label = '$tab_label3' WHERE view_id = $view_id AND tab_number = 3");
  @mysql_query("UPDATE {$g_table_prefix}view_tabs SET tab_label = '$tab_label4' WHERE view_id = $view_id AND tab_number = 4");
  @mysql_query("UPDATE {$g_table_prefix}view_tabs SET tab_label = '$tab_label5' WHERE view_id = $view_id AND tab_number = 5");
  @mysql_query("UPDATE {$g_table_prefix}view_tabs SET tab_label = '$tab_label6' WHERE view_id = $view_id AND tab_number = 6");

  // empty the tab info stored in sessions
  unset($_SESSION["ft"]["view_{$view_id}_tab_info"]);

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
  $form_fields = ft_get_form_fields($form_id);
  $field_columns = array();
  for ($i=0; $i<count($form_fields); $i++)
    $field_columns[$form_fields[$i]["field_id"]] = $form_fields[$i]["col_name"];

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
    $values   = "";

    // date field
    if ($field_columns[$info["standard_filter_{$i}_field_id"]] == "submission_date" ||
        $field_columns[$info["standard_filter_{$i}_field_id"]] == "last_modified_date")
    {
      $values   = $info["standard_filter_{$i}_filter_date_values"];
      $operator = $info["standard_filter_{$i}_operator_date"];

      // build the SQL statement
      $sql_operator = ($operator == "after") ? ">" : "<";
      $field_name = $field_columns[$field_id];
      $sql = "$field_name $sql_operator '$values'";
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
      $field_name = $field_columns[$field_id];

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
            $sql_statements_arr[] = "$field_name $sql_operator '$escaped_value' OR $field_name IS NOT NULL";
          else
            $sql_statements_arr[] = "$field_name $sql_operator '$escaped_value' OR $field_name IS NULL";
        }
        else
        {
          // if the value is EMPTY, we need to add an additional IS NULL / IS NOT NULL check
          if (empty($trimmed_value))
            $sql_statements_arr[] = "$field_name $sql_operator '$escaped_value' OR $field_name $null_test";
          else
            $sql_statements_arr[] = "$field_name $sql_operator '$escaped_value'";
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

    $field_name = $field_columns[$field_id];
    $original_client_field = $client_field;

    // now we're going to build the actual SQL query that contains the Smarty placeholders for the account info.
    // first, convert the client field name to a Smarty variable.
    $sql_client_field = "{\$$client_field}";

    // second, if this is a LIKE operator (not_like, like), wrap the value even further with a %...%
    if ($operator == "like" || $operator == "not_like")
      $sql_client_field = "%$sql_client_field%";

    $sql = addslashes("($field_name $sql_operator '$sql_client_field')");

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
    mysql_query("INSERT INTO {$g_table_prefix}public_view_omit_list (view_id, account_id) VALUES ($view_id, $account_id)");

  return array(true, $LANG["notify_public_view_omit_list_updated"]);
}


/**
 * Caches the total number of (finalized) submissions in a particular form - or all forms -
 * in the $_SESSION["ft"]["form_{$form_id}_num_submissions"] key. That value is used on the administrators
 * main Forms page to list the form submission count. It also stores the earliest form submission date, which
 * isn't used directly, but it's copied by the _ft_cache_view_stats function for all Views that don't have a
 * filter.
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
      $_SESSION["ft"]["view_{$view_id}_num_submissions"]       = $_SESSION["ft"]["form_{$form_id}_num_submissions"];
      $_SESSION["ft"]["view_{$view_id}_first_submission_date"] = $_SESSION["ft"]["form_{$form_id}_first_submission_date"];
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
      $_SESSION["ft"]["view_{$form_id}_num_submissions"] = $info["c"];

      $first_date_query = mysql_query("
        SELECT submission_date
        FROM   {$g_table_prefix}form_$form_id
        WHERE  is_finalized = 'yes' AND
        $filter_clause
        ORDER BY submission_date ASC
        LIMIT 1
          ")
            or ft_handle_error("Failed query in <b>" . __FUNCTION__ . "</b>, line " . __LINE__, mysql_error());
      $info = mysql_fetch_assoc($first_date_query);

      $_SESSION["ft"]["view_{$view_id}_first_submission_date"] = $info["submission_date"];
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

  extract(ft_process_hooks("end", compact("form_id", "result"), array("result")), EXTR_OVERWRITE);

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
      ") or die(mysql_error());

  $view_id = "";
  $view_info = mysql_fetch_assoc($query);

  if (!empty($view_info))
    $view_id = $view_info["view_id"];

  return $view_id;
}

