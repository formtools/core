<?php

/**
 * This file contains all code relating to upgrading Form Tools.
 *
 * @copyright Encore Web Studios 2010
 * @author Encore Web Studios <formtools@encorewebstudios.com>
 * @package 2-0-3
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
 * @return boolean is_upgraded a boolean indicating whether or not the program was just upgraded.
 */
function ft_upgrade_form_tools()
{
  global $g_table_prefix, $g_current_version, $g_release_type, $g_release_date;

  $is_upgraded = false;
  $old_version_info = ft_get_core_version_info();


  // ----------------------------------------------------------------------------------------------
  // 2.0.0 beta updates

  if ($old_version_info["release_date"] < 20090113)
  {
    // add the Hooks table
    mysql_query("
      CREATE TABLE {$g_table_prefix}hooks (
        hook_id mediumint(8) unsigned NOT NULL auto_increment,
        action_location enum('start','end') NOT NULL,
        module_folder varchar(255) NOT NULL,
        core_function varchar(255) NOT NULL,
        hook_function varchar(255) NOT NULL,
        priority tinyint(4) NOT NULL default '50',
        PRIMARY KEY (hook_id)
      ) TYPE=MyISAM DEFAULT CHARSET=utf8
      ");
  }

  if ($old_version_info["release_date"] < 20090301)
  {
    mysql_query("
      ALTER TABLE {$g_table_prefix}email_templates
      CHANGE email_reply_to email_reply_to
      ENUM('none', 'admin', 'client', 'user', 'custom')
      CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL
        ");
  }

  if ($old_version_info["release_date"] < 20090317)
  {
    mysql_query("
      ALTER TABLE {$g_table_prefix}views
      ADD may_add_submissions ENUM('yes', 'no') NOT NULL DEFAULT 'no'
        ");
  }

  if ($old_version_info["release_date"] < 20090402)
  {
    mysql_query("
      ALTER TABLE {$g_table_prefix}hooks
      ADD hook_type ENUM('code', 'template') NOT NULL DEFAULT 'code' AFTER hook_id
        ");
    mysql_query("
      ALTER TABLE {$g_table_prefix}hooks
      CHANGE action_location action_location VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL
        ");
    mysql_query("
      ALTER TABLE {$g_table_prefix}account_settings
      CHANGE setting_value setting_value MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL
        ");
  }

  if ($old_version_info["release_date"] < 20090510)
  {
    mysql_query("
      ALTER TABLE {$g_table_prefix}view_fields
      ADD is_searchable ENUM('yes','no') NOT NULL DEFAULT 'yes' AFTER is_editable
        ");
  }

  // bug #117
  if ($old_version_info["release_date"] < 20090627)
  {
    mysql_query("
      ALTER TABLE {$g_table_prefix}view_filters
      CHANGE operator operator ENUM('equals', 'not_equals', 'like', 'not_like', 'before', 'after' )
      CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'equals'
        ");
  }

  if ($old_version_info["release_date"] < 20090815)
  {
    mysql_query("
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
      mysql_query("
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
      mysql_query("UPDATE {$g_table_prefix}views SET has_standard_filter = 'yes' WHERE view_id = $view_id");
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
      ) TYPE=MyISAM DEFAULT CHARSET=utf8
        ");

    // [5] rename the "recipient_user_type" enum options to call the "user" option "form_email_field" instead,
    // but first, store all the recipient_ids so we can update them after the DB change
    $recipients_id_query = mysql_query("
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
    $forms_query = mysql_query("SELECT form_id, user_email_field, user_first_name_field, user_last_name_field FROM {$g_table_prefix}forms");
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
      "public_form_omit_list", "public_view_omit_list", "settings", "themes", "views",
      "view_fields", "view_filters", "view_tabs"
    );
    foreach ($core_tables as $table)
    {
      @mysql_query("ALTER TABLE {$g_table_prefix}$table TYPE=MyISAM");
    }

    // convert all the custom tables to MyISAM as well
    $forms = ft_get_forms();
    foreach ($forms as $form_info)
    {
      $form_id = $form_info["form_id"];
      @mysql_query("ALTER TABLE {$g_table_prefix}form_{$form_id} TYPE=MyISAM");
    }
  }

  // ----------------------------------------------------------------------------------------------

  // if the full version string (version-type-date) is different, update the database
  if ($old_version_info["full"] != "{$g_current_version}-{$g_release_type}-{$g_release_date}")
  {
  	$new_settings = array(
  	  "program_version" => $g_current_version,
  	  "release_date"    => $g_release_date,
  	  "release_type"    => $g_release_type
  	);
    ft_set_settings($new_settings);
    $is_upgraded = true;
  }

  return $is_upgraded;
}


/**
 * This function builds a series of hidden fields containing information about this users installation and
 * caches the string in sessions. This function is called on (admin) login, when themes and modules are
 * installed or deleted.
 *
 * The information is then processed on the Form Tools site to determine what, if anything, can be
 * upgraded, and determine compatibility conflicts, etc.
 */
function ft_build_and_cache_upgrade_info()
{
  $settings = ft_get_settings();

  // a hash of k => v storing the hidden field values to pass along
  $fields = array();

  // get the main build version
  $program_version = $settings["program_version"];
  $fields[] = array("k" => "m", "v" => $settings["program_version"]);
  $fields[] = array("k" => "beta", "v" => $settings["is_beta"]);
  $fields[] = array("k" => "api", "v" => $settings["api_version"]);

  if ($settings["is_beta"] == "yes")
    $fields[] = array("k" => "bv", "v" => $settings["beta_version"]);

  // get the theme info
  $themes = ft_get_themes();
  $count = 1;
  foreach ($themes as $theme_info)
  {
    $fields[] = array("k" => "t{$count}", "v" => $theme_info["theme_folder"]);
    $fields[] = array("k" => "tv{$count}", "v" => $theme_info["theme_version"]);
    $count++;
  }

  // get the module info
  $modules = ft_get_modules();
  $count = 1;
  foreach ($modules as $module_info)
  {
    $fields[] = array("k" => "m{$count}", "v" => $module_info["module_folder"]);
    $fields[] = array("k" => "mv{$count}", "v" => $module_info["version"]);
    $count++;
  }

  // save the link
  $_SESSION["ft"]["upgrade_info"] = $fields;
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
 *    "release_type"    - "beta" or "main"
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


