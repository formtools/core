<?php

/**
 * This file contains all code relating to upgrading Form Tools.
 *
 * @copyright Benjamin Keen 2012
 * @author Benjamin Keen <ben.keen@gmail.com>
 * @package 2-2-x
 * @subpackage Upgrade
 */

// -------------------------------------------------------------------------------------------------


/**
 * This function upgrades the Form Tools Core. As of 2.0.3, it works very simply: this gets called
 * every time a person goes to the login page, the function is called. It contains all the
 * updates made to the script since the original release and based on the release date of the users
 * current build, only upgrades the more recent changes. Since $g_release_date was only added in 2.0.3,
 * there's a helper function that contains the dates of the main releases, to ensure those are updated
 * properly.
 *
 * The changes here are listed in the changelog: http://docs.formtools.org/changelog.php
 *
 * @return array a hash with the following keys:
 *            "upgraded" => (boolean) true if the script did just attempt to upgrade, false otherwise
 *            "success"  => (boolean) if an upgrade attempt was made, this determines whether it was
 *                          successful or not
 *            "message"  => the error message, if unsuccessful
 */
function ft_upgrade_form_tools()
{
  global $g_table_prefix, $g_current_version, $g_release_type, $g_release_date, $LANG, $g_default_datetime_format;

  $upgrade_attempted = false;
  $success     = "";
  $message     = "";
  $old_version_info = ft_get_core_version_info();

  // upgrading to 2.1.0 can take a while (not THIS long, but this should be safe)
  set_time_limit(600);


  // ----------------------------------------------------------------------------------------------
  // 2.0.0 beta updates

  if ($old_version_info["release_date"] < 20090113)
  {
    // add the Hooks table
    @mysql_query("
      CREATE TABLE {$g_table_prefix}hooks (
        hook_id mediumint(8) unsigned NOT NULL auto_increment,
        action_location enum('start','end') NOT NULL,
        module_folder varchar(255) NOT NULL,
        core_function varchar(255) NOT NULL,
        hook_function varchar(255) NOT NULL,
        priority tinyint(4) NOT NULL default '50',
        PRIMARY KEY (hook_id)
      ) DEFAULT CHARSET=utf8
      ");
  }

  if ($old_version_info["release_date"] < 20090301)
  {
    @mysql_query("
      ALTER TABLE {$g_table_prefix}email_templates
      CHANGE email_reply_to email_reply_to
      ENUM('none', 'admin', 'client', 'user', 'custom')
      CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL
        ");
  }

  if ($old_version_info["release_date"] < 20090317)
  {
    @mysql_query("
      ALTER TABLE {$g_table_prefix}views
      ADD may_add_submissions ENUM('yes', 'no') NOT NULL DEFAULT 'no'
        ");
  }

  if ($old_version_info["release_date"] < 20090402)
  {
    @mysql_query("
      ALTER TABLE {$g_table_prefix}hooks
      ADD hook_type ENUM('code', 'template') NOT NULL DEFAULT 'code' AFTER hook_id
        ");
    @mysql_query("
      ALTER TABLE {$g_table_prefix}hooks
      CHANGE action_location action_location VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL
        ");
    @mysql_query("
      ALTER TABLE {$g_table_prefix}account_settings
      CHANGE setting_value setting_value MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL
        ");
  }

  if ($old_version_info["release_date"] < 20090510)
  {
    @mysql_query("
      ALTER TABLE {$g_table_prefix}view_fields
      ADD is_searchable ENUM('yes','no') NOT NULL DEFAULT 'yes' AFTER is_editable
        ");
  }

  // bug #117
  if ($old_version_info["release_date"] < 20090627)
  {
    @mysql_query("
      ALTER TABLE {$g_table_prefix}view_filters
      CHANGE operator operator ENUM('equals', 'not_equals', 'like', 'not_like', 'before', 'after')
      CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'equals'
        ");
  }

  if ($old_version_info["release_date"] < 20090815)
  {
    @mysql_query("
      ALTER TABLE {$g_table_prefix}forms
      ADD edit_submission_page_label TEXT NULL
        ");

    // for upgrades, for maximum language compatibility set all the form Edit Submission Labels to
    // $LANG.phrase_edit_submission. They can always change it to English or whatever language they
    // want. New installations will have that value set to the administrator's language
    $forms = ft_get_forms();
    foreach ($forms as $form_info)
    {
      $form_id = $form_info["form_id"];
      @mysql_query("
        UPDATE {$g_table_prefix}forms
        SET    edit_submission_page_label = '{\$LANG.phrase_edit_submission|upper}'
        WHERE  form_id = $form_id
          ");
    }
  }

  if ($old_version_info["release_date"] < 20090826)
  {
    // bug fix for previous version which had a syntax error
    $query = mysql_query("SHOW COLUMNS FROM {$g_table_prefix}forms");
    $has_edit_submission_page_label_field = false;
    while ($row = mysql_fetch_assoc($query))
    {
      if ($row["Field"] == "edit_submission_page_label")
        $has_edit_submission_page_label_field = true;
    }

    if (!$has_edit_submission_page_label_field)
    {
      @mysql_query("ALTER TABLE {$g_table_prefix}forms ADD edit_submission_page_label TEXT NULL");
      $forms = ft_get_forms();
      foreach ($forms as $form_info)
      {
        $form_id = $form_info["form_id"];
        @mysql_query("
          UPDATE {$g_table_prefix}forms
          SET    edit_submission_page_label = '{\$LANG.phrase_edit_submission|upper}'
          WHERE  form_id = $form_id
            ");
      }
    }
  }

  if ($old_version_info["release_date"] < 20091113)
  {
    @mysql_query("ALTER TABLE {$g_table_prefix}view_filters ADD filter_type ENUM('standard', 'client_map') NOT NULL DEFAULT 'standard' AFTER view_id");
    @mysql_query("ALTER TABLE {$g_table_prefix}views ADD has_standard_filter ENUM('yes', 'no') NOT NULL DEFAULT 'no'");
    @mysql_query("ALTER TABLE {$g_table_prefix}views ADD has_client_map_filter ENUM('yes', 'no') NOT NULL DEFAULT 'no'");

    // set the has_standard_filter value to "yes" for any Views that have a filter defined
    $query = @mysql_query("SELECT view_id FROM {$g_table_prefix}view_filters GROUP BY view_id");
    while ($row = mysql_fetch_assoc($query))
    {
      $view_id = $row["view_id"];
      @mysql_query("UPDATE {$g_table_prefix}views SET has_standard_filter = 'yes' WHERE view_id = $view_id");
    }
  }

  // this version introduced an improved "form email fields" feature that lets you mark multiple email
  // fields as having significance for the email mechanism. All DB changes relate to this new feature.
  if ($old_version_info["release_date"] < 20100118)
  {
    // [1] misc DB column updates
    @mysql_query("
      ALTER TABLE {$g_table_prefix}email_templates
      ADD email_from_form_email_id MEDIUMINT UNSIGNED NULL AFTER email_from_account_id
        ");
    @mysql_query("
      ALTER TABLE {$g_table_prefix}email_templates
      ADD email_reply_to_form_email_id MEDIUMINT UNSIGNED NULL AFTER email_reply_to_account_id
        ");

    // [2] email_from DB field update
    $email_from_query = mysql_query("
      SELECT email_id
      FROM   {$g_table_prefix}email_templates
      WHERE  email_from = 'user'
        ");
    @mysql_query("
      ALTER TABLE {$g_table_prefix}email_templates
      CHANGE email_from email_from ENUM('admin', 'client', 'form_email_field', 'custom', 'none')
      CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL
        ");
    while ($row = mysql_fetch_assoc($email_from_query))
    {
      $email_id = $row["email_id"];
      mysql_query("
        UPDATE {$g_table_prefix}email_templates
        SET    email_from = 'form_email_field'
        WHERE  email_id = $email_id
          ");
    }

    // [3] email_reply_to DB field update
    $email_reply_to_query = mysql_query("
      SELECT email_id
      FROM   {$g_table_prefix}email_templates
      WHERE  email_reply_to = 'user'
        ");
    @mysql_query("
      ALTER TABLE {$g_table_prefix}email_templates
      CHANGE email_reply_to email_reply_to ENUM('admin', 'client', 'form_email_field', 'custom', 'none')
      CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL
        ");
    while ($row = mysql_fetch_assoc($email_reply_to_query))
    {
      $email_id = $row["email_id"];
      mysql_query("
        UPDATE {$g_table_prefix}email_templates
        SET    email_reply_to = 'form_email_field'
        WHERE  email_id = $email_id
          ");
    }

    // [4] create our new form_email_fields table
    @mysql_query("
      CREATE TABLE {$g_table_prefix}form_email_fields (
        form_email_id MEDIUMINT unsigned NOT NULL auto_increment,
        form_id MEDIUMINT UNSIGNED NOT NULL,
        email_field VARCHAR( 255 ) NOT NULL,
        first_name_field VARCHAR( 255 ) NULL,
        last_name_field VARCHAR( 255 ) NULL,
        PRIMARY KEY (form_email_id)
      ) DEFAULT CHARSET=utf8
        ");

    // [5] rename the "recipient_user_type" enum options to call the "user" option "form_email_field" instead,
    // but first, store all the recipient_ids so we can update them after the DB change
    $recipients_id_query = @mysql_query("
      SELECT recipient_id
      FROM   {$g_table_prefix}email_template_recipients
      WHERE  recipient_user_type = 'user'
        ");
    @mysql_query("
      ALTER TABLE {$g_table_prefix}email_template_recipients
      CHANGE recipient_user_type recipient_user_type ENUM('admin', 'client', 'form_email_field', 'custom')
      CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL
        ");
    @mysql_query("
      ALTER TABLE {$g_table_prefix}email_template_recipients
      ADD form_email_id MEDIUMINT UNSIGNED NULL AFTER account_id
        ");
    while ($row = mysql_fetch_assoc($recipients_id_query))
    {
      // we can safely set the form_email_id to 1 for these because after upgrading they will
      // have one and only one form email ID
      $recipient_id = $row["recipient_id"];
      mysql_query("
        UPDATE {$g_table_prefix}email_template_recipients
        SET    recipient_user_type = 'form_email_field',
               form_email_id = 1
        WHERE  recipient_id = $recipient_id
          ");
    }

    // [6] now update the old "user" email field data to the new "form email field" table
    // and update the corresponding DB tables
    $forms_query = @mysql_query("SELECT form_id, user_email_field, user_first_name_field, user_last_name_field FROM {$g_table_prefix}forms");
    while ($form_info = mysql_fetch_assoc($forms_query))
    {
      $form_id = $form_info["form_id"];
      $user_email_field      = $form_info["user_email_field"];
      $user_first_name_field = $form_info["user_first_name_field"];
      $user_last_name_field  = $form_info["user_last_name_field"];

      if (!empty($user_email_field))
      {
        // create the new email field
        @mysql_query("
          INSERT INTO {$g_table_prefix}form_email_fields (form_id, email_field, first_name_field, last_name_field)
          VALUES ($form_id, '$user_email_field', '$user_first_name_field', '$user_last_name_field')
            ");
        $form_email_id = mysql_insert_id();

        // "from"
        @mysql_query("
          UPDATE {$g_table_prefix}email_templates
          SET    email_from_form_email_id = $form_email_id
          WHERE  form_id = $form_id AND
                 email_from = 'form_email_field'
            ");
        // "reply-to"
        @mysql_query("
          UPDATE {$g_table_prefix}email_templates
          SET    email_reply_to_form_email_id = $form_email_id
          WHERE  form_id = $form_id AND
                 email_reply_to = 'form_email_field'
              ");
        // "to"
        @mysql_query("
          UPDATE {$g_table_prefix}email_template_recipients
          SET    form_email_id = $form_email_id
          WHERE  form_id = $form_id AND
                 recipient_user_type = 'form_email_field'
              ");
      }
    }

    // delete the old fields in the forms table. They're not needed any more
    @mysql_query("ALTER TABLE {$g_table_prefix}forms DROP COLUMN user_email_field");
    @mysql_query("ALTER TABLE {$g_table_prefix}forms DROP COLUMN user_first_name_field");
    @mysql_query("ALTER TABLE {$g_table_prefix}forms DROP COLUMN user_last_name_field");
  }

  // ----------------------------------------------------------------------------------------------
  // 2.0.3 beta updates

  if ($old_version_info["release_date"] < 20100731)
  {
    // add the default security setting fields
    $settings = array(
      "default_max_failed_login_attempts" => "",
      "min_password_length"               => "",
      "required_password_chars"           => "",
      "num_password_history"              => "",
      "clients_may_edit_max_failed_login_attempts" => ""
    );
    ft_set_settings($settings);

    // now set the default values for all clients. This sucks, obviously - but eventually the
    // whole inheritance model for client account settings will be overhauled and replaced with a
    // "Client Group" system
    $client_settings = array(
      "min_password_length"       => "",
      "num_failed_login_attempts" => 0,
      "num_password_history"      => "",
      "required_password_chars"   => "",
      "may_edit_max_failed_login_attempts" => ""
    );

    $clients = ft_get_client_list();
    foreach ($clients as $client_info)
    {
      // add the current password to the password history queue
      $client_settings["password_history"] = $client_info["password"];
      ft_set_account_settings($client_info["account_id"], $client_settings);
    }
  }

  if ($old_version_info["release_date"] < 20100908)
  {
    // convert all core tables to MyISAM
    $core_tables = array(
      "accounts", "account_settings", "client_forms", "client_views", "email_templates",
      "email_template_edit_submission_views", "email_template_recipients", "field_options",
      "field_option_groups", "field_settings", "forms", "form_email_fields", "form_fields",
      "hooks", "menus", "menu_items", "modules", "module_menu_items", "multi_page_form_urls",
      "public_form_omit_list", "public_view_omit_list", "settings", "sessions", "themes", "views",
      "view_fields", "view_filters", "view_tabs"
    );
    foreach ($core_tables as $table)
    {
      @mysql_query("ALTER TABLE {$g_table_prefix}$table TYPE=MyISAM");
      @mysql_query("ALTER TABLE {$g_table_prefix}$table ENGINE=MyISAM");
    }

    // convert all the custom tables to MyISAM as well
    $forms = ft_get_forms();
    foreach ($forms as $form_info)
    {
      $form_id = $form_info["form_id"];
      @mysql_query("ALTER TABLE {$g_table_prefix}form_{$form_id} TYPE=MyISAM");
      @mysql_query("ALTER TABLE {$g_table_prefix}form_{$form_id} ENGINE=MyISAM");
    }
  }

  // ----------------------------------------------------------------------------------------------
  // 2.1.0

  if ($old_version_info["release_date"] < 20110509)
  {
    // create the new 2.1.0 tables (this is only ever done once!)
    $check_tables_query = mysql_query("SHOW TABLES");
    $existing_tables = array();
    while ($row = mysql_fetch_array($check_tables_query))
    {
      $existing_tables[] = $row[0];
    }

    if (!in_array("{$g_table_prefix}field_type_setting_options", $existing_tables))
    {
      $query = mysql_query("
        CREATE TABLE {$g_table_prefix}field_type_setting_options (
          setting_id mediumint(9) NOT NULL,
          option_text varchar(255) default NULL,
          option_value varchar(255) default NULL,
          option_order smallint(6) NOT NULL,
          is_new_sort_group enum('yes','no') NOT NULL,
          PRIMARY KEY  (setting_id,option_order)
        ) DEFAULT CHARSET=utf8
      ");

      // textbox - size
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (1, 'Tiny', 'cf_size_tiny', 1, 'yes')");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (1, 'Small', 'cf_size_small', 2, 'yes')");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (1, 'Medium', 'cf_size_medium', 3, 'yes')");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (1, 'Large', 'cf_size_large', 4, 'yes')");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (1, 'Full Width', 'cf_size_full_width', 5, 'yes')");

      // textbox - highlight
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (3, 'Orange', 'cf_colour_orange', 3, 'yes')");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (3, 'Yellow', 'cf_colour_yellow', 4, 'yes')");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (3, 'Red', 'cf_colour_red', 2, 'yes')");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (3, 'None', '', 1, 'yes')");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (3, 'Green', 'cf_colour_green', 5, 'yes')");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (3, 'Blue', 'cf_colour_blue', 6, 'yes')");

      // textarea - height
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (5, 'Tiny (30px)', 'cf_size_tiny', 1, 'yes')");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (5, 'Small (80px)', 'cf_size_small', 2, 'yes')");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (5, 'Medium (150px)', 'cf_size_medium', 3, 'yes')");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (5, 'Large (300px)', 'cf_size_large', 4, 'yes')");

      // textarea - highlight
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (6, 'None', '', 1, 'yes')");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (6, 'Red', 'cf_colour_red', 2, 'yes')");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (6, 'Orange', 'cf_colour_orange', 3, 'yes')");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (6, 'Yellow', 'cf_colour_yellow', 4, 'yes')");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (6, 'Green', 'cf_colour_green', 5, 'yes')");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (6, 'Blue', 'cf_colour_blue', 6, 'yes')");

      // textarea - input length
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (7, 'No Limit', '', 1, 'yes')");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (7, 'Words', 'words', 2, 'yes')");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (7, 'Characters', 'chars', 3, 'yes')");

      // radios
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (17, 'Horizontal', 'horizontal', 1, 'yes')");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (17, 'Vertical', 'vertical', 2, 'yes')");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (17, '2 Columns', 'cf_option_list_2cols', 3, 'yes')");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (17, '3 Columns', 'cf_option_list_3cols', 4, 'yes')");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (17, '4 Columns', 'cf_option_list_4cols', 5, 'yes')");

      // checkboxes
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (20, 'Horizontal', 'horizontal', 1, 'yes')");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (20, 'Vertical', 'vertical', 2, 'yes')");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (20, '2 Columns', 'cf_option_list_2cols', 3, 'yes')");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (20, '3 Columns', 'cf_option_list_3cols', 4, 'yes')");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (20, '4 Columns', 'cf_option_list_4cols', 5, 'yes')");

      // date
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (22, '2011-11-30', 'yy-mm-dd', 1, 'yes')");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (22, '30/11/2011 (dd/mm/yyyy)', 'dd/mm/yy', 2, 'yes')");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (22, '11/30/2011 (mm/dd/yyyy)', 'mm/dd/yy', 3, 'yes')");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (22, 'Nov 30, 2011', 'M d, yy', 4, 'yes')");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (22, 'November 30, 2011', 'MM d, yy', 5, 'yes')");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (22, 'Wed Nov 30, 2011 ', 'D M d, yy', 6, 'yes')");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (22, 'Wednesday, November 30, 2011', 'DD, MM d, yy', 7, 'yes')");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (22, '30. 08. 2011.', 'dd. mm. yy.', 8, 'yes')");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (22, '30/11/2011 8:00 PM', 'datetime:dd/mm/yy|h:mm TT|ampm`true', 9, 'yes')");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (22, '11/30/2011 8:00 PM', 'datetime:mm/dd/yy|h:mm TT|ampm`true', 10, 'yes')");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (22, '2011-11-30 8:00 PM', 'datetime:yy-mm-dd|h:mm TT|ampm`true', 11, 'yes')");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (22, '2011-11-30 20:00', 'datetime:yy-mm-dd|hh:mm', 12, 'yes')");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (22, '2011-11-30 20:00:00', 'datetime:yy-mm-dd|hh:mm:ss|showSecond`true', 13, 'yes')");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (22, '30. 08. 2011. 20:00', 'datetime:dd. mm. yy.|hh:mm', 14, 'yes')");

      // time
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (24, '8:00 AM', 'h:mm TT|ampm`true', 1, 'yes')");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (24, '16:00', 'hh:mm|ampm`false', 2, 'yes')");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (24, '16:00:00', 'hh:mm:ss|showSecond`true|ampm`false', 3, 'yes')");

      // code / markup
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (28, 'CSS', 'CSS', 1, 'yes')");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (28, 'HTML', 'HTML', 2, 'yes')");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (28, 'JavaScript', 'JavaScript', 3, 'yes')");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (28, 'XML', 'XML', 4, 'yes')");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (29, 'Tiny (50px)', '50', 1, 'yes')");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (29, 'Small (100px)', '100', 2, 'yes')");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (29, 'Medium (200px)', '200', 3, 'yes')");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES (29, 'Large (400px)', '400', 4, 'yes')");
    }

    if (!in_array("{$g_table_prefix}field_type_settings", $existing_tables))
    {
      $query = mysql_query("
        CREATE TABLE {$g_table_prefix}field_type_settings (
          setting_id mediumint(8) unsigned NOT NULL auto_increment,
          field_type_id mediumint(8) unsigned NOT NULL,
          field_label varchar(255) NOT NULL,
          field_setting_identifier varchar(50) NOT NULL,
          field_type enum('textbox','textarea','radios','checkboxes','select','multi-select','option_list_or_form_field') NOT NULL,
          field_orientation enum('horizontal','vertical','na') NOT NULL default 'na',
          default_value_type enum('static','dynamic') NOT NULL default 'static',
          default_value varchar(255) default NULL,
          list_order smallint(6) NOT NULL,
          PRIMARY KEY  (setting_id)
        ) DEFAULT CHARSET=utf8
      ");

      // textbox
      mysql_query("INSERT INTO {$g_table_prefix}field_type_settings VALUES (1, 1, 'Size', 'size', 'select', 'na', 'static', 'cf_size_medium', 1)");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_settings VALUES (2, 1, 'Max Length', 'maxlength', 'textbox', 'na', 'static', '', 2)");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_settings VALUES (3, 1, 'Highlight', 'highlight', 'select', 'na', 'static', '', 4)");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_settings VALUES (4, 1, 'Field Comments', 'comments', 'textarea', 'na', 'static', '', 3)");

      // textarea
      mysql_query("INSERT INTO {$g_table_prefix}field_type_settings VALUES (5, 2, 'Height', 'height', 'select', 'na', 'static', 'cf_size_small', 1)");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_settings VALUES (6, 2, 'Highlight Colour', 'highlight_colour', 'select', 'na', 'static', '', 3)");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_settings VALUES (7, 2, 'Input length limit', 'input_length', 'radios', 'horizontal', 'static', '', 4)");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_settings VALUES (8, 2, '- Max length (words/chars)', 'maxlength', 'textbox', 'na', 'static', '', 5)");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_settings VALUES (9, 2, 'Field Comments', 'comments', 'textarea', 'na', 'static', '', 2)");

      // password
      mysql_query("INSERT INTO {$g_table_prefix}field_type_settings VALUES (10, 3, 'Field Comments', 'comments', 'textarea', 'na', 'static', '', 1)");

      // dropdown
      mysql_query("INSERT INTO {$g_table_prefix}field_type_settings VALUES (11, 4, 'Option List / Contents', 'contents', 'option_list_or_form_field', 'na', 'static', '', 1)");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_settings VALUES (12, 4, 'Field Comments', 'comments', 'textarea', 'na', 'static', '', 2)");

      // multi-select dropdown
      mysql_query("INSERT INTO {$g_table_prefix}field_type_settings VALUES (13, 5, 'Option List / Contents', 'contents', 'option_list_or_form_field', 'na', 'static', '', 1)");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_settings VALUES (14, 5, 'Num Rows', 'num_rows', 'textbox', 'na', 'static', '5', 2)");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_settings VALUES (15, 5, 'Field Comments', 'comments', 'textarea', 'na', 'static', '', 3)");

      // radios
      mysql_query("INSERT INTO {$g_table_prefix}field_type_settings VALUES (16, 6, 'Option List / Contents', 'contents', 'option_list_or_form_field', 'na', 'static', '', 1)");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_settings VALUES (17, 6, 'Formatting', 'formatting', 'select', 'na', 'static', 'horizontal', 2)");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_settings VALUES (18, 6, 'Field Comments', 'comments', 'textarea', 'na', 'static', '', 3)");

      // checkboxes
      mysql_query("INSERT INTO {$g_table_prefix}field_type_settings VALUES (19, 7, 'Option List / Contents', 'contents', 'option_list_or_form_field', 'na', 'static', '', 1)");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_settings VALUES (20, 7, 'Formatting', 'formatting', 'select', 'na', 'static', 'horizontal', 2)");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_settings VALUES (21, 7, 'Field Comments', 'comments', 'textarea', 'na', 'static', '', 3)");

      // date
      mysql_query("INSERT INTO {$g_table_prefix}field_type_settings VALUES (22, 8, 'Custom Display Format', 'display_format', 'select', 'na', 'static', 'yy-mm-dd', 1)");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_settings VALUES (23, 8, 'Field Comments', 'comments', 'textarea', 'na', 'static', '', 2)");

      // time
      mysql_query("INSERT INTO {$g_table_prefix}field_type_settings VALUES (24, 9, 'Custom Display Format', 'display_format', 'select', 'na', 'static', 'h:mm TT|ampm`true', 1)");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_settings VALUES (25, 9, 'Field Comments', 'comments', 'textarea', 'na', 'static', '', 2)");

      // phone number
      mysql_query("INSERT INTO {$g_table_prefix}field_type_settings VALUES (26, 10, 'Phone Number Format', 'phone_number_format', 'textbox', 'na', 'static', '(xxx) xxx-xxxx', 1)");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_settings VALUES (27, 10, 'Field Comments', 'comments', 'textarea', 'na', 'static', '', 2)");

      // code / markup
      mysql_query("INSERT INTO {$g_table_prefix}field_type_settings VALUES (28, 11, 'Code / Markup Type', 'code_markup', 'select', 'na', 'static', 'HTML', 1)");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_settings VALUES (29, 11, 'Height', 'height', 'select', 'na', 'static', '200', 2)");
      mysql_query("INSERT INTO {$g_table_prefix}field_type_settings VALUES (30, 11, 'Field Comments', 'comments', 'textarea', 'na', 'static', '', 3)");
    }

    if (!in_array("{$g_table_prefix}field_types", $existing_tables))
    {
      $query = mysql_query("
        CREATE TABLE {$g_table_prefix}field_types (
          field_type_id mediumint(8) unsigned NOT NULL auto_increment,
          is_editable enum('yes','no') NOT NULL,
          non_editable_info mediumtext,
          managed_by_module_id mediumint(9) default NULL,
          field_type_name varchar(255) NOT NULL,
          field_type_identifier varchar(50) NOT NULL,
          group_id smallint(6) NOT NULL,
          is_file_field enum('yes','no') NOT NULL default 'no',
          is_date_field enum('yes','no') NOT NULL default 'no',
          raw_field_type_map varchar(50) default NULL,
          raw_field_type_map_multi_select_id mediumint(9) default NULL,
          list_order smallint(6) NOT NULL,
          compatible_field_sizes varchar(255) NOT NULL,
          view_field_smarty_markup mediumtext NOT NULL,
          edit_field_smarty_markup mediumtext NOT NULL,
          php_processing mediumtext NOT NULL,
          resources_css mediumtext,
          resources_js mediumtext,
          PRIMARY KEY (field_type_id)
        ) DEFAULT CHARSET=utf8
      ");

      mysql_query("INSERT INTO {$g_table_prefix}field_types VALUES (1, 'no', '{\$LANG.text_non_deletable_fields}', NULL, '{\$LANG.word_textbox}', 'textbox', 1, 'no', 'no', 'textbox', NULL, 1, '1char,2chars,tiny,small,medium,large,very_large', '\r\n', '<input type=\"text\" name=\"{\$NAME}\" value=\"{\$VALUE|escape}\" \r\n  class=\"{\$size}{if \$highlight} {\$highlight}{/if}\" \r\n  {if \$maxlength}maxlength=\"{\$maxlength}\"{/if} />\r\n \r\n{if \$comments}\r\n  <div class=\"cf_field_comments\">{\$comments}</div>\r\n{/if}\r\n', '\r\n', 'input.cf_size_tiny {\r\n  width: 30px; \r\n}\r\ninput.cf_size_small {\r\n  width: 80px; \r\n}\r\ninput.cf_size_medium {\r\n  width: 150px; \r\n}\r\ninput.cf_size_large {\r\n  width: 250px;\r\n}\r\ninput.cf_size_full_width {\r\n  width: 99%; \r\n}\r\n\r\n', '')");
      mysql_query("INSERT INTO {$g_table_prefix}field_types VALUES (2, 'yes', NULL, NULL, '{\$LANG.word_textarea}', 'textarea', 1, 'no', 'no', 'textarea', NULL, 2, 'medium,large,very_large', '{\$VALUE|nl2br}', '{* figure out all the classes *}\r\n{assign var=classes value=\$height}\r\n{if \$highlight_colour}\r\n  {assign var=classes value=\"`\$classes` `\$highlight_colour`\"}\r\n{/if}\r\n{if \$input_length == \"words\" && \$maxlength != \"\"}\r\n  {assign var=classes value=\"`\$classes` cf_wordcounter max`\$maxlength`\"}\r\n{else if \$input_length == \"chars\" && \$maxlength != \"\"}\r\n  {assign var=classes value=\"`\$classes` cf_textcounter max`\$maxlength`\"}\r\n{/if}\r\n\r\n<textarea name=\"{\$NAME}\" id=\"{\$NAME}_id\" class=\"{\$classes}\">{\$VALUE}</textarea>\r\n\r\n{if \$input_length == \"words\" && \$maxlength != \"\"}\r\n  <div class=\"cf_counter\" id=\"{\$NAME}_counter\">\r\n    {\$maxlength} word limit. <span></span> remaining words\r\n  </div>\r\n{elseif \$input_length == \"chars\" && \$max_length != \"\"}\r\n  <div class=\"cf_counter\" id=\"{\$NAME}_counter\">\r\n    {\$maxlength} characters limit. <span></span> remaining characters\r\n  </div>\r\n{/if}\r\n\r\n{if \$comments}\r\n  <div class=\"cf_field_comments\">{\$comments|nl2br}</div>\r\n{/if}\r\n', '', '.cf_counter span {\r\n  font-weight: bold; \r\n}\r\ntextarea {\r\n  width: 99%;\r\n}\r\ntextarea.cf_size_tiny {\r\n  height: 30px;\r\n}\r\ntextarea.cf_size_small {\r\n  height: 80px;  \r\n}\r\ntextarea.cf_size_medium {\r\n  height: 150px;  \r\n}\r\ntextarea.cf_size_large {\r\n  height: 300px;\r\n}\r\n', '/**\r\n * The following code provides a simple text/word counter option for any  \r\n * textarea. It either just keeps counting up, or limits the results to a\r\n * certain number - all depending on what the user has selected via the\r\n * field type settings.\r\n */\r\nvar cf_counter = {};\r\ncf_counter.get_max_count = function(el) {\r\n  var classes = \$(el).attr(''class'').split(\" \").slice(-1);\r\n  var max = null;\r\n  for (var i=0; i<classes.length; i++) {\r\n    var result = classes[i].match(/max(\\\d+)/);\r\n    if (result != null) {\r\n      max = result[1];\r\n      break;\r\n    }\r\n  }\r\n  return max;\r\n}\r\n\r\n\$(function() {\r\n  \$(\"textarea[class~=''cf_wordcounter'']\").each(function() {\r\n    var max = cf_counter.get_max_count(this);\r\n    if (max == null) {\r\n      return;\r\n    }\r\n    \$(this).bind(\"keydown\", function() {\r\n      var val = \$(this).val();\r\n      var len        = val.split(/[\\\s]+/);\r\n      var field_name = \$(this).attr(\"name\");\r\n      var num_words  = len.length - 1;\r\n      if (num_words > max) {\r\n        var allowed_words = val.split(/[\\\s]+/, max);\r\n        truncated_str = allowed_words.join(\" \");\r\n        \$(this).val(truncated_str);\r\n      } else {\r\n        \$(\"#\" + field_name + \"_counter\").find(\"span\").html(parseInt(max) - parseInt(num_words));\r\n      }\r\n    });     \r\n    \$(this).trigger(\"keydown\");\r\n  });\r\n\r\n  \$(\"textarea[class~=''cf_textcounter'']\").each(function() {\r\n    var max = cf_counter.get_max_count(this);\r\n    if (max == null) {\r\n      return;\r\n    }\r\n    \$(this).bind(\"keydown\", function() {    \r\n      var field_name = \$(this).attr(\"name\");      \r\n      if (this.value.length > max) {\r\n        this.value = this.value.substring(0, max);\r\n      } else {\r\n        \$(\"#\" + field_name + \"_counter\").find(\"span\").html(max - this.value.length);\r\n      }\r\n    });\r\n    \$(this).trigger(\"keydown\");\r\n  }); \r\n});\r\n\r\n')");
      mysql_query("INSERT INTO {$g_table_prefix}field_types VALUES (3, 'yes', NULL, NULL, '{\$LANG.word_password}', 'password', 1, 'no', 'no', 'password', NULL, 3, '1char,2chars,tiny,small,medium', '\r\n', '<input type=\"password\" name=\"{\$NAME}\" value=\"{\$VALUE|escape}\" \r\n  class=\"cf_password\" />\r\n \r\n{if \$comments}\r\n  <div class=\"cf_field_comments\">{\$comments}</div>\r\n{/if}\r\n', '', 'input.cf_password {\r\n  width: 120px;\r\n}\r\n', '')");
      mysql_query("INSERT INTO {$g_table_prefix}field_types VALUES (4, 'yes', NULL, NULL, '{\$LANG.word_dropdown}', 'dropdown', 1, 'no', 'no', 'select', 11, 6, '1char,2chars,tiny,small,medium,large', '{if \$contents != \"\"}\r\n  {assign var=counter value=\"1\"}\r\n  {foreach from=\$.options item=curr_group_info name=group}\r\n    {assign var=group_info value=\$curr_group_info.group_info}\r\n    {assign var=options value=\$curr_group_info.options}\r\n    {foreach from=\$options item=option name=row}\r\n      {if \$VALUE == \$option.option_value}{\$option.option_name}{/if}\r\n    {/foreach}\r\n  {/foreach}\r\n{/if}', '{if \$contents == \"\"}\r\n  <div class=\"cf_field_comments\">\r\n    This field isn''t assigned to an Option List. \r\n  </div>\r\n{else}\r\n  <select name=\"{\$NAME}\">\r\n  {foreach from=\$contents.options item=curr_group_info name=group}\r\n    {assign var=group_info value=\$curr_group_info.group_info}\r\n    {assign var=options value=\$curr_group_info.options}\r\n    {if \$group_info.group_name}\r\n      <optgroup label=\"{\$group_info.group_name|escape}\">\r\n    {/if}\r\n    {foreach from=\$options item=option name=row}\r\n      <option value=\"{\$option.option_value}\"\r\n        {if \$VALUE == \$option.option_value}selected{/if}>{\$option.option_name}</option>\r\n    {/foreach}\r\n    {if \$group_info.group_name}\r\n      </optgroup>\r\n    {/if}\r\n  {/foreach}\r\n  </select>\r\n{/if}\r\n\r\n{if \$comments}\r\n  <div class=\"cf_field_comments\">{\$comments}</div>\r\n{/if}\r\n\r\n', '', '', '')");
      mysql_query("INSERT INTO {$g_table_prefix}field_types VALUES (5, 'yes', NULL, NULL, '{\$LANG.phrase_multi_select_dropdown}', 'multi_select_dropdown', 1, 'no', 'no', 'multi-select', 13, 7, '1char,2chars,tiny,small,medium,large', '{if \$contents != \"\"}\r\n  {assign var=vals value=\"`\$g_multi_val_delimiter`\"|explode:\$VALUE}\r\n  {assign var=is_first value=true}\r\n  {strip}\r\n  {foreach from=\$contents.options item=curr_group_info name=group}\r\n    {assign var=options value=\$curr_group_info.options}\r\n    {foreach from=\$options item=option name=row}\r\n      {if \$option.option_value|in_array:\$vals}\r\n        {if \$is_first == false}, {/if}\r\n        {\$option.option_name}\r\n        {assign var=is_first value=false}\r\n      {/if}\r\n    {/foreach}\r\n  {/foreach}\r\n  {/strip}\r\n{/if}', '{if \$contents == \"\"}\r\n  <div class=\"cf_field_comments\">\r\n    This field isn''t assigned to an Option List. \r\n  </div>\r\n{else}\r\n  {assign var=vals value=\"`\$g_multi_val_delimiter`\"|explode:\$VALUE}\r\n  <select name=\"{\$NAME}[]\" multiple size=\"{if \$num_rows}{\$num_rows}{else}5{/if}\">\r\n  {foreach from=\$contents.options item=curr_group_info name=group}\r\n    {assign var=group_info value=\$curr_group_info.group_info}\r\n    {assign var=options value=\$curr_group_info.options}\r\n    {if \$group_info.group_name}\r\n      <optgroup label=\"{\$group_info.group_name|escape}\">\r\n    {/if}\r\n    {foreach from=\$options item=option name=row}\r\n      <option value=\"{\$option.option_value}\"\r\n        {if \$option.option_value|in_array:\$vals}selected{/if}>{\$option.option_name}</option>\r\n    {/foreach}\r\n    {if \$group_info.group_name}\r\n      </optgroup>\r\n    {/if}\r\n  {/foreach}\r\n  </select>\r\n{/if}\r\n\r\n{if \$comments}\r\n  <div class=\"cf_field_comments\">{\$comments}</div>\r\n{/if}\r\n', '', '', '')");
      mysql_query("INSERT INTO {$g_table_prefix}field_types VALUES (6, 'yes', NULL, NULL, '{\$LANG.phrase_radio_buttons}', 'radio_buttons', 1, 'no', 'no', 'radio-buttons', 16, 4, '1char,2chars,tiny,small,medium,large', '{if \$contents != \"\"}\r\n  {assign var=counter value=\"1\"}\r\n  {foreach from=\$contents.options item=curr_group_info name=group}\r\n    {assign var=group_info value=\$curr_group_info.group_info}\r\n    {assign var=options value=\$curr_group_info.options}\r\n    {foreach from=\$options item=option name=row}\r\n      {if \$VALUE == \$option.option_value}{\$option.option_name}{/if}\r\n    {/foreach}\r\n  {/foreach}\r\n{/if}\r\n', '{if \$contents == \"\"}\r\n  <div class=\"cf_field_comments\">\r\n    This field isn''t assigned to an Option List. \r\n  </div>\r\n{else}\r\n  {assign var=is_in_columns value=false}\r\n  {if \$formatting == \"cf_option_list_2cols\" || \r\n      \$formatting == \"cf_option_list_3cols\" || \r\n      \$formatting == \"cf_option_list_4cols\"}\r\n    {assign var=is_in_columns value=true}\r\n  {/if}\r\n\r\n  {assign var=counter value=\"1\"}\r\n  {foreach from=\$contents.options item=curr_group_info name=group}\r\n    {assign var=group_info value=\$curr_group_info.group_info}\r\n    {assign var=options value=\$curr_group_info.options}\r\n\r\n    {if \$group_info.group_name}\r\n      <div class=\"cf_option_list_group_label\">{\$group_info.group_name}</div>\r\n    {/if}\r\n\r\n    {if \$is_in_columns}<div class=\"{\$formatting}\">{/if}\r\n\r\n    {foreach from=\$options item=option name=row}\r\n      {if \$is_in_columns}<div class=\"column\">{/if}\r\n        <input type=\"radio\" name=\"{\$NAME}\" id=\"{\$NAME}_{\$counter}\" \r\n          value=\"{\$option.option_value}\"\r\n          {if \$VALUE == \$option.option_value}checked{/if} />\r\n          <label for=\"{\$NAME}_{\$counter}\">{\$option.option_name}</label>\r\n      {if \$is_in_columns}</div>{/if}\r\n      {if \$formatting == \"vertical\"}<br />{/if}\r\n      {assign var=counter value=\$counter+1}\r\n    {/foreach}\r\n\r\n    {if \$is_in_columns}</div>{/if}\r\n  {/foreach}\r\n\r\n  {if \$comments}<div class=\"cf_field_comments\">{\$comments}</div>{/if}\r\n{/if}\r\n', '', '/* All CSS styles for this field type are found in Shared Resources */\r\n', '')");
      mysql_query("INSERT INTO {$g_table_prefix}field_types VALUES (7, 'yes', NULL, NULL, '{\$LANG.word_checkboxes}', 'checkboxes', 1, 'no', 'no', 'checkboxes', 19, 5, '1char,2chars,tiny,small,medium,large', '{if \$contents != \"\"}\r\n  {assign var=vals value=\"`\$g_multi_val_delimiter`\"|explode:\$VALUE}\r\n  {assign var=is_first value=true}\r\n  {strip}\r\n  {foreach from=\$contents.options item=curr_group_info name=group}\r\n    {assign var=options value=\$curr_group_info.options}\r\n    {foreach from=\$options item=option name=row}\r\n      {if \$option.option_value|in_array:\$vals}\r\n        {if \$is_first == false}, {/if}\r\n        {\$option.option_name}\r\n        {assign var=is_first value=false}\r\n      {/if}\r\n    {/foreach}\r\n  {/foreach}\r\n  {/strip}\r\n{/if}', '{if \$contents == \"\"}\r\n  <div class=\"cf_field_comments\">\r\n    This field isn''t assigned to an Option List. \r\n  </div>\r\n{else}\r\n  {assign var=vals value=\"`\$g_multi_val_delimiter`\"|explode:\$VALUE}\r\n  {assign var=is_in_columns value=false}\r\n  {if \$formatting == \"cf_option_list_2cols\" || \r\n      \$formatting == \"cf_option_list_3cols\" || \r\n      \$formatting == \"cf_option_list_4cols\"}\r\n    {assign var=is_in_columns value=true}\r\n  {/if}\r\n\r\n  {assign var=counter value=\"1\"}\r\n  {foreach from=\$contents.options item=curr_group_info name=group}\r\n    {assign var=group_info value=\$curr_group_info.group_info}\r\n    {assign var=options value=\$curr_group_info.options}\r\n\r\n    {if \$group_info.group_name}\r\n      <div class=\"cf_option_list_group_label\">{\$group_info.group_name}</div>\r\n    {/if}\r\n\r\n    {if \$is_in_columns}<div class=\"{\$formatting}\">{/if}\r\n\r\n    {foreach from=\$options item=option name=row}\r\n      {if \$is_in_columns}<div class=\"column\">{/if}\r\n        <input type=\"checkbox\" name=\"{\$NAME}[]\" id=\"{\$NAME}_{\$counter}\" \r\n          value=\"{\$option.option_value|escape}\" \r\n          {if \$option.option_value|in_array:\$vals}checked{/if} />\r\n          <label for=\"{\$NAME}_{\$counter}\">{\$option.option_name}</label>\r\n      {if \$is_in_columns}</div>{/if}\r\n      {if \$formatting == \"vertical\"}<br />{/if}\r\n      {assign var=counter value=\$counter+1}\r\n    {/foreach}\r\n\r\n    {if \$is_in_columns}</div>{/if}\r\n  {/foreach}\r\n\r\n  {if {\$comments}\r\n    <div class=\"cf_field_comments\">{\$comments}</div> \r\n  {/if}\r\n{/if}\r\n', '', '/* all CSS is found in Shared Resources */\r\n', '')");

      mysql_query("
        INSERT INTO {$g_table_prefix}field_types (field_type_id, is_editable, non_editable_info, managed_by_module_id, field_type_name,
          field_type_identifier, group_id, is_file_field, is_date_field, raw_field_type_map, raw_field_type_map_multi_select_id,
          list_order, compatible_field_sizes, view_field_smarty_markup, edit_field_smarty_markup, php_processing,
          resources_css, resources_js)
        VALUES (8, 'no', '{\$LANG.text_non_deletable_fields}', NULL, 'Date', 'date', 2, 'no', 'yes', '', NULL, 1, 'small', '', '', '',
            '.cf_datepicker {\r\n  width: 160px; \r\n}\r\n.cf_datetimepicker {\r\n  width: 160px; \r\n}\r\n.ui-datepicker-trigger {\r\n  cursor: pointer; \r\n}\r\n',
            '\$(function() {\r\n  // the datetimepicker has a bug that prevents the icon from appearing. So\r\n  // instead, we add the image manually into the page and assign the open event\r\n  // handler to the image\r\n  var default_settings = {\r\n    changeYear: true,\r\n    changeMonth: true   \r\n  }\r\n\r\n  \$(\".cf_datepicker\").each(function() {\r\n    var field_name = \$(this).attr(\"name\");\r\n    var settings = default_settings;\r\n    if (\$(\"#\" + field_name + \"_id\").length) {\r\n      settings.dateFormat = \$(\"#\" + field_name + \"_format\").val();\r\n    }\r\n    \$(this).datepicker(settings);\r\n    \$(\"#\" + field_name + \"_icon_id\").bind(\"click\",\r\n      { field_id: \"#\" + field_name + \"_id\" }, function(e) {      \r\n      \$.datepicker._showDatepicker(\$(e.data.field_id)[0]);\r\n    });\r\n  });\r\n    \r\n  \$(\".cf_datetimepicker\").each(function() {\r\n    var field_name = \$(this).attr(\"name\");\r\n    var settings = default_settings;\r\n    if (\$(\"#\" + field_name + \"_id\").length) {\r\n      var settings_str = \$(\"#\" + field_name + \"_format\").val();\r\n      settings_str = settings_str.replace(/datetime:/, \"\");\r\n      var settings_list = settings_str.split(\"|\");\r\n      var settings = {};\r\n      settings.dateFormat = settings_list[0];\r\n      settings.timeFormat = settings_list[1];      \r\n      for (var i=2; i<settings_list.length; i++) {\r\n        var parts = settings_list[i].split(\"`\");\r\n        if (parts[1] === \"true\") {\r\n          parts[1] = true;\r\n        }\r\n        settings[parts[0]] = parts[1];\r\n      }\r\n    }\r\n    \$(this).datetimepicker(settings);\r\n    \$(\"#\" + field_name + \"_icon_id\").bind(\"click\",\r\n      { field_id: \"#\" + field_name + \"_id\" }, function(e) {      \r\n      \$.datepicker._showDatepicker(\$(e.data.field_id)[0]);\r\n    });\r\n  });  \r\n});')");

      mysql_query("INSERT INTO {$g_table_prefix}field_types VALUES (9, 'yes', NULL, NULL, '{\$LANG.word_time}', 'time', 2, 'no', 'no', '', NULL, 2, 'small', '', '<div class=\"cf_date_group\">\r\n  <input type=\"input\" name=\"{\$NAME}\" value=\"{\$VALUE}\" class=\"cf_datefield cf_timepicker\" />\r\n  <input type=\"hidden\" id=\"{\$NAME}_id\" value=\"{\$display_format}\" />\r\n  \r\n  {if \$comments}\r\n    <div class=\"cf_field_comments\">{\$comments}</div>\r\n  {/if}\r\n</div>\r\n', '\r\n', '.cf_timepicker {\r\n  width: 60px; \r\n}\r\n.ui-timepicker-div .ui-widget-header{ margin-bottom: 8px; }\r\n.ui-timepicker-div dl{ text-align: left; }\r\n.ui-timepicker-div dl dt{ height: 25px; }\r\n.ui-timepicker-div dl dd{ margin: -25px 0 10px 65px; }\r\n.ui-timepicker-div td { font-size: 90%; }\r\n\r\n', '\$(function() {  \r\n  var default_settings = {\r\n    buttonImage:     g.root_url + \"/global/images/clock.png\",      \r\n    showOn:          \"both\",\r\n    buttonImageOnly: true\r\n  }\r\n  \$(\".cf_timepicker\").each(function() {\r\n    var field_name = \$(this).attr(\"name\");\r\n    var settings = default_settings;\r\n    if (\$(\"#\" + field_name + \"_id\").length) {\r\n      var settings_list = \$(\"#\" + field_name + \"_id\").val().split(\"|\");      \r\n      if (settings_list.length > 0) {\r\n        settings.timeFormat = settings_list[0];\r\n        for (var i=1; i<settings_list.length; i++) {\r\n          var parts = settings_list[i].split(\"`\");\r\n          if (parts[1] === \"true\") {\r\n            parts[1] = true;\r\n          } else if (parts[1] === \"false\") {\r\n            parts[1] = false;\r\n          }\r\n          settings[parts[0]] = parts[1];\r\n        }\r\n      }\r\n    }\r\n    \$(this).timepicker(settings);\r\n  });\r\n});\r\n\r\n')");
      mysql_query("INSERT INTO {$g_table_prefix}field_types VALUES (10, 'yes', NULL, NULL, '{\$LANG.phrase_phone_number}', 'phone', 2, 'no', 'no', '', NULL, 3, 'small,medium', '{php}\r\n\$format = \$this->get_template_vars(\"phone_number_format\");\r\n\$values = explode(\"|\", \$this->get_template_vars(\"VALUE\"));\r\n\$pieces = preg_split(\"/(x+)/\", \$format, 0, PREG_SPLIT_DELIM_CAPTURE);\r\n\$counter = 1;\r\n\$output = \"\";\r\n\$has_content = false;\r\nforeach (\$pieces as \$piece)\r\n{\r\n  if (empty(\$piece))\r\n    continue;\r\n\r\n  if (\$piece[0] == \"x\") {    \r\n    \$value = (isset(\$values[\$counter-1])) ? \$values[\$counter-1] : \"\";\r\n    \$output .= \$value;\r\n    if (!empty(\$value))\r\n    {\r\n      \$has_content = true;\r\n    }\r\n    \$counter++;\r\n  } else {\r\n    \$output .= \$piece;\r\n  }\r\n}\r\n\r\nif (!empty(\$output) && \$has_content)\r\n  echo \$output;\r\n{/php}', '{php}\r\n\$format = \$this->get_template_vars(\"phone_number_format\");\r\n\$values = explode(\"|\", \$this->get_template_vars(\"VALUE\"));\r\n\$name   = \$this->get_template_vars(\"NAME\");\r\n\r\n\$pieces = preg_split(\"/(x+)/\", \$format, 0, PREG_SPLIT_DELIM_CAPTURE);\r\n\$counter = 1;\r\nforeach (\$pieces as \$piece)\r\n{\r\n  if (strlen(\$piece) == 0)\r\n    continue;\r\n\r\n  if (\$piece[0] == \"x\") {\r\n    \$size = strlen(\$piece); \r\n    \$value = (isset(\$values[\$counter-1])) ? \$values[\$counter-1] : \"\";\r\n    \$value = htmlspecialchars(\$value);\r\n    echo \"<input type=\\\\\"text\\\\\" name=\\\\\"{\$name}_\$counter\\\\\" value=\\\\\"\$value\\\\\"\r\n            size=\\\\\"\$size\\\\\" maxlength=\\\\\"\$size\\\\\" />\";\r\n    \$counter++;\r\n  } else {\r\n    echo \$piece;\r\n  }\r\n}\r\n{/php}\r\n{if \$comments}\r\n  <div class=\"cf_field_comments\">{\$comments}</div>\r\n{/if}\r\n', '\$field_name = \$vars[\"field_info\"][\"field_name\"];\r\n\$joiner = \"|\";\r\n\r\n\$count = 1;\r\n\$parts = array();\r\nwhile (isset(\$vars[\"data\"][\"{\$field_name}_\$count\"]))\r\n{\r\n  \$parts[] = \$vars[\"data\"][\"{\$field_name}_\$count\"];\r\n  \$count++;\r\n}\r\n\$value = implode(\"|\", \$parts);\r\n\r\n\r\n', '', '')");
      mysql_query("INSERT INTO {$g_table_prefix}field_types VALUES (11, 'yes', NULL, NULL, '{\$LANG.phrase_code_markup_field}', 'code_markup', 2, 'no', 'no', 'textarea', NULL, 4, 'large,very_large', '{if \$CONTEXTPAGE == \"edit_submission\"}\r\n  <textarea id=\"{\$NAME}_id\" name=\"{\$NAME}\">{\$VALUE}</textarea>\r\n  <script>\r\n  var code_mirror_{\$NAME} = new CodeMirror.fromTextArea(\"{\$NAME}_id\", \r\n  {literal}{{/literal}\r\n    height: \"{\$SIZE_PX}px\",\r\n    path:   \"{\$g_root_url}/global/codemirror/js/\",\r\n    readOnly: true,\r\n    {if \$code_markup == \"HTML\" || \$code_markup == \"XML\"}\r\n      parserfile: [\"parsexml.js\"],\r\n      stylesheet: \"{\$g_root_url}/global/codemirror/css/xmlcolors.css\"\r\n    {elseif \$code_markup == \"CSS\"}\r\n      parserfile: [\"parsecss.js\"],\r\n      stylesheet: \"{\$g_root_url}/global/codemirror/css/csscolors.css\"\r\n    {elseif \$code_markup == \"JavaScript\"}  \r\n      parserfile: [\"tokenizejavascript.js\", \"parsejavascript.js\"],\r\n      stylesheet: \"{\$g_root_url}/global/codemirror/css/jscolors.css\"\r\n    {/if}\r\n  {literal}});{/literal}\r\n  </script>\r\n{else}\r\n  {\$VALUE|strip_tags}\r\n{/if}\r\n', '<div class=\"editor\">\r\n  <textarea id=\"{\$NAME}_id\" name=\"{\$NAME}\">{\$VALUE}</textarea>\r\n</div>\r\n<script>\r\n  var code_mirror_{\$NAME} = new CodeMirror.fromTextArea(\"{\$NAME}_id\", \r\n  {literal}{{/literal}\r\n    height: \"{\$SIZE_PX}px\",\r\n    path:   \"{\$g_root_url}/global/codemirror/js/\",\r\n    {if \$code_markup == \"HTML\" || \$code_markup == \"XML\"}\r\n      parserfile: [\"parsexml.js\"],\r\n      stylesheet: \"{\$g_root_url}/global/codemirror/css/xmlcolors.css\"\r\n    {elseif \$code_markup == \"CSS\"}\r\n      parserfile: [\"parsecss.js\"],\r\n      stylesheet: \"{\$g_root_url}/global/codemirror/css/csscolors.css\"\r\n    {elseif \$code_markup == \"JavaScript\"}  \r\n      parserfile: [\"tokenizejavascript.js\", \"parsejavascript.js\"],\r\n      stylesheet: \"{\$g_root_url}/global/codemirror/css/jscolors.css\"\r\n    {/if}\r\n  {literal}});{/literal}\r\n</script>\r\n\r\n{if \$comments}\r\n  <div class=\"cf_field_comments\">{\$comments}</div>\r\n{/if}\r\n', '', '.cf_view_markup_field {\r\n  margin: 0px; \r\n}\r\n', '')");
    }

    if (!in_array("{$g_table_prefix}field_types", $existing_tables))
    {
      $query = mysql_query("
        CREATE TABLE {$g_table_prefix}list_groups (
          group_id mediumint(8) unsigned NOT NULL auto_increment,
          group_type varchar(50) NOT NULL,
          group_name varchar(255) NOT NULL,
          custom_data text NOT NULL,
          list_order smallint(6) NOT NULL,
          PRIMARY KEY  (group_id)
        ) DEFAULT CHARSET=utf8
      ");

      $standard_fields = ft_sanitize($LANG["phrase_standard_fields"]);
      $special_fields  = ft_sanitize($LANG["phrase_special_fields"]);
      mysql_query("INSERT INTO {$g_table_prefix}list_groups (group_type, group_name, list_order) VALUES ('field_types', '$standard_fields', 1)");
      mysql_query("INSERT INTO {$g_table_prefix}list_groups (group_type, group_name, list_order) VALUES ('field_types', '$special_fields', 2)");
    }

    // this will automatically fail if the fields already exist
    @mysql_query("
      ALTER TABLE {$g_table_prefix}modules
      ADD is_premium ENUM('yes', 'no') NOT NULL DEFAULT 'no' AFTER is_enabled,
      ADD module_key VARCHAR(15) NULL AFTER is_premium
    ");

    @mysql_query("ALTER TABLE {$g_table_prefix}field_types ADD view_field_rendering_type ENUM('none', 'php', 'smarty') NOT NULL DEFAULT 'none' AFTER compatible_field_sizes");
    @mysql_query("ALTER TABLE {$g_table_prefix}field_types ADD view_field_php_function VARCHAR(255) NULL AFTER view_field_rendering_type");
    @mysql_query("ALTER TABLE {$g_table_prefix}field_types ADD view_field_php_function_source VARCHAR(255) NULL AFTER view_field_rendering_type");

    mysql_query("ALTER TABLE {$g_table_prefix}hooks RENAME {$g_table_prefix}hook_calls");
    mysql_query("ALTER TABLE {$g_table_prefix}hook_calls CHANGE core_function function_name VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL");

    // okay! At this point, we've created the new tables for the field types & can safely add the new file and tinyMCE field
    // types. In order to upgrade to 2.1.0, the user MUST have installed the WYSIWYG and file upload modules
    ft_update_module_list();

    // not installed? Return an error. Note: the above code will NOT be re-inser
    if (!ft_check_module_available("field_type_file"))
    {
      return array(
        "upgraded" => true,
        "success"  => false,
        "message"  => "Sorry, the <b>File Upload module</b> isn't installed. In order to upgrade to 2.1.0 or later, you must upload that module to your /modules folder, then refresh this page."
      );
    }
    if (!ft_check_module_available("field_type_tinymce"))
    {
      return array(
        "upgraded" => true,
        "success"  => false,
        "message"  => "Sorry, the <b>TinyMCE Field</b> module isn't installed. In order to upgrade to 2.1.0 or later, you must upload that module to your /modules folder, then refresh this page."
      );
    }

    // okay, now this is a balancing act. At this point in the upgrade, SOME of the database has been updated, but not
    // all. The thing is, in order to upgrade the DB to use the file and WYSIWYG fields (and port over their old values
    // to the new DB structure) we need to ensure those two modules are installed. The code at the top of this section
    // ensured that those two modules are in fact found in the folder, but now we need to install them as well
    $modules = ft_get_modules();

    foreach ($modules as $module_info)
    {
      $module_id    = $module_info["module_id"];
      $is_installed = $module_info["is_installed"];

      if ($is_installed == "yes")
        continue;

      ft_install_module(array("install" => $module_id));
    }

    $query = @mysql_query("
      CREATE TABLE {$g_table_prefix}new_view_submission_defaults (
        view_id mediumint(9) NOT NULL,
        field_id mediumint(9) NOT NULL,
        default_value text NOT NULL,
        list_order smallint(6) NOT NULL,
        PRIMARY KEY  (view_id,field_id)
      ) DEFAULT CHARSET=utf8
    ");

    $query = @mysql_query("
      CREATE TABLE {$g_table_prefix}view_columns (
        view_id mediumint(9) NOT NULL,
        field_id mediumint(9) NOT NULL,
        list_order smallint(6) NOT NULL,
        is_sortable enum('yes','no') NOT NULL,
        auto_size enum('yes','no') NOT NULL default 'yes',
        custom_width varchar(10) default NULL,
        truncate enum('truncate','no_truncate') NOT NULL default 'truncate',
        PRIMARY KEY  (view_id,field_id,list_order)
      ) DEFAULT CHARSET=utf8
    ");



    // changed Tables: simple changes that don't require any data manipulation
    mysql_query("ALTER TABLE {$g_table_prefix}accounts ADD last_logged_in DATETIME NULL AFTER account_status");
    mysql_query("ALTER TABLE {$g_table_prefix}accounts CHANGE username username VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL");
    mysql_query("ALTER TABLE {$g_table_prefix}accounts CHANGE password password VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL");

    mysql_query("ALTER TABLE {$g_table_prefix}menu_items ADD is_new_sort_group ENUM('yes','no') NOT NULL DEFAULT 'yes' AFTER is_submenu");
    mysql_query("ALTER TABLE {$g_table_prefix}field_options ADD is_new_sort_group ENUM('yes','no') NOT NULL DEFAULT 'yes'");
    mysql_query("ALTER TABLE {$g_table_prefix}field_options CHANGE field_group_id list_id MEDIUMINT(8) UNSIGNED NOT NULL");

    mysql_query("ALTER TABLE {$g_table_prefix}form_fields ADD is_new_sort_group ENUM('yes','no') NOT NULL DEFAULT 'yes' AFTER list_order");
    mysql_query("ALTER TABLE {$g_table_prefix}form_fields CHANGE field_size field_size VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT 'medium'");

    mysql_query("ALTER TABLE {$g_table_prefix}field_settings CHANGE setting_value setting_value MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT ''");

    mysql_query("ALTER TABLE {$g_table_prefix}view_fields ADD is_new_sort_group ENUM('yes','no') NOT NULL DEFAULT 'yes'");

    mysql_query("ALTER TABLE {$g_table_prefix}views ADD is_new_sort_group ENUM('yes','no') NOT NULL DEFAULT 'yes' AFTER view_order");
    mysql_query("ALTER TABLE {$g_table_prefix}views ADD group_id SMALLINT NULL AFTER is_new_sort_group");

    mysql_query("ALTER TABLE {$g_table_prefix}forms DROP default_view_id");
    mysql_query("ALTER TABLE {$g_table_prefix}forms ADD form_type ENUM('internal','external') NOT NULL DEFAULT 'external' AFTER form_id");
    mysql_query("ALTER TABLE {$g_table_prefix}forms ADD add_submission_button_label VARCHAR(255) NULL DEFAULT '{$LANG["word_add_rightarrow"]}'");
    mysql_query("ALTER TABLE {$g_table_prefix}forms CHANGE form_url form_url VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL");

    mysql_query("INSERT INTO {$g_table_prefix}settings (setting_name, setting_value, module) VALUES ('edit_submission_shared_resources_js', '\$(function() {\r\n  \$(\".fancybox\").fancybox();\r\n});\r\n', 'core')");
    mysql_query("INSERT INTO {$g_table_prefix}settings (setting_name, setting_value, module) VALUES ('edit_submission_shared_resources_css', '/* used in the \"Highlight\" setting for most field types */\r\n.cf_colour_red { \r\n  background-color: #990000;\r\n  color: white;\r\n}\r\n.cf_colour_orange {\r\n  background-color: orange; \r\n}\r\n.cf_colour_yellow {\r\n  background-color: yellow; \r\n}\r\n.cf_colour_green {\r\n  background-color: green;\r\n  color: white; \r\n}\r\n.cf_colour_blue {\r\n  background-color: #336699; \r\n  color: white; \r\n}\r\n\r\n/* field comments */\r\n.cf_field_comments {\r\n  font-style: italic;\r\n  color: #999999;\r\n  clear: both;\r\n}\r\n\r\n/* column layouts for radios & checkboxes */\r\n.cf_option_list_group_label {\r\n  font-weight: bold;  \r\n  clear: both;\r\n  margin-left: 4px;\r\n}\r\n.cf_option_list_2cols, .cf_option_list_3cols, .cf_option_list_4cols {\r\n  clear: both; \r\n}\r\n.cf_option_list_2cols .column { \r\n  width: 50%;\r\n  float: left; \r\n}\r\n.cf_option_list_3cols .column { \r\n  width: 33%;\r\n  float: left;\r\n}\r\n.cf_option_list_4cols .column { \r\n  width: 25%;\r\n  float: left;\r\n}\r\n\r\n/* Used for the date and time pickers */\r\n.cf_date_group img {\r\n  margin-bottom: -4px;\r\n  padding: 1px;\r\n}\r\n\r\n', 'core')");
    mysql_query("INSERT INTO {$g_table_prefix}settings (setting_name, setting_value, module) VALUES ('edit_submission_onload_resources', '<script src=\"{\$g_root_url}/global/codemirror/js/codemirror.js\"></script>|<script src=\"{\$g_root_url}/global/scripts/jquery-ui-timepicker-addon.js\"></script>|<script src=\"{\$g_root_url}/global/fancybox/jquery.fancybox-1.3.4.pack.js\"></script> |<link rel=\"stylesheet\" href=\"{\$g_root_url}/global/fancybox/jquery.fancybox-1.3.4.css\" type=\"text/css\" media=\"screen\" />', 'core')");
    $forms_page_default_message = ft_sanitize($LANG["text_client_welcome"]);
    mysql_query("INSERT INTO {$g_table_prefix}settings (setting_name, setting_value, module) VALUES ('forms_page_default_message', '$forms_page_default_message', 'core')");

    mysql_query("UPDATE {$g_table_prefix}settings SET setting_name = 'num_option_lists_per_page' WHERE setting_name = 'num_field_option_groups_per_page'");

    // Field Option Groups are now called Option Lists
    mysql_query("ALTER TABLE {$g_table_prefix}field_option_groups RENAME {$g_table_prefix}option_lists");
    mysql_query("ALTER TABLE {$g_table_prefix}option_lists CHANGE group_id list_id MEDIUMINT(8) UNSIGNED NOT NULL AUTO_INCREMENT");
    mysql_query("ALTER TABLE {$g_table_prefix}option_lists CHANGE group_name option_list_name VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL");
    mysql_query("ALTER TABLE {$g_table_prefix}option_lists ADD is_grouped ENUM('yes', 'no') NOT NULL DEFAULT 'no' AFTER option_list_name");

    // may not be necessary, but just to make sure
    mysql_query("
      ALTER TABLE {$g_table_prefix}view_filters
      CHANGE operator operator ENUM('equals', 'not_equals', 'like', 'not_like', 'before', 'after')
      CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'equals'
    ");

    // not used anymore! Dependency tracking is entirely handled by formtools.org
    mysql_query("ALTER TABLE {$g_table_prefix}themes DROP supports_ft_versions");
    mysql_query("ALTER TABLE {$g_table_prefix}modules DROP supports_ft_versions");


    // next, we need up update menus to change field_option_groups to option_lists. Since the user may have tweaked the
    // menu label to say whatever they want, only update the actual label if it was the original English "Field Option Groups"
    $menu_items_query = mysql_query("
      SELECT *
      FROM   {$g_table_prefix}menu_items
      WHERE  page_identifier = 'field_option_groups'
        ");
    while ($row = mysql_fetch_assoc($menu_items_query))
    {
      $menu_item_id = $row["menu_item_id"];
      $display_text_clause = "";
      if ($row["display_text"] == "Field Option Groups")
      {
        $display_text = ft_sanitize($LANG["phrase_option_lists"]);
        $display_text_clause = ", display_text = '{$display_text}'";
      }
      mysql_query("
        UPDATE {$g_table_prefix}menu_items
        SET    page_identifier = 'option_lists',
               url = '/admin/forms/option_lists/'
               $display_text_clause
        WHERE  menu_item_id = $menu_item_id
      ");
    }
    mysql_query("DELETE FROM {$g_table_prefix}menu_items WHERE page_identifier = 'settings_wysiwyg'");

    mysql_query("
      UPDATE {$g_table_prefix}menu_items
      SET    page_identifier = 'add_form_choose_type',
             url = '/admin/forms/add/'
      WHERE  page_identifier = 'add_form1'
    ");


    // all Views are now grouped. Add in a default group for each
    $forms_query = mysql_query("SELECT form_id FROM {$g_table_prefix}forms WHERE is_complete = 'yes'");
    $views_label = ft_sanitize($LANG["word_views"]);
    while ($row = mysql_fetch_assoc($forms_query))
    {
      $form_id = $row["form_id"];
      mysql_query("
        INSERT INTO {$g_table_prefix}list_groups (group_type, group_name, custom_data, list_order)
        VALUES ('form_{$form_id}_view_group', '$views_label', '', 1)
      ");
      $new_group_id = mysql_insert_id();

      mysql_query("
        UPDATE {$g_table_prefix}views
        SET    group_id = $new_group_id
        WHERE  form_id = $form_id
      ");
    }

    // the field options table now groups the options. What we need to do here is create a group for all items in each
    // Option List
    mysql_query("ALTER TABLE {$g_table_prefix}field_options ADD list_group_id MEDIUMINT NOT NULL AFTER list_id");
    $field_options = mysql_query("SELECT DISTINCT list_id FROM {$g_table_prefix}field_options");
    while ($row = mysql_fetch_assoc($field_options))
    {
      $list_id = $row["list_id"];
      mysql_query("
        INSERT INTO {$g_table_prefix}list_groups (group_type, group_name, custom_data, list_order)
        VALUES ('option_list_{$list_id}', '', '', 1)
      ");
      $new_group_id = mysql_insert_id();
      mysql_query("UPDATE {$g_table_prefix}field_options SET list_group_id = $new_group_id WHERE list_id = $list_id");
    }

    // field types are now field type IDs. This has significant impact.
    $forms = ft_get_forms();
    $form_changes = array();
    $date_system_field_ids = array();
    $fields_with_option_lists = array();
    foreach ($forms as $form_info)
    {
      $form_id = $form_info["form_id"];
      $fields  = ft_get_form_fields($form_id);
      $field_types = array();  // stores field IDs grouped by field type (key == old field type name string)
      $field_col_to_id_map = array();
      $field_id_to_option_list_id = array();
      foreach ($fields as $field_info)
      {
        $field_id = $field_info["field_id"];
        if (!array_key_exists($field_info["field_type"], $field_types))
          $field_types[$field_info["field_type"]] = array();

        if ($field_info["field_type"] == "system" && ($field_info["col_name"] == "last_modified_date" || $field_info["col_name"] == "submission_date"))
          $date_system_field_ids[] = $field_id;

        $field_types[$field_info["field_type"]][] = $field_id;
        $field_col_to_id_map[$field_info["col_name"]] = $field_id;

        if (!empty($field_info["field_group_id"]))
          $field_id_to_option_list_id[$field_info["field_id"]] = $field_info["field_group_id"];
      }
      $form_changes[$form_id] = array(
        "field_types"              => $field_types,
        "field_col_to_id_map"      => $field_col_to_id_map,
        "fields_with_option_lists" => $field_id_to_option_list_id
      );
    }

    // update the core field names for all forms
    foreach ($forms as $form_info)
    {
      $form_id = $form_info["form_id"];
      if ($form_info["is_complete"] == "no")
        continue;

      mysql_query("
        UPDATE {$g_table_prefix}form_fields
        SET    field_name = 'core__submission_id'
        WHERE  col_name = 'submission_id' AND
               field_type = 'system'
      ");
      mysql_query("
        UPDATE {$g_table_prefix}form_fields
        SET    field_name = 'core__last_modified'
        WHERE  col_name = 'last_modified_date' AND
               field_type = 'system'
      ");
      mysql_query("
        UPDATE {$g_table_prefix}form_fields
        SET    field_name = 'core__submission_date'
        WHERE  col_name = 'submission_date' AND
               field_type = 'system'
      ");
      mysql_query("
        UPDATE {$g_table_prefix}form_fields
        SET    field_name = 'core__ip_address'
        WHERE  col_name = 'ip_address' AND
               field_type = 'system'
       ");
    }

    mysql_query("ALTER TABLE {$g_table_prefix}form_fields CHANGE field_type field_type_id SMALLINT NOT NULL DEFAULT '1'");
    mysql_query("ALTER TABLE {$g_table_prefix}form_fields ADD is_system_field ENUM('yes','no') NOT NULL DEFAULT 'no' AFTER field_type_id");

    $field_types = ft_get_field_types();
    $field_type_map = array();
    foreach ($field_types as $row)
    {
      $field_type_map[$row["field_type_identifier"]] = $row["field_type_id"];
    }

    // existing ENUM key => new field type identifier
    $textbox_field_type_id = ft_get_field_type_id_by_identifier("textbox");
    $date_field_type_id    = ft_get_field_type_id_by_identifier("date");
    $map = array(
      "textbox"       => $field_type_map["textbox"],
      "textarea"      => $field_type_map["textarea"],
      "password"      => $field_type_map["password"],
      "select"        => $field_type_map["dropdown"],
      "multi-select"  => $field_type_map["multi_select_dropdown"],
      "radio-buttons" => $field_type_map["radio_buttons"],
      "checkboxes"    => $field_type_map["checkboxes"],
      "date"          => $field_type_map["date"],

      // at this juncture, we know these two exist and are installed
      "file"          => $field_type_map["file"],
      "wysiwyg"       => $field_type_map["tinymce"],

      // this is overridden for any date system fields (submission_date, last_modified_date)
      "system"        => $textbox_field_type_id
    );

    while (list($form_id, $changes) = each($form_changes))
    {
      while (list($field_label, $field_ids) = each($changes["field_types"]))
      {
        foreach ($field_ids as $field_id)
        {
          $field_type_id = $map[$field_label];
          if (in_array($field_id, $date_system_field_ids))
            $field_type_id = $date_field_type_id;

          $is_system_field = ($field_label == "system") ? "yes" : "no";
          mysql_query("
            UPDATE {$g_table_prefix}form_fields
            SET    field_type_id = $field_type_id,
                   is_system_field = '$is_system_field'
            WHERE  field_id = $field_id
          ");
        }
      }
    }
    reset($form_changes);

    // next, the form_email_fields table now uses IDs instead of col names. Update it!
    $email_updates = array();
    foreach ($forms as $form_info)
    {
      $form_id = $form_info["form_id"];
      $email_field_query = mysql_query("
        SELECT *
        FROM   {$g_table_prefix}form_email_fields
        WHERE  form_id = $form_id
      ");
      $email_fields = array();
      while ($field_info = mysql_fetch_assoc($email_field_query))
      {
        $email_fields[] = array(
          "form_email_id"       => $field_info["form_email_id"],
          "email_field_id"      => array_key_exists($field_info["email_field"], $form_changes[$form_id]["field_col_to_id_map"]) ? $form_changes[$form_id]["field_col_to_id_map"][$field_info["email_field"]] : "",
          "first_name_field_id" => array_key_exists($field_info["first_name_field"], $form_changes[$form_id]["field_col_to_id_map"]) ? $form_changes[$form_id]["field_col_to_id_map"][$field_info["first_name_field"]] : "NULL",
          "last_name_field_id"  => array_key_exists($field_info["last_name_field"], $form_changes[$form_id]["field_col_to_id_map"]) ? $form_changes[$form_id]["field_col_to_id_map"][$field_info["last_name_field"]] : "NULL",
        );
      }
      $email_updates[$form_id] = $email_fields;
    }

    mysql_query("ALTER TABLE {$g_table_prefix}form_email_fields CHANGE email_field email_field_id MEDIUMINT(9) NOT NULL");
    mysql_query("ALTER TABLE {$g_table_prefix}form_email_fields CHANGE first_name_field first_name_field_id MEDIUMINT(9) NULL DEFAULT NULL");
    mysql_query("ALTER TABLE {$g_table_prefix}form_email_fields CHANGE last_name_field last_name_field_id MEDIUMINT(9) NULL DEFAULT NULL");

    while (list($form_id, $changes) = each($email_updates))
    {
      foreach ($changes as $email_change_info)
      {
        $form_email_id       = $email_change_info["form_email_id"];
        $email_field_id      = $email_change_info["email_field_id"];
        if (empty($email_field_id) || !is_numeric($email_field_id))
          continue;
        $first_name_field_id = !empty($email_change_info["first_name_field_id"]) ? "'{$email_change_info["first_name_field_id"]}'" : "NULL";
        $last_name_field_id  = !empty($email_change_info["last_name_field_id"]) ? "'{$email_change_info["last_name_field_id"]}'" : "NULL";
        mysql_query("
          UPDATE {$g_table_prefix}form_email_fields
          SET    email_field_id      = $email_field_id,
                 first_name_field_id = $first_name_field_id,
                 last_name_field_id  = $last_name_field_id
          WHERE  form_email_id = $form_email_id
        ");
      }
    }

    // now update the View fields table. The is_column and is_sortable info is now stored in the view_columns table
    $view_cols = array();
    foreach ($forms as $form_info)
    {
      $form_id = $form_info["form_id"];
      $view_query = mysql_query("SELECT view_id FROM {$g_table_prefix}views WHERE form_id = $form_id");
      $view_ids = array();
      while ($view_info = mysql_fetch_assoc($view_query))
        $view_ids[] = $view_info["view_id"];

      foreach ($view_ids as $view_id)
      {
        // we can't use ft_get_view_fields here because the Core code references DB changes
        // that can't be made yet
        $view_fields_query = mysql_query("
          SELECT field_id, tab_number, is_column, is_sortable
          FROM   {$g_table_prefix}view_fields
          WHERE  view_id = $view_id
        ");

        while ($view_field_info = mysql_fetch_assoc($view_fields_query))
        {
          $field_id    = $view_field_info["field_id"];
          $tab_number  = $view_field_info["tab_number"];
          $is_column   = $view_field_info["is_column"];
          $is_sortable = $view_field_info["is_sortable"];

          if ($is_column != "yes")
            continue;

          $view_cols[$view_id][] = array(
            "field_id"    => $field_id,
            "is_sortable" => $is_sortable
          );
        }
      }
    }

    // now insert the view_columns records
    while (list($view_id, $info) = each($view_cols))
    {
      $order = 1;
      foreach ($info as $col_info)
      {
        $field_id    = $col_info["field_id"];
        $is_sortable = $col_info["is_sortable"];
        mysql_query("
          INSERT INTO {$g_table_prefix}view_columns (view_id, field_id, list_order, is_sortable, auto_size, custom_width, truncate)
          VALUES ($view_id, $field_id, $order, 'yes', 'yes', '', 'truncate')
        ");
        $order++;
      }
    }

    mysql_query("ALTER TABLE {$g_table_prefix}view_fields DROP is_column, DROP is_sortable");
    mysql_query("ALTER TABLE {$g_table_prefix}view_fields ADD group_id MEDIUMINT(9) NOT NULL AFTER field_id");


    // all View fields are now grouped. Formerly, view fields could be mapped to tabs individually, now they're mapped
    // to the View field group. So what we do here is: look at each View, and those fields within it.
    $forms_query = mysql_query("SELECT form_id FROM {$g_table_prefix}forms WHERE is_complete = 'yes'");
    while ($row = mysql_fetch_assoc($forms_query))
    {
      $form_id = $row["form_id"];
      $views_query = mysql_query("SELECT view_id FROM {$g_table_prefix}views WHERE form_id = $form_id");
      while ($view_info = mysql_fetch_assoc($views_query))
      {
        $view_id = $view_info["view_id"];

        $tab_num_query = mysql_query("
          SELECT DISTINCT tab_number
          FROM   {$g_table_prefix}view_fields
          WHERE  view_id = $view_id AND
                 tab_number IS NOT NULL
        ");

        $tab_numbers = array();
        while ($tab_row = mysql_fetch_assoc($tab_num_query))
        {
          $tab_numbers[] = $tab_row["tab_number"];
        }

        // if none of the fields were mapped to a tab, cool! Just map all View fields to a single list group
        if (empty($tab_numbers))
        {
          mysql_query("
            INSERT INTO {$g_table_prefix}list_groups (group_type, group_name, custom_data, list_order)
            VALUES ('view_fields_{$view_id}', '', '', 1)
          ");
          $new_group_id = mysql_insert_id();
          mysql_query("
            UPDATE {$g_table_prefix}view_fields
            SET    group_id = $new_group_id
            WHERE  view_id = $view_id
          ");
        }
        else
        {
          $order = 1;
          foreach ($tab_numbers as $tab_number)
          {
            mysql_query("
              INSERT INTO {$g_table_prefix}list_groups (group_type, group_name, custom_data, list_order)
              VALUES ('view_fields_{$view_id}', '', '$tab_number', $order)
            ");
            $new_group_id = mysql_insert_id();
            $order++;

            mysql_query("
              UPDATE {$g_table_prefix}view_fields
              SET    group_id = $new_group_id
              WHERE  view_id = $view_id AND
                     tab_number = $tab_number
            ");
          }
        }
      }
    }
    mysql_query("ALTER TABLE {$g_table_prefix}view_fields DROP tab_number");


    // FIELD_SETTINGS table  -----------------
    $old_field_settings = array();
    $field_settings_query = mysql_query("SELECT * FROM {$g_table_prefix}field_settings");
    while ($row = mysql_fetch_assoc($field_settings_query))
    {
      $old_field_settings[] = $row;
    }
    mysql_query("TRUNCATE {$g_table_prefix}field_settings");
    mysql_query("ALTER TABLE {$g_table_prefix}field_settings DROP module"); // this field wasn't ever used
    mysql_query("ALTER TABLE {$g_table_prefix}field_settings CHANGE setting_name setting_id MEDIUMINT NOT NULL");

    $file_field_type_id = ft_get_field_type_id_by_identifier("file");
    $field_setting_name_to_id_map = array(
      "file_upload_dir"       => ft_get_field_type_setting_by_identifier($file_field_type_id, "folder_path"),
      "file_upload_url"       => ft_get_field_type_setting_by_identifier($file_field_type_id, "folder_url"),
      "file_upload_filetypes" => ft_get_field_type_setting_by_identifier($file_field_type_id, "permitted_file_types"),
      "file_upload_max_size"  => ft_get_field_type_setting_by_identifier($file_field_type_id, "max_file_size")
    );

    foreach ($old_field_settings as $info)
    {
      $field_id      = $info["field_id"];
      $setting_id    = array_key_exists($info["setting_name"], $field_setting_name_to_id_map) ? $field_setting_name_to_id_map[$info["setting_name"]] : "";
      $setting_value = $info["setting_value"];

      // this shouldn't happen. The field_settings table was exceedingly underutilized & only ever contained settings with
      // those 4 names specified above
      if (empty($setting_id))
        continue;

      mysql_query("INSERT INTO {$g_table_prefix}field_settings (field_id, setting_id, setting_value) VALUES ($field_id, $setting_id, '$setting_value')");
    }

    // now the field_settings table is up to date, a few Core fields need new entries in the table
    $query = mysql_query("
      SELECT field_id
      FROM   {$g_table_prefix}form_fields
      WHERE  is_system_field = 'yes' AND
             (field_name = 'core__submission_date' OR field_name = 'core__last_modified')
    ");
    $date_field_type_datetime_setting_id = ft_get_field_type_setting_id_by_identifier($date_field_type_id, "display_format");
    while ($row = mysql_fetch_assoc($query))
    {
      $field_id = $row["field_id"];
      mysql_query("
        INSERT INTO {$g_table_prefix}field_settings (field_id, setting_id, setting_value)
        VALUES ($field_id, $date_field_type_datetime_setting_id, '$g_default_datetime_format')
      ");
    }

    // next we need to update the mappings for any fields that use an Option List. Before, that info was stored in the
    // field_group_id field in the form_fields table; now it's associated with a setting for the field type. First, create
    // a map of field_type_id => field_setting_id, where the setting ID is the one that contains the Option List
    $field_type_option_list_setting_id_map = array();
    foreach ($field_types as $row)
    {
      $field_type_id = $row["field_type_id"];
      $field_setting_info = ft_get_field_type_setting_by_identifier($field_type_id, "contents");

      if (!empty($field_setting_info))
        $field_type_option_list_setting_id_map[$field_type_id] = $field_setting_info["setting_id"];
    }

    // next, loop through each field that has an old field_option_group field and add the new field_setting
    $query = mysql_query("
      SELECT field_id, field_type_id, field_group_id
      FROM   {$g_table_prefix}form_fields
      WHERE  field_group_id IS NOT NULL AND field_group_id != ''
    ");

    // this stores those field IDs that are mapped to each option list ID
    $option_list_id_to_field_ids = array();

    while ($row = mysql_fetch_assoc($query))
    {
      $field_id       = $row["field_id"];
      $field_type_id  = $row["field_type_id"];
      $field_group_id = $row["field_group_id"];
      $setting_id = array_key_exists($field_type_id, $field_type_option_list_setting_id_map) ? $field_type_option_list_setting_id_map[$field_type_id] : "";

      mysql_query("
        INSERT INTO {$g_table_prefix}field_settings (field_id, setting_id, setting_value)
        VALUES ($field_id, $setting_id, $field_group_id)
      ");

      if (!array_key_exists($field_group_id, $option_list_id_to_field_ids))
        $option_list_id_to_field_ids[$field_group_id] = array();
      $option_list_id_to_field_ids[$field_group_id][] = $field_id;
    }
    mysql_query("ALTER TABLE {$g_table_prefix}form_fields DROP field_group_id");

    // for this one, we need to locate every field that uses the Option List and add a custom setting to ensure the
    // orientation isn't lost. At this juncture, we have the luxury of knowing that the default field type settings &
    // options haven't been modified by the user
    $orientation_query = mysql_query("
      SELECT list_id, field_orientation
      FROM   {$g_table_prefix}option_lists
      WHERE  field_orientation = 'vertical' OR field_orientation = 'horizontal'
    ");
    while ($row = mysql_fetch_assoc($orientation_query))
    {
      $curr_option_list_id = $row["list_id"];
      $orientation         = $row["field_orientation"];

      if (!array_key_exists($curr_option_list_id, $option_list_id_to_field_ids))
        continue;

      // the assumption here is that the multi-select field types have an orientation setting with
      // values "horizontal" and "vertical". It's safe.
      foreach ($option_list_id_to_field_ids[$curr_option_list_id] as $field_id)
      {
        // slow and crappy!
        $field_type_id = ft_get_field_type_id_by_field_id($field_id);
        $setting_id    = ft_get_field_type_setting_id_by_identifier($field_type_id, "formatting");

        // for checkbox & radios fields that were assigned to an option list with a "n/a" orientation, don't
        // worry about it: they'll inherit the default value
        if (empty($setting_id))
          continue;

        mysql_query("
          INSERT INTO {$g_table_prefix}field_settings (field_id, setting_id, setting_value)
          VALUES ($field_id, $setting_id, '$orientation')
        ");
      }
    }

    mysql_query("ALTER TABLE {$g_table_prefix}option_lists DROP field_orientation");
  }

  if ($old_version_info["release_date"] < 20110521)
  {
    mysql_query("
      UPDATE {$g_table_prefix}field_types
      SET    view_field_smarty_markup = '{\$VALUE|htmlspecialchars}'
      WHERE  field_type_identifier = 'textbox'
    ");
    mysql_query("
      UPDATE {$g_table_prefix}field_types
      SET    view_field_smarty_markup = '{if \$CONTEXTPAGE == \"edit_submission\"}\t\n  {\$VALUE|nl2br}\r\n{else}\r\n  {\$VALUE}\r\n{/if}'
      WHERE  field_type_identifier = 'textarea'
    ");
  }

  if ($old_version_info["release_date"] < 20110527)
  {
    mysql_query("
      UPDATE {$g_table_prefix}field_types
      SET    raw_field_type_map_multi_select_id = 16
      WHERE  field_type_identifier = 'radio_buttons'
    ");

    mysql_query("
      CREATE TABLE {$g_table_prefix}hooks (
        id mediumint(8) unsigned NOT NULL auto_increment,
        hook_type enum('code','template') NOT NULL,
        component enum('core','api','module') NOT NULL,
        filepath varchar(255) NOT NULL,
        action_location varchar(255) NOT NULL,
        function_name varchar(255) NOT NULL,
        params mediumtext,
        overridable mediumtext,
        PRIMARY KEY (id)
      )");

  }

  if ($old_version_info["release_date"] < 20110528)
  {
    // assorted updates to the Date field type
    mysql_query("
      INSERT INTO {$g_table_prefix}field_type_settings (field_type_id, field_label, field_setting_identifier, field_type,
        field_orientation, default_value_type, default_value, list_order)
      VALUES (8, 'Apply Timezone Offset', 'apply_timezone_offset', 'radios', 'horizontal', 'static', 'no', 2)
    ");
    $new_setting_id = mysql_insert_id();
    mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES ($new_setting_id, 'Yes', 'yes', 1, 'yes')");
    mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES ($new_setting_id, 'No', 'no', 2, 'yes')");

    mysql_query("
     UPDATE {$g_table_prefix}field_type_settings
     SET    list_order = 3
     WHERE  field_type_id = 8 AND
            field_setting_identifier = 'comments'
    ");

    mysql_query("
      UPDATE {$g_table_prefix}field_types
      SET    view_field_smarty_markup = '{strip}\r\n  {if \$VALUE}\r\n    {assign var=tzo value=\"\"}\r\n    {if \$apply_timezone_offset == \"yes\"}\r\n      {assign var=tzo value=\$ACCOUNT_INFO.timezone_offset}\r\n    {/if}\r\n    {if \$display_format == \"yy-mm-dd\" || !\$display_format}\r\n      {\$VALUE|custom_format_date:\$tzo:\"Y-m-d\"}\r\n    {elseif \$display_format == \"dd/mm/yy\"}\r\n      {\$VALUE|custom_format_date:\$tzo:\"d/m/Y\"}\r\n    {elseif \$display_format == \"mm/dd/yy\"}\r\n      {\$VALUE|custom_format_date:\$tzo:\"m/d/Y\"}\r\n    {elseif \$display_format == \"M d, yy\"}\r\n      {\$VALUE|custom_format_date:\$tzo:\"M j, Y\"}\r\n    {elseif \$display_format == \"MM d, yy\"}\r\n      {\$VALUE|custom_format_date:\$tzo:\"F j, Y\"}\r\n    {elseif \$display_format == \"D M d, yy\"}\r\n      {\$VALUE|custom_format_date:\$tzo:\"D M j, Y\"}\r\n    {elseif \$display_format == \"DD, MM d, yy\"}\r\n      {\$VALUE|custom_format_date:\$tzo:\"l M j, Y\"}\r\n    {elseif \$display_format == \"datetime:dd/mm/yy|h:mm TT|ampm`true\"}\r\n      {\$VALUE|custom_format_date:\$tzo:\"d/m/Y g:i A\"}\r\n    {elseif \$display_format == \"datetime:mm/dd/yy|h:mm TT|ampm`true\"}\r\n      {\$VALUE|custom_format_date:\$tzo:\"m/d/Y g:i A\"}\r\n    {elseif \$display_format == \"datetime:yy-mm-dd|h:mm TT|ampm`true\"}\r\n      {\$VALUE|custom_format_date:\$tzo:\"Y-m-d g:i A\"}\r\n    {elseif \$display_format == \"datetime:yy-mm-dd|hh:mm\"}\r\n      {\$VALUE|custom_format_date:\$tzo:\"Y-m-d H:i\"}\r\n    {elseif \$display_format == \"datetime:yy-mm-dd|hh:mm:ss|showSecond`true\"}\r\n      {\$VALUE|custom_format_date:\$tzo:\"Y-m-d H:i:s\"}\r\n    {/if}\r\n{/if}{/strip}\r\n',
             edit_field_smarty_markup = '{assign var=class value=\"cf_datepicker\"}\r\n{if \$display_format|strpos:\"datetime\" === 0}\r\n  {assign var=class value=\"cf_datetimepicker\"}\r\n{/if}\r\n\r\n{assign var=\"val\" value=\"\"}\r\n{if \$VALUE}\r\n  {assign var=tzo value=\"\"}\r\n  {if \$apply_timezone_offset == \"yes\"}\r\n    {assign var=tzo value=\$ACCOUNT_INFO.timezone_offset}\r\n  {/if}\r\n  {if \$display_format == \"yy-mm-dd\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"Y-m-d\"}\r\n  {elseif \$display_format == \"dd/mm/yy\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"d/m/Y\"}\r\n  {elseif \$display_format == \"mm/dd/yy\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"m/d/Y\"}\r\n  {elseif \$display_format == \"M d, yy\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"M j, Y\"}\r\n  {elseif \$display_format == \"MM d, yy\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"F j, Y\"}\r\n  {elseif \$display_format == \"D M d, yy\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"D M j, Y\"}\r\n  {elseif \$display_format == \"DD, MM d, yy\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"l M j, Y\"}\r\n  {elseif \$display_format == \"datetime:dd/mm/yy|h:mm TT|ampm`true\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"d/m/Y g:i A\"}\r\n  {elseif \$display_format == \"datetime:mm/dd/yy|h:mm TT|ampm`true\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"m/d/Y g:i A\"}\r\n  {elseif \$display_format == \"datetime:yy-mm-dd|h:mm TT|ampm`true\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"Y-m-d g:i A\"}\r\n  {elseif \$display_format == \"datetime:yy-mm-dd|hh:mm\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"Y-m-d H:i\"}\r\n  {elseif \$display_format == \"datetime:yy-mm-dd|hh:mm:ss|showSecond`true\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"Y-m-d H:i:s\"}\r\n  {/if}\r\n{/if}\r\n\r\n<div class=\"cf_date_group\">\r\n  <input type=\"input\" name=\"{\$NAME}\" id=\"{\$NAME}_id\" \r\n    class=\"cf_datefield {\$class}\" value=\"{\$val}\" /><img class=\"ui-datepicker-trigger\" src=\"{\$g_root_url}/global/images/calendar.png\" id=\"{\$NAME}_icon_id\" />\r\n  <input type=\"hidden\" id=\"{\$NAME}_format\" value=\"{\$display_format}\" />\r\n  {if \$comments}\r\n    <div class=\"cf_field_comments\">{\$comments}</div>\r\n  {/if}\r\n</div>\r\n',
             php_processing = '\$field_name     = \$vars[\"field_info\"][\"field_name\"];\r\n\$date           = \$vars[\"data\"][\$field_name];\r\n\$display_format = \$vars[\"settings\"][\"display_format\"];\r\n\$atzo           = \$vars[\"settings\"][\"apply_timezone_offset\"];\r\n\$account_info   = isset(\$vars[\"account_info\"]) ? \$vars[\"account_info\"] : array();\r\n\r\nif (empty(\$date))\r\n{\r\n  \$value = \"\";\r\n}\r\nelse\r\n{\r\n  if (strpos(\$display_format, \"datetime:\") === 0)\r\n  {\r\n    \$parts = explode(\" \", \$date);\r\n    switch (\$display_format)\r\n    {\r\n      case \"datetime:dd/mm/yy|h:mm TT|ampm`true\":\r\n        \$date = substr(\$date, 3, 2) . \"/\" . substr(\$date, 0, 2) . \"/\" . \r\n          substr(\$date, 6);        \r\n        break;\r\n    }\r\n  }\r\n  else\r\n  {\r\n    if (\$display_format == \"dd/mm/yy\")\r\n    {\r\n      \$date = substr(\$date, 3, 2) . \"/\" . substr(\$date, 0, 2) . \"/\" . \r\n        substr(\$date, 6);\r\n    }\r\n  }\r\n  \$time = strtotime(\$date);\r\n  \r\n  // lastly, if this field has a timezone offset being applied to it, do the\r\n  // appropriate math on the date\r\n  if (\$atzo == \"yes\" && !isset(\$account_info[\"timezone_offset\"]))\r\n  {\r\n    \$seconds_offset = \$account_info[\"timezone_offset\"] * 60 * 60;\r\n    \$time += \$seconds_offset;\r\n  }\r\n\r\n  \$value = date(\"Y-m-d H:i:s\", \$time);\r\n}\r\n\r\n\r\n'
      WHERE  field_type_id = 8
    ");
  }

  if ($old_version_info["release_date"] < 20110530)
  {
    mysql_query("INSERT INTO {$g_table_prefix}settings (setting_name, setting_value) VALUES ('default_date_field_search_value', 'none')");
  }

  if ($old_version_info["release_date"] < 20110612)
  {
    @mysql_query("ALTER TABLE {$g_table_prefix}accounts ADD temp_reset_password VARCHAR(50) NULL");
  }

  if ($old_version_info["release_date"] < 20110622)
  {
    @mysql_query("UPDATE {$g_table_prefix}field_types SET view_field_php_function_source = 'core'");

    // for compatibility with existing field type modules
    mysql_query("
      UPDATE {$g_table_prefix}field_types
      SET     view_field_rendering_type = 'smarty'
    ");

    // textbox
    mysql_query("
      UPDATE {$g_table_prefix}field_types
      SET    view_field_rendering_type = 'smarty',
             view_field_php_function_source = 'core',
             view_field_php_function = ''
      WHERE  field_type_identifier = 'textbox'
    ");

    // textarea
    mysql_query("
      UPDATE {$g_table_prefix}field_types
      SET    view_field_rendering_type = 'smarty',
             view_field_php_function_source = 'core',
             view_field_php_function = '',
             view_field_smarty_markup = '{if \$CONTEXTPAGE == \"edit_submission\"}  \r\n  {\$VALUE|nl2br}\r\n{else}\r\n  {\$VALUE}\r\n{/if}',
             edit_field_smarty_markup = '{* figure out all the classes *}\r\n{assign var=classes value=\$height}\r\n{if \$highlight_colour}\r\n  {assign var=classes value=\"`\$classes` `\$highlight_colour`\"}\r\n{/if}\r\n{if \$input_length == \"words\" && \$maxlength != \"\"}\r\n  {assign var=classes value=\"`\$classes` cf_wordcounter max`\$maxlength`\"}\r\n{else if \$input_length == \"chars\" && \$maxlength != \"\"}\r\n  {assign var=classes value=\"`\$classes` cf_textcounter max`\$maxlength`\"}\r\n{/if}\r\n\r\n<textarea name=\"{\$NAME}\" id=\"{\$NAME}_id\" class=\"{\$classes}\">{\$VALUE}</textarea>\r\n\r\n{if \$input_length == \"words\" && \$maxlength != \"\"}\r\n  <div class=\"cf_counter\" id=\"{\$NAME}_counter\">\r\n    {\$maxlength} {\$LANG.phrase_word_limit_p} <span></span> {\$LANG.phrase_remaining_words}\r\n  </div>\r\n{elseif \$input_length == \"chars\" && \$max_length != \"\"}\r\n  <div class=\"cf_counter\" id=\"{\$NAME}_counter\">\r\n    {\$maxlength} {\$LANG.phrase_characters_limit_p} <span></span> {\$LANG.phrase_remaining_characters}\r\n  </div>\r\n{/if}\r\n\r\n{if \$comments}\r\n  <div class=\"cf_field_comments\">{\$comments|nl2br}</div>\r\n{/if}'
      WHERE  field_type_identifier = 'textarea'
    ");

    // password
    mysql_query("
      UPDATE {$g_table_prefix}field_types
      SET     view_field_rendering_type = 'none'
      WHERE   field_type_identifier = 'password'
    ");

    // dropdown
    mysql_query("
      UPDATE {$g_table_prefix}field_types
      SET    view_field_rendering_type = 'php',
             view_field_php_function_source = 'core',
             view_field_php_function = 'ft_display_field_type_dropdown',
             view_field_smarty_markup = '{strip}{if \$contents != \"\"}\r\n  {foreach from=\$contents.options item=curr_group_info name=group}\r\n    {assign var=options value=\$curr_group_info.options}\r\n    {foreach from=\$options item=option name=row}\r\n      {if \$VALUE == \$option.option_value}{\$option.option_name}{/if}\r\n    {/foreach}\r\n  {/foreach}\r\n{/if}{/strip}',
             edit_field_smarty_markup = '{if \$contents == \"\"}\r\n  <div class=\"cf_field_comments\">{\$LANG.phrase_not_assigned_to_option_list}</div>\r\n{else}\r\n  <select name=\"{\$NAME}\">\r\n  {foreach from=\$contents.options item=curr_group_info name=group}\r\n    {assign var=group_info value=\$curr_group_info.group_info}\r\n    {assign var=options value=\$curr_group_info.options}\r\n    {if \$group_info.group_name}\r\n      <optgroup label=\"{\$group_info.group_name|escape}\">\r\n    {/if}\r\n    {foreach from=\$options item=option name=row}\r\n      <option value=\"{\$option.option_value}\"\r\n        {if \$VALUE == \$option.option_value}selected{/if}>{\$option.option_name}</option>\r\n    {/foreach}\r\n    {if \$group_info.group_name}\r\n      </optgroup>\r\n    {/if}\r\n  {/foreach}\r\n  </select>\r\n{/if}\r\n\r\n{if \$comments}\r\n  <div class=\"cf_field_comments\">{\$comments}</div>\r\n{/if}'
      WHERE  field_type_identifier = 'dropdown'
    ");

    // multi-select dropdown
    mysql_query("
      UPDATE {$g_table_prefix}field_types
      SET    view_field_rendering_type = 'php',
             view_field_php_function_source = 'core',
             view_field_php_function = 'ft_display_field_type_multi_select_dropdown',
             view_field_smarty_markup = '{if \$contents != \"\"}\r\n  {assign var=vals value=\"`\$g_multi_val_delimiter`\"|explode:\$VALUE}\r\n  {assign var=is_first value=true}\r\n  {strip}\r\n  {foreach from=\$contents.options item=curr_group_info name=group}\r\n    {assign var=options value=\$curr_group_info.options}\r\n    {foreach from=\$options item=option name=row}\r\n      {if \$option.option_value|in_array:\$vals}\r\n        {if \$is_first == false}, {/if}\r\n        {\$option.option_name}\r\n        {assign var=is_first value=false}\r\n      {/if}\r\n    {/foreach}\r\n  {/foreach}\r\n  {/strip}\r\n{/if}',
             edit_field_smarty_markup = '{if \$contents == \"\"}\r\n  <div class=\"cf_field_comments\">{\$LANG.phrase_not_assigned_to_option_list}</div>\r\n{else}\r\n  {assign var=vals value=\"`\$g_multi_val_delimiter`\"|explode:\$VALUE}\r\n  <select name=\"{\$NAME}[]\" multiple size=\"{if \$num_rows}{\$num_rows}{else}5{/if}\">\r\n  {foreach from=\$contents.options item=curr_group_info name=group}\r\n    {assign var=group_info value=\$curr_group_info.group_info}\r\n    {assign var=options value=\$curr_group_info.options}\r\n    {if \$group_info.group_name}\r\n      <optgroup label=\"{\$group_info.group_name|escape}\">\r\n    {/if}\r\n    {foreach from=\$options item=option name=row}\r\n      <option value=\"{\$option.option_value}\"\r\n        {if \$option.option_value|in_array:\$vals}selected{/if}>{\$option.option_name}</option>\r\n    {/foreach}\r\n    {if \$group_info.group_name}\r\n      </optgroup>\r\n    {/if}\r\n  {/foreach}\r\n  </select>\r\n{/if}\r\n\r\n{if \$comments}\r\n  <div class=\"cf_field_comments\">{\$comments}</div>\r\n{/if}',
      WHERE  field_type_identifier = 'multi_select_dropdown'
    ");

    // radio buttons
    mysql_query("
      UPDATE {$g_table_prefix}field_types
      SET    view_field_rendering_type = 'php',
             view_field_php_function_source = 'core',
             view_field_php_function = 'ft_display_field_type_radios',
             view_field_smarty_markup = '{strip}{if \$contents != \"\"}\r\n  {foreach from=\$contents.options item=curr_group_info name=group}\r\n    {assign var=options value=\$curr_group_info.options}\r\n    {foreach from=\$options item=option name=row}\r\n      {if \$VALUE == \$option.option_value}{\$option.option_name}{/if}\r\n    {/foreach}\r\n  {/foreach}\r\n{/if}{/strip}',
             edit_field_smarty_markup = '{if \$contents == \"\"}\r\n  <div class=\"cf_field_comments\">{\$LANG.phrase_not_assigned_to_option_list}</div>\r\n{else}\r\n  {assign var=is_in_columns value=false}\r\n  {if \$formatting == \"cf_option_list_2cols\" || \r\n      \$formatting == \"cf_option_list_3cols\" || \r\n      \$formatting == \"cf_option_list_4cols\"}\r\n    {assign var=is_in_columns value=true}\r\n  {/if}\r\n\r\n  {assign var=counter value=\"1\"}\r\n  {foreach from=\$contents.options item=curr_group_info name=group}\r\n    {assign var=group_info value=\$curr_group_info.group_info}\r\n    {assign var=options value=\$curr_group_info.options}\r\n\r\n    {if \$group_info.group_name}\r\n      <div class=\"cf_option_list_group_label\">{\$group_info.group_name}</div>\r\n    {/if}\r\n\r\n    {if \$is_in_columns}<div class=\"{\$formatting}\">{/if}\r\n\r\n    {foreach from=\$options item=option name=row}\r\n      {if \$is_in_columns}<div class=\"column\">{/if}\r\n        <input type=\"radio\" name=\"{\$NAME}\" id=\"{\$NAME}_{\$counter}\" \r\n          value=\"{\$option.option_value}\"\r\n          {if \$VALUE == \$option.option_value}checked{/if} />\r\n          <label for=\"{\$NAME}_{\$counter}\">{\$option.option_name}</label>\r\n      {if \$is_in_columns}</div>{/if}\r\n      {if \$formatting == \"vertical\"}<br />{/if}\r\n      {assign var=counter value=\$counter+1}\r\n    {/foreach}\r\n\r\n    {if \$is_in_columns}</div>{/if}\r\n  {/foreach}\r\n\r\n  {if \$comments}<div class=\"cf_field_comments\">{\$comments}</div>{/if}\r\n{/if}'
      WHERE  field_type_identifier = 'radio_buttons'
    ");

    // checkboxes
    mysql_query("
      UPDATE {$g_table_prefix}field_types
      SET    view_field_rendering_type = 'php',
             view_field_php_function_source = 'core',
             view_field_php_function = 'ft_display_field_type_checkboxes',
             view_field_smarty_markup = '{strip}{if \$contents != \"\"}\r\n  {assign var=vals value=\"`\$g_multi_val_delimiter`\"|explode:\$VALUE}\r\n  {assign var=is_first value=true}\r\n  {strip}\r\n  {foreach from=\$contents.options item=curr_group_info name=group}\r\n    {assign var=options value=\$curr_group_info.options}\r\n    {foreach from=\$options item=option name=row}\r\n      {if \$option.option_value|in_array:\$vals}\r\n        {if \$is_first == false}, {/if}\r\n        {\$option.option_name}\r\n        {assign var=is_first value=false}\r\n      {/if}\r\n    {/foreach}\r\n  {/foreach}\r\n  {/strip}\r\n{/if}{/strip}',
             edit_field_smarty_markup = '{if \$contents == \"\"}\r\n  <div class=\"cf_field_comments\">{\$LANG.phrase_not_assigned_to_option_list}</div>\r\n{else}\r\n  {assign var=vals value=\"`\$g_multi_val_delimiter`\"|explode:\$VALUE}\r\n  {assign var=is_in_columns value=false}\r\n  {if \$formatting == \"cf_option_list_2cols\" || \r\n      \$formatting == \"cf_option_list_3cols\" || \r\n      \$formatting == \"cf_option_list_4cols\"}\r\n    {assign var=is_in_columns value=true}\r\n  {/if}\r\n\r\n  {assign var=counter value=\"1\"}\r\n  {foreach from=\$contents.options item=curr_group_info name=group}\r\n    {assign var=group_info value=\$curr_group_info.group_info}\r\n    {assign var=options value=\$curr_group_info.options}\r\n\r\n    {if \$group_info.group_name}\r\n      <div class=\"cf_option_list_group_label\">{\$group_info.group_name}</div>\r\n    {/if}\r\n\r\n    {if \$is_in_columns}<div class=\"{\$formatting}\">{/if}\r\n\r\n    {foreach from=\$options item=option name=row}\r\n      {if \$is_in_columns}<div class=\"column\">{/if}\r\n        <input type=\"checkbox\" name=\"{\$NAME}[]\" id=\"{\$NAME}_{\$counter}\" \r\n          value=\"{\$option.option_value|escape}\" \r\n          {if \$option.option_value|in_array:\$vals}checked{/if} />\r\n          <label for=\"{\$NAME}_{\$counter}\">{\$option.option_name}</label>\r\n      {if \$is_in_columns}</div>{/if}\r\n      {if \$formatting == \"vertical\"}<br />{/if}\r\n      {assign var=counter value=\$counter+1}\r\n    {/foreach}\r\n\r\n    {if \$is_in_columns}</div>{/if}\r\n  {/foreach}\r\n\r\n  {if {\$comments}\r\n    <div class=\"cf_field_comments\">{\$comments}</div> \r\n  {/if}\r\n{/if}'
      WHERE  field_type_identifier = 'checkboxes'
    ");

    // date
    mysql_query("
      UPDATE {$g_table_prefix}field_types
      SET    view_field_rendering_type = 'php',
             view_field_php_function_source = 'core',
             view_field_php_function = 'ft_display_field_type_date'
      WHERE  field_type_identifier = 'date'
    ");

    // time
    mysql_query("
      UPDATE {$g_table_prefix}field_types
      SET    view_field_rendering_type = 'none'
      WHERE  field_type_identifier = 'time'
    ");

    // phone
    mysql_query("
      UPDATE {$g_table_prefix}field_types
      SET    view_field_rendering_type = 'php',
             view_field_php_function_source = 'core',
             view_field_php_function = 'ft_display_field_type_phone_number'
      WHERE  field_type_identifier = 'phone'
    ");

    // code / markup
    mysql_query("
      UPDATE {$g_table_prefix}field_types
      SET    view_field_rendering_type = 'php',
             view_field_php_function_source = 'core',
             view_field_php_function = 'ft_display_field_type_code_markup'
      WHERE  field_type_identifier = 'code_markup'
    ");
  }

  if ($old_version_info["release_date"] < 20110630)
  {
    mysql_query("INSERT INTO {$g_table_prefix}settings (setting_name, setting_value, module) VALUES ('field_type_settings_shared_characteristics', 'field_comments:textbox,comments`textarea,comments`password,comments`dropdown,comments`multi_select_dropdown,comments`radio_buttons,comments`checkboxes,comments`date,comments`time,comments`phone,comments`code_markup,comments`file,comments`google_maps_field,comments`tinymce,comments|data_source:dropdown,contents`multi_select_dropdown,contents`radio_buttons,contents`checkboxes,contents|column_formatting:checkboxes,formatting`radio_buttons,formatting|maxlength_attr:textbox,maxlength|colour_highlight:textbox,highlight|folder_path:file,folder_path|folder_url:file,folder_url|permitted_file_types:file,folder_url|max_file_size:file,max_file_size|date_display_format:date,display_format|apply_timezone_offset:date,apply_timezone_offset', 'core')");
  }

  // yet more field type updates
  if ($old_version_info["release_date"] < 20110702)
  {
    mysql_query("UPDATE {$g_table_prefix}field_types SET field_type_name = '{\$LANG.word_time}' WHERE field_type_identifier = 'time'");
    mysql_query("UPDATE {$g_table_prefix}field_types SET field_type_name = '{\$LANG.word_date}' WHERE field_type_identifier = 'date'");
    mysql_query("UPDATE {$g_table_prefix}field_types SET field_type_name = '{\$LANG.phrase_phone_number}' WHERE field_type_identifier = 'phone'");
    mysql_query("UPDATE {$g_table_prefix}field_types SET field_type_name = '{\$LANG.phrase_code_markup_field}' WHERE field_type_identifier = 'code_markup'");

    mysql_query("
      UPDATE {$g_table_prefix}field_types
      SET    edit_field_smarty_markup = '{* figure out all the classes *}\r\n{assign var=classes value=\$height}\r\n{if \$highlight_colour}\r\n  {assign var=classes value=\"`\$classes` `\$highlight_colour`\"}\r\n{/if}\r\n{if \$input_length == \"words\" && \$maxlength != \"\"}\r\n  {assign var=classes value=\"`\$classes` cf_wordcounter max`\$maxlength`\"}\r\n{elseif \$input_length == \"chars\" && \$maxlength != \"\"}\r\n  {assign var=classes value=\"`\$classes` cf_textcounter max`\$maxlength`\"}\r\n{/if}\r\n\r\n<textarea name=\"{\$NAME}\" id=\"{\$NAME}_id\" class=\"{\$classes}\">{\$VALUE}</textarea>\r\n\r\n{if \$input_length == \"words\" && \$maxlength != \"\"}\r\n  <div class=\"cf_counter\" id=\"{\$NAME}_counter\">\r\n    {\$maxlength} {\$LANG.phrase_word_limit_p} <span></span> {\$LANG.phrase_remaining_words}\r\n  </div>\r\n{elseif \$input_length == \"chars\" && \$maxlength != \"\"}\r\n  <div class=\"cf_counter\" id=\"{\$NAME}_counter\">\r\n    {\$maxlength} {\$LANG.phrase_characters_limit_p} <span></span> {\$LANG.phrase_remaining_characters}\r\n  </div>\r\n{/if}\r\n\r\n{if \$comments}\r\n  <div class=\"cf_field_comments\">{\$comments|nl2br}</div>\r\n{/if}',
             resources_js = '/**\r\n * The following code provides a simple text/word counter option for any  \r\n * textarea. It either just keeps counting up, or limits the results to a\r\n * certain number - all depending on what the user has selected via the\r\n * field type settings.\r\n */\r\nvar cf_counter = {};\r\ncf_counter.get_max_count = function(el) {\r\n  var classes = \$(el).attr(''class'').split(\" \").slice(-1);\r\n  var max = null;\r\n  for (var i=0; i<classes.length; i++) {\r\n    var result = classes[i].match(/max(\\\\d+)/);\r\n    if (result != null) {\r\n      max = result[1];\r\n      break;\r\n    }\r\n  }\r\n  return max;\r\n}\r\n\r\n\$(function() {\r\n  \$(\"textarea[class~=''cf_wordcounter'']\").each(function() {\r\n    var max = cf_counter.get_max_count(this);\r\n    if (max == null) {\r\n      return;\r\n    }\r\n    \$(this).bind(\"keydown\", function() {\r\n      var val = \$(this).val();\r\n      var len        = val.split(/[\\\\s]+/);\r\n      var field_name = \$(this).attr(\"name\");\r\n      var num_words  = len.length - 1;\r\n      if (num_words > max) {\r\n        var allowed_words = val.split(/[\\\\s]+/, max);\r\n        truncated_str = allowed_words.join(\" \");\r\n        \$(this).val(truncated_str);\r\n      } else {\r\n        \$(\"#\" + field_name + \"_counter\").find(\"span\").html(parseInt(max) - parseInt(num_words));\r\n      }\r\n    });     \r\n    \$(this).trigger(\"keydown\");\r\n  });\r\n\r\n  \$(\"textarea[class~=''cf_textcounter'']\").each(function() {\r\n    var max = cf_counter.get_max_count(this);\r\n    if (max == null) {\r\n      return;\r\n    }\r\n    \$(this).bind(\"keydown\", function() {    \r\n      var field_name = \$(this).attr(\"name\");      \r\n      if (this.value.length > max) {\r\n        this.value = this.value.substring(0, max);\r\n      } else {\r\n        \$(\"#\" + field_name + \"_counter\").find(\"span\").html(max - this.value.length);\r\n      }\r\n    });\r\n    \$(this).trigger(\"keydown\");\r\n  }); \r\n});'
      WHERE  field_type_identifier = 'textarea'
    ");

    mysql_query("
      UPDATE {$g_table_prefix}field_types
      SET    view_field_smarty_markup = '{php}\r\n\$format = \$this->get_template_vars(\"phone_number_format\");\r\n\$values = explode(\"|\", \$this->get_template_vars(\"VALUE\"));\r\n\$pieces = preg_split(\"/(x+)/\", \$format, 0, PREG_SPLIT_DELIM_CAPTURE);\r\n\$counter = 1;\r\n\$output = \"\";\r\n\$has_content = false;\r\nforeach (\$pieces as \$piece)\r\n{\r\n  if (empty(\$piece))\r\n    continue;\r\n\r\n  if (\$piece[0] == \"x\") {    \r\n    \$value = (isset(\$values[\$counter-1])) ? \$values[\$counter-1] : \"\";\r\n    \$output .= \$value;\r\n    if (!empty(\$value))\r\n    {\r\n      \$has_content = true;\r\n    }\r\n    \$counter++;\r\n  } else {\r\n    \$output .= \$piece;\r\n  }\r\n}\r\n\r\nif (!empty(\$output) && \$has_content)\r\n  echo \$output;\r\n{/php}',
             edit_field_smarty_markup = '{php}\r\n\$format = \$this->get_template_vars(\"phone_number_format\");\r\n\$values = explode(\"|\", \$this->get_template_vars(\"VALUE\"));\r\n\$name   = \$this->get_template_vars(\"NAME\");\r\n\r\n\$pieces = preg_split(\"/(x+)/\", \$format, 0, PREG_SPLIT_DELIM_CAPTURE);\r\n\$counter = 1;\r\nforeach (\$pieces as \$piece)\r\n{\r\n  if (strlen(\$piece) == 0)\r\n    continue;\r\n\r\n  if (\$piece[0] == \"x\") {\r\n    \$size = strlen(\$piece); \r\n    \$value = (isset(\$values[\$counter-1])) ? \$values[\$counter-1] : \"\";\r\n    \$value = htmlspecialchars(\$value);\r\n    echo \"<input type=\\\\\"text\\\\\" name=\\\\\"{\$name}_\$counter\\\\\" value=\\\\\"\$value\\\\\"\r\n            size=\\\\\"\$size\\\\\" maxlength=\\\\\"\$size\\\\\" />\";\r\n    \$counter++;\r\n  } else {\r\n    echo \$piece;\r\n  }\r\n}\r\n{/php}\r\n{if \$comments}\r\n  <div class=\"cf_field_comments\">{\$comments}</div>\r\n{/if}'
      WHERE  field_type_identifier = 'phone'
    ");

    mysql_query("
      UPDATE {$g_table_prefix}field_types
      SET    edit_field_smarty_markup = '<div class=\"editor\">\r\n  <textarea id=\"{\$NAME}_id\" name=\"{\$NAME}\">{\$VALUE}</textarea>\r\n</div>\r\n<script>\r\n  var code_mirror_{\$NAME} = new CodeMirror.fromTextArea(\"{\$NAME}_id\", \r\n  {literal}{{/literal}\r\n    height: \"{\$height}px\",\r\n    path:   \"{\$g_root_url}/global/codemirror/js/\",\r\n    {if \$code_markup == \"HTML\" || \$code_markup == \"XML\"}\r\n      parserfile: [\"parsexml.js\"],\r\n      stylesheet: \"{\$g_root_url}/global/codemirror/css/xmlcolors.css\"\r\n    {elseif \$code_markup == \"CSS\"}\r\n      parserfile: [\"parsecss.js\"],\r\n      stylesheet: \"{\$g_root_url}/global/codemirror/css/csscolors.css\"\r\n    {elseif \$code_markup == \"JavaScript\"}  \r\n      parserfile: [\"tokenizejavascript.js\", \"parsejavascript.js\"],\r\n      stylesheet: \"{\$g_root_url}/global/codemirror/css/jscolors.css\"\r\n    {/if}\r\n  {literal}});{/literal}\r\n</script>\r\n\r\n{if \$comments}\r\n  <div class=\"cf_field_comments\">{\$comments}</div>\r\n{/if}'
      WHERE  field_type_identifier = 'code_markup'
    ");
  }

  if ($old_version_info["release_date"] < 20110716)
  {
    $field_type_info = ft_get_field_type_by_identifier("code_markup");
    foreach ($field_type_info["settings"] as $curr_field_type_info)
    {
    	if ($curr_field_type_info["field_setting_identifier"] != "height")
    	  continue;

    	$setting_id = $curr_field_type_info["setting_id"];
    	mysql_query("UPDATE {$g_table_prefix}field_type_settings SET default_value = '200' WHERE setting_id = $setting_id") or die(mysql_error());
    }
  }

  // update the Date field type for additional custom date formats
  if ($old_version_info["release_date"] < 20110811)
  {
		$field_type_info = ft_get_field_type_by_identifier("date");
		$field_type_id = $field_type_info["field_type_id"];
		$custom_date_format_info = ft_get_field_type_setting_by_identifier($field_type_id, "display_format");

		$setting_id = $custom_date_format_info["setting_id"];
		mysql_query("DELETE FROM {$g_table_prefix}field_type_setting_options WHERE setting_id = $setting_id");

		mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES ($setting_id, '2011-11-30', 'yy-mm-dd', 1, 'yes')");
		mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES ($setting_id, '30/11/2011 (dd/mm/yyyy)', 'dd/mm/yy', 2, 'yes')");
		mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES ($setting_id, '11/30/2011 (mm/dd/yyyy)', 'mm/dd/yy', 3, 'yes')");
		mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES ($setting_id, 'Nov 30, 2011', 'M d, yy', 4, 'yes')");
		mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES ($setting_id, 'November 30, 2011', 'MM d, yy', 5, 'yes')");
		mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES ($setting_id, 'Wed Nov 30, 2011 ', 'D M d, yy', 6, 'yes')");
		mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES ($setting_id, 'Wednesday, November 30, 2011', 'DD, MM d, yy', 7, 'yes')");
		mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES ($setting_id, '30. 08. 2011.', 'dd. mm. yy.', 8, 'yes')");
		mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES ($setting_id, '30/11/2011 8:00 PM', 'datetime:dd/mm/yy|h:mm TT|ampm`true', 9, 'yes')");
		mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES ($setting_id, '11/30/2011 8:00 PM', 'datetime:mm/dd/yy|h:mm TT|ampm`true', 10, 'yes')");
		mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES ($setting_id, '2011-11-30 8:00 PM', 'datetime:yy-mm-dd|h:mm TT|ampm`true', 11, 'yes')");
		mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES ($setting_id, '2011-11-30 20:00', 'datetime:yy-mm-dd|hh:mm', 12, 'yes')");
		mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES ($setting_id, '2011-11-30 20:00:00', 'datetime:yy-mm-dd|hh:mm:ss|showSecond`true', 13, 'yes')");
		mysql_query("INSERT INTO {$g_table_prefix}field_type_setting_options VALUES ($setting_id, '30. 08. 2011. 20:00', 'datetime:dd. mm. yy.|hh:mm', 14, 'yes')");

    mysql_query("
      UPDATE {$g_table_prefix}field_types
      SET    view_field_smarty_markup = '{strip}\r\n  {if \$VALUE}\r\n    {assign var=tzo value=\"\"}\r\n    {if \$apply_timezone_offset == \"yes\"}\r\n      {assign var=tzo value=\$ACCOUNT_INFO.timezone_offset}\r\n    {/if}\r\n    {if \$display_format == \"yy-mm-dd\" || !\$display_format}\r\n      {\$VALUE|custom_format_date:\$tzo:\"Y-m-d\"}\r\n    {elseif \$display_format == \"dd/mm/yy\"}\r\n      {\$VALUE|custom_format_date:\$tzo:\"d/m/Y\"}\r\n    {elseif \$display_format == \"mm/dd/yy\"}\r\n      {\$VALUE|custom_format_date:\$tzo:\"m/d/Y\"}\r\n    {elseif \$display_format == \"M d, yy\"}\r\n      {\$VALUE|custom_format_date:\$tzo:\"M j, Y\"}\r\n    {elseif \$display_format == \"MM d, yy\"}\r\n      {\$VALUE|custom_format_date:\$tzo:\"F j, Y\"}\r\n    {elseif \$display_format == \"D M d, yy\"}\r\n      {\$VALUE|custom_format_date:\$tzo:\"D M j, Y\"}\r\n    {elseif \$display_format == \"DD, MM d, yy\"}\r\n      {\$VALUE|custom_format_date:\$tzo:\"l M j, Y\"}\r\n    {elseif \$display_format == \"dd. mm. yy.\"}\r\n      {\$VALUE|custom_format_date:\$tzo:\"d. m. Y.\"}\r\n    {elseif \$display_format == \"datetime:dd/mm/yy|h:mm TT|ampm`true\"}\r\n      {\$VALUE|custom_format_date:\$tzo:\"d/m/Y g:i A\"}\r\n    {elseif \$display_format == \"datetime:mm/dd/yy|h:mm TT|ampm`true\"}\r\n      {\$VALUE|custom_format_date:\$tzo:\"m/d/Y g:i A\"}\r\n    {elseif \$display_format == \"datetime:yy-mm-dd|h:mm TT|ampm`true\"}\r\n      {\$VALUE|custom_format_date:\$tzo:\"Y-m-d g:i A\"}\r\n    {elseif \$display_format == \"datetime:yy-mm-dd|hh:mm\"}\r\n      {\$VALUE|custom_format_date:\$tzo:\"Y-m-d H:i\"}\r\n    {elseif \$display_format == \"datetime:yy-mm-dd|hh:mm:ss|showSecond`true\"}\r\n      {\$VALUE|custom_format_date:\$tzo:\"Y-m-d H:i:s\"}\r\n    {elseif \$display_format == \"datetime:dd. mm. yy.|hh:mm\"}\r\n      {\$VALUE|custom_format_date:\$tzo:\"d. m. Y. H:i\"}\r\n    {/if}\r\n{/if}{/strip}',
             edit_field_smarty_markup = '{assign var=class value=\"cf_datepicker\"}\r\n{if \$display_format|strpos:\"datetime\" === 0}\r\n  {assign var=class value=\"cf_datetimepicker\"}\r\n{/if}\r\n\r\n{assign var=\"val\" value=\"\"}\r\n{if \$VALUE}\r\n  {assign var=tzo value=\"\"}\r\n  {if \$apply_timezone_offset == \"yes\"}\r\n    {assign var=tzo value=\$ACCOUNT_INFO.timezone_offset}\r\n  {/if}\r\n  {if \$display_format == \"yy-mm-dd\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"Y-m-d\"}\r\n  {elseif \$display_format == \"dd/mm/yy\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"d/m/Y\"}\r\n  {elseif \$display_format == \"mm/dd/yy\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"m/d/Y\"}\r\n  {elseif \$display_format == \"M d, yy\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"M j, Y\"}\r\n  {elseif \$display_format == \"MM d, yy\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"F j, Y\"}\r\n  {elseif \$display_format == \"D M d, yy\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"D M j, Y\"}\r\n  {elseif \$display_format == \"DD, MM d, yy\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"l M j, Y\"}\r\n  {elseif \$display_format == \"dd. mm. yy.\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"d. m. Y.\"}\r\n  {elseif \$display_format == \"datetime:dd/mm/yy|h:mm TT|ampm`true\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"d/m/Y g:i A\"}\r\n  {elseif \$display_format == \"datetime:mm/dd/yy|h:mm TT|ampm`true\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"m/d/Y g:i A\"}\r\n  {elseif \$display_format == \"datetime:yy-mm-dd|h:mm TT|ampm`true\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"Y-m-d g:i A\"}\r\n  {elseif \$display_format == \"datetime:yy-mm-dd|hh:mm\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"Y-m-d H:i\"}\r\n  {elseif \$display_format == \"datetime:yy-mm-dd|hh:mm:ss|showSecond`true\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"Y-m-d H:i:s\"}\r\n  {elseif \$display_format == \"datetime:dd. mm. yy.|hh:mm\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"d. m. Y. H:i\"}\r\n  {/if}\r\n{/if}\r\n\r\n<div class=\"cf_date_group\">\r\n  <input type=\"input\" name=\"{\$NAME}\" id=\"{\$NAME}_id\" \r\n    class=\"cf_datefield {\$class}\" value=\"{\$val}\" /><img class=\"ui-datepicker-trigger\" src=\"{\$g_root_url}/global/images/calendar.png\" id=\"{\$NAME}_icon_id\" />\r\n  <input type=\"hidden\" id=\"{\$NAME}_format\" value=\"{\$display_format}\" />\r\n  {if \$comments}\r\n    <div class=\"cf_field_comments\">{\$comments}</div>\r\n  {/if}\r\n</div>',
             php_processing = '\$field_name     = \$vars[\"field_info\"][\"field_name\"];\r\n\$date           = \$vars[\"data\"][\$field_name];\r\n\$display_format = \$vars[\"settings\"][\"display_format\"];\r\n\$atzo           = \$vars[\"settings\"][\"apply_timezone_offset\"];\r\n\$account_info   = isset(\$vars[\"account_info\"]) ? \$vars[\"account_info\"] : array();\r\n\r\nif (empty(\$date))\r\n{\r\n  \$value = \"\";\r\n}\r\nelse\r\n{\r\n  if (strpos(\$display_format, \"datetime:\") === 0)\r\n  {\r\n    \$parts = explode(\" \", \$date);\r\n    switch (\$display_format)\r\n    {\r\n      case \"datetime:dd/mm/yy|h:mm TT|ampm`true\":\r\n        \$date = substr(\$date, 3, 2) . \"/\" . substr(\$date, 0, 2) . \"/\" . \r\n          substr(\$date, 6);\r\n        break;\r\n      case \"datetime:dd. mm. yy.|hh:mm\":\r\n        \$date = substr(\$date, 4, 2) . \"/\" . substr(\$date, 0, 2) . \"/\" . \r\n          substr(\$date, 8, 4) . \" \" . substr(\$date, 14);\r\n        break;\r\n    }\r\n  }\r\n  else\r\n  {\r\n    if (\$display_format == \"dd/mm/yy\")\r\n    {\r\n      \$date = substr(\$date, 3, 2) . \"/\" . substr(\$date, 0, 2) . \"/\" . \r\n        substr(\$date, 6);\r\n    } \r\n    else if (\$display_format == \"dd. mm. yy.\")\r\n    {\r\n      \$parts = explode(\" \", \$date);\r\n      \$date = trim(\$parts[1], \".\") . \"/\" . trim(\$parts[0], \".\") . \"/\" . trim(\$parts[2], \".\");\r\n    }\r\n  }\r\n\r\n  \$time = strtotime(\$date);\r\n  \r\n  // lastly, if this field has a timezone offset being applied to it, do the\r\n  // appropriate math on the date\r\n  if (\$atzo == \"yes\" && !isset(\$account_info[\"timezone_offset\"]))\r\n  {\r\n    \$seconds_offset = \$account_info[\"timezone_offset\"] * 60 * 60;\r\n    \$time += \$seconds_offset;\r\n  }\r\n\r\n  \$value = date(\"Y-m-d H:i:s\", \$time);\r\n}\r\n\r\n'
      WHERE  field_type_id = $field_type_id
        ");
  }


  // 2.1.3: swatches + new "email_template_when_sent_views" table to log multiple "when sent" email-View mapping
  $has_problems = false;
  if ($old_version_info["release_date"] < 20110927)
  {
  	$upgrade_attempted = true;
  	$queries = array();
  	$queries[] = "
  	  ALTER TABLE {$g_table_prefix}themes
  	    ADD uses_swatches ENUM('yes', 'no') NOT NULL DEFAULT 'no' AFTER theme_name,
        ADD swatches MEDIUMTEXT NULL AFTER uses_swatches
  	";
  	$queries[] = "ALTER TABLE {$g_table_prefix}accounts ADD swatch VARCHAR(255) NOT NULL AFTER theme";
    $queries[] = "UPDATE {$g_table_prefix}accounts SET swatch = 'green' WHERE theme = 'default'";
    $queries[] = "
      CREATE TABLE {$g_table_prefix}email_template_when_sent_views (
        email_id MEDIUMINT NOT NULL,
        view_id MEDIUMINT NOT NULL
      ) DEFAULT CHARSET=utf8
    ";
    $find_query = mysql_query("
      SELECT email_id, view_mapping_view_id
      FROM   {$g_table_prefix}email_templates
      WHERE  view_mapping_view_id != '' AND view_mapping_view_id IS NOT NULL
    ");
    while ($row = mysql_fetch_assoc($find_query))
    {
		  $email_id = $row["email_id"];
		  $view_id  = $row["view_mapping_view_id"];
      $queries[] = "INSERT INTO {$g_table_prefix}email_template_when_sent_views (email_id, view_id) VALUES ($email_id, $view_id)";
    }

    ft_set_settings(array("default_client_swatch" => "green"));
  	foreach ($queries as $query)
  	{
  		$result = @mysql_query($query);
      if (!$result)
      {
      	$has_problems = true;
      	$success      = false;
				$mysql_error  = "<i>$query></i> [" . mysql_error() . "]";

        $error_message = ft_eval_smarty_string($LANG["notify_problem_upgrading"], array("version" => $g_current_version));
        $link_text     = ft_eval_smarty_string($LANG["phrase_upgrade_problem_link"], array("link" => "http://docs.formtools.org/upgrading/?page=problems_upgrading"));
      	$message = $error_message . " " . $mysql_error . "<br />" . $_LANG["phrase_upgrade_problem_link"] . " " . $link_text;
      	break;
      }
  	}

  	// if there were ANY problems, undo all the changes we just did
  	if ($has_problems)
  	{
    	@mysql_query("ALTER TABLE {$g_table_prefix}themes DROP uses_swatches");
    	@mysql_query("ALTER TABLE {$g_table_prefix}themes DROP swatches");
    	@mysql_query("ALTER TABLE {$g_table_prefix}accounts DROP swatch");
    	@mysql_query("DROP TABLE {$g_table_prefix}email_template_when_sent_views");
    	@mysql_query("DELETE FROM {$g_table_prefix}settings WHERE setting_name='default_client_swatch' AND module='core'");
    }
    else
    {
    	// delete the old view_mapping_view_id column from the email_templates table
      @mysql_query("ALTER TABLE {$g_table_prefix}email_templates DROP view_mapping_view_id");

    	// refresh the theme list. This updates Form Tools to recognize the new swatches for the default theme, saving
    	// the administrator from having to click the "Refresh Theme List" button
      ft_update_theme_list();
    }
  }


  // 2.1.4: field validation
  $has_problems = false;
  if ($old_version_info["release_date"] < 20111007)
  {
  	$upgrade_attempted = true;
  	@mysql_query("ALTER TABLE {$g_table_prefix}form_fields DROP option_list_id");
    @mysql_query("ALTER TABLE {$g_table_prefix}modules CHANGE module_key module_key VARCHAR(15)");

    $queries = array();
    $queries[] = "
      CREATE TABLE {$g_table_prefix}field_type_validation_rules (
        rule_id mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
        field_type_id mediumint(9) NOT NULL,
        rsv_rule varchar(50) NOT NULL,
        rule_label varchar(100) NOT NULL,
        rsv_field_name varchar(255) NOT NULL,
        custom_function varchar(100) NOT NULL,
        custom_function_required enum('yes','no','na') NOT NULL DEFAULT 'na',
        default_error_message mediumtext NOT NULL,
        list_order smallint(6) NOT NULL,
        PRIMARY KEY (rule_id)
      ) DEFAULT CHARSET=utf8
    ";

    $queries[] = "INSERT INTO {$g_table_prefix}field_type_validation_rules VALUES(1, 1, 'required', '{\$LANG.word_required}', '{\$field_name}', '', 'no', '{\$LANG.validation_default_rule_required}', 1)";
    $queries[] = "INSERT INTO {$g_table_prefix}field_type_validation_rules VALUES(2, 1, 'valid_email', '{\$LANG.phrase_valid_email}', '{\$field_name}', '', 'no', '{\$LANG.validation_default_rule_valid_email}', 2)";
    $queries[] = "INSERT INTO {$g_table_prefix}field_type_validation_rules VALUES(3, 1, 'digits_only', '{\$LANG.phrase_numbers_only}', '{\$field_name}', '', 'no', '{\$LANG.validation_default_rule_numbers_only}', 3)";
    $queries[] = "INSERT INTO {$g_table_prefix}field_type_validation_rules VALUES(4, 1, 'letters_only', '{\$LANG.phrase_letters_only}', '{\$field_name}', '', 'no', '{\$LANG.validation_default_rule_letters_only}', 4)";
    $queries[] = "INSERT INTO {$g_table_prefix}field_type_validation_rules VALUES(5, 1, 'is_alpha', '{\$LANG.phrase_alphanumeric}', '{\$field_name}', '', 'no', '{\$LANG.validation_default_rule_alpha}', 5)";
    $queries[] = "INSERT INTO {$g_table_prefix}field_type_validation_rules VALUES(6, 2, 'required', '{\$LANG.word_required}', '{\$field_name}', '', '', '{\$LANG.validation_default_rule_required}', 1)";
    $queries[] = "INSERT INTO {$g_table_prefix}field_type_validation_rules VALUES(7, 3, 'required', '{\$LANG.word_required}', '{\$field_name}', '', '', '{\$LANG.validation_default_rule_required}', 1)";
    $queries[] = "INSERT INTO {$g_table_prefix}field_type_validation_rules VALUES(8, 4, 'required', '{\$LANG.word_required}', '{\$field_name}', '', '', '{\$LANG.validation_default_rule_required}', 1)";
    $queries[] = "INSERT INTO {$g_table_prefix}field_type_validation_rules VALUES(9, 5, 'required', '{\$LANG.word_required}', '{\$field_name}[]', '', 'no', '{\$LANG.validation_default_rule_required}', 1)";
    $queries[] = "INSERT INTO {$g_table_prefix}field_type_validation_rules VALUES(10, 6, 'required', '{\$LANG.word_required}', '{\$field_name}', '', '', '{\$LANG.validation_default_rule_required}', 1)";
    $queries[] = "INSERT INTO {$g_table_prefix}field_type_validation_rules VALUES(11, 7, 'required', '{\$LANG.word_required}', '{\$field_name}[]', '', '', '{\$LANG.validation_default_rule_required}', 1)";
    $queries[] = "INSERT INTO {$g_table_prefix}field_type_validation_rules VALUES(12, 8, 'required', '{\$LANG.word_required}', '{\$field_name}', '', 'no', '{\$LANG.validation_default_rule_required}', 1)";
    $queries[] = "INSERT INTO {$g_table_prefix}field_type_validation_rules VALUES(13, 9, 'required', '{\$LANG.word_required}', '{\$field_name}', '', 'no', '{\$LANG.validation_default_rule_required}', 1)";
    $queries[] = "INSERT INTO {$g_table_prefix}field_type_validation_rules VALUES(14, 10, 'function', '{\$LANG.word_required}', '', 'cf_phone.check_required', 'yes', '{\$LANG.validation_default_phone_num_required}', 1)";
    $queries[] = "INSERT INTO {$g_table_prefix}field_type_validation_rules VALUES(15, 11, 'function', '{\$LANG.word_required}', '', 'cf_code.check_required', 'yes', '{\$LANG.validation_default_rule_required}', 1)";

    $queries[] = "
      CREATE TABLE {$g_table_prefix}field_validation (
        rule_id mediumint(8) unsigned NOT NULL,
        field_id mediumint(9) NOT NULL,
        error_message mediumtext NOT NULL,
        UNIQUE KEY rule_id (rule_id,field_id)
      ) DEFAULT CHARSET=utf8
    ";

    // now update the field types that have changed: phone & code/markup
    $queries[] = "UPDATE {$g_table_prefix}field_types SET resources_js = 'var cf_phone = {};\r\ncf_phone.check_required = function() {\r\n  var errors = [];\r\n  for (var i=0; i<rsv_custom_func_errors.length; i++) {\r\n    if (rsv_custom_func_errors[i].func != \"cf_phone.check_required\") {\r\n      continue;\r\n    }\r\n    var field_name = rsv_custom_func_errors[i].field;\r\n    var fields = $(\"input[name^=\\\\\"\" + field_name + \"_\\\\\"]\");\r\n    fields.each(function() {\r\n      if (!this.name.match(/_(\\\\d+)$/)) {\r\n        return;\r\n      }\r\n      var req_len = $(this).attr(\"maxlength\");\r\n      var actual_len = this.value.length;\r\n      if (req_len != actual_len || this.value.match(/\\\\D/)) {\r\n        var el = document.edit_submission_form[field_name];\r\n        errors.push([el, rsv_custom_func_errors[i].err]);\r\n        return false;\r\n      }\r\n    });\r\n  }\r\n\r\n  if (errors.length) {\r\n    return errors;\r\n  }\r\n\r\n  return true;\r\n  \r\n}' WHERE field_type_identifier = 'phone'";
    $queries[] = "UPDATE {$g_table_prefix}field_types SET resources_js = 'var cf_code = {};\r\ncf_code.check_required = function() {\r\n  var errors = [];\r\n  for (var i=0; i<rsv_custom_func_errors.length; i++) {\r\n    if (rsv_custom_func_errors[i].func != \"cf_code.check_required\") {\r\n      continue;\r\n    }\r\n    var field_name = rsv_custom_func_errors[i].field;\r\n    var val = \$.trim(window[\"code_mirror_\" + field_name].getCode());\r\n    if (!val) {\r\n      var el = document.edit_submission_form[field_name];\r\n      errors.push([el, rsv_custom_func_errors[i].err]);\r\n    }\r\n  }\r\n  if (errors.length) {\r\n    return errors;\r\n  }\r\n  return true;  \r\n}\r\n' WHERE field_type_identifier = 'code_markup'";

    foreach ($queries as $query)
    {
      $result = @mysql_query($query);
      if (!$result)
      {
        $has_problems = true;
        $success      = false;
        $mysql_error  = "<i>$query></i> [" . mysql_error() . "]";
        $error_message = ft_eval_smarty_string($LANG["notify_problem_upgrading"], array("version" => $g_current_version));
        $link_text     = ft_eval_smarty_string($LANG["phrase_upgrade_problem_link"], array("link" => "http://docs.formtools.org/upgrading/?page=problems_upgrading"));
        $message = $error_message . " " . $mysql_error . "<br />" . $_LANG["phrase_upgrade_problem_link"] . " " . $link_text;
        break;
      }
    }

    // if there were ANY problems, undo all the changes we just did
    if ($has_problems)
    {
      @mysql_query("DROP TABLE {$g_table_prefix}field_type_validation_rules");
      @mysql_query("DROP TABLE {$g_table_prefix}field_validation");
      // the changes to the field types don't need to be undone; they just added functions
    }
  }

  // 2.1.5
  $has_problems = false;
  if ($old_version_info["release_date"] < 20111022)
  {
    $upgrade_attempted = true;

    $setting = array("core_version_upgrade_track" => "unknown");
    ft_set_settings($setting);

    $queries = array();
    $queries[] = "
      ALTER TABLE {$g_table_prefix}forms
      CHANGE form_type form_type ENUM('internal', 'external', 'form_builder')
      CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'external'
    ";

    foreach ($queries as $query)
    {
      $result = @mysql_query($query);
      if (!$result)
      {
        $has_problems = true;
        $success      = false;
        $mysql_error  = "<i>$query></i> [" . mysql_error() . "]";
        $error_message = ft_eval_smarty_string($LANG["notify_problem_upgrading"], array("version" => $g_current_version));
        $link_text     = ft_eval_smarty_string($LANG["phrase_upgrade_problem_link"], array("link" => "http://docs.formtools.org/upgrading/?page=problems_upgrading"));
        $message = $error_message . " " . $mysql_error . "<br />" . $_LANG["phrase_upgrade_problem_link"] . " " . $link_text;
        break;
      }
    }
  }

  // ----------------------------------------------------------------------------------------------

  // if no problems were encountered, and the the full version string (version-type-date) has changed,
  // update the database
  if ($old_version_info["full"] != "{$g_current_version}-{$g_release_type}-{$g_release_date}" && !$has_problems)
  {
    $upgrade_attempted = true;

  	$upgrade_track = ft_get_settings("core_version_upgrade_track");
  	$upgrade_track .= ",{$g_current_version}-{$g_release_type}-{$g_release_date}";

    $new_settings = array(
      "program_version" => $g_current_version,
      "release_date"    => $g_release_date,
      "release_type"    => $g_release_type,
      "core_version_upgrade_track" => $upgrade_track
    );
    ft_set_settings($new_settings);

    // any time the Core version changes, we need to update the list of hooks found in the source files
    ft_update_available_hooks();
    $success = true;
  }

  return array(
    "upgraded" => $upgrade_attempted,
    "success"  => $success,
    "message"  => $message
  );
}


/**
 * Returns the current core version, whether it's a Beta or main release. The way Form Tools handles
 * the release versions was changed in 2.0.3-beta-20100919. Prior to that release, the "program_version"
 * setting stored the version information. The format changed depending on the release type:
 *    2.0.0               - example of main build version
 *    2.0.1-beta-20100931 - example of Beta version
 *
 * Now the current version information is stored in 3 fields in the settings table:
 *    "program_version" - JUST the main build info (e.g. 2.0.0)
 *    "release_type"    - "alpha", "beta" or "main"
 *    "release_date"    - the date in the format YYYYMMDD
 *
 *
 * @return array a hash with the following keys:
 *                    "version"      => e.g. 2.0.0
 *                    "release_type" => "main" or "beta"
 *                    "release_date" => e.g. 20081231
 *                    "full"         => e.g. 2.0.0-beta-20081231 or 2.0.3
 */
function ft_get_core_version_info()
{
  $settings = ft_get_settings();

  $program_version = $settings["program_version"];

  // if there's a hyphen in the program version, we know we're dealing with an old beta version
  if (strpos($program_version, "-") !== false)
  {
    $parts = split("-", $program_version);
    $version_info = array(
      "full"         => $program_version,
      "version"      => $parts[0],
      "release_type" => $parts[1],
      "release_date" => $parts[2]
    );
  }

  // otherwise, it's either an old MAIN release, or 2.0.3-beta-20100919 or later
  else
  {
    if ($program_version == "2.0.0")
    {
      $version_info = array(
        "full"         => "2.0.0-main-20100101",
        "version"      => "2.0.0",
        "release_date" => "20100101",
        "release_type" => "main"
      );
    }
    else if ($program_version == "2.0.1")
    {
      $version_info = array(
        "full"         => "2.0.1-main-20100412",
        "version"      => "2.0.1",
        "release_date" => "20100412",
        "release_type" => "main"
      );
    }
    else if ($program_version == "2.0.2")
    {
      $version_info = array(
        "full"         => "2.0.2-main-20100704",
        "version"      => "2.0.2",
        "release_date" => "20100704",
        "release_type" => "main"
      );
    }
    else
    {
      // here, there will always be release_date and release_type values in $settings, except
      // for ONE scenario: when a user is upgrading to that first version. Here, the DB won't have
      // any values for those fields (release_date, release_type). To get around this, we set the
      // release date to be the day before: this enables the calling function (ft_upgrade_form_tools)
      // to just seamlessly upgrade to the new versioning scheme. From there on out, we can rely
      // on those settings being in the database
      $release_date    = (isset($settings["release_date"])) ? $settings["release_date"] : "20100919";
      $release_type    = (isset($settings["release_type"])) ? $settings["release_type"] : "beta";

      $version_info = array(
        "full"         => "{$program_version}-{$release_type}-{$release_date}",
        "version"      => $program_version,
        "release_date" => $release_date,
        "release_type" => $release_type
      );
    }
  }

  return $version_info;
}


/**
 * Used in the modules. Basically this is the old version of ft_get_core_version_info,
 * left here for regression. Eventually the modules should be updated to use the same version
 * methodology as the core. It's way better.
 *
 * @param $version_string
 */
function ft_get_version_info($version_string)
{
  $parts = split("-", $version_string);

  $version_parts = array();
  $version_parts["full"] = $version_string;
  $version_parts["version"] = $parts[0];
  $version_parts["release_type"] = (count($parts) > 1) ? $parts[1] : "main";
  $version_parts["release_date"] = (count($parts) > 2) ? $parts[2] : "";

  return $version_parts;
}
