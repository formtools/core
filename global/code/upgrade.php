<?php

/**
 * This file contains all code relating to upgrading Form Tools. Note: Form Tools 2 can only be upgraded
 * directly from Form Tools 1.5.0 and 1.5.1. All other version need to upgrade to one of those versions
 * first.
 *
 * @copyright Encore Web Studios 2008
 * @author Encore Web Studios <formtools@encorewebstudios.com>
 * @package 2-0-0
 * @subpackage Upgrade
 */

// -------------------------------------------------------------------------------------------------


/**
 * This function upgrades the Form Tools Core.
 *
 * TODO Improve Me.
 *
 * @return boolean is_upgraded a boolean indicating whether or not the program was just upgraded.
 */
function ft_upgrade_form_tools()
{
  global $g_table_prefix, $g_current_version;

  $is_upgraded = false;

  $settings = ft_get_settings();
  $existing_version_info = ft_get_version_info($settings["program_version"]);
  $current_version_info  = ft_get_version_info($g_current_version);

  // 2.0.0
  if ($existing_version_info["version"] == "2.0.0")
  {
    // BETA
    if ($existing_version_info["release_type"] == "beta")
    {
      if ($existing_version_info["release_date"] < 20090113)
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
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8
          ");
      }

      if ($existing_version_info["release_date"] < 20090301)
      {
      	mysql_query("
          ALTER TABLE {$g_table_prefix}email_templates
          CHANGE email_reply_to email_reply_to
          ENUM('none', 'admin', 'client', 'user', 'custom')
          CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL
            ");
      }

      if ($existing_version_info["release_date"] < 20090317)
      {
      	mysql_query("
      	  ALTER TABLE {$g_table_prefix}views
      	  ADD may_add_submissions ENUM('yes', 'no') NOT NULL DEFAULT 'no'
      	    ");
      }

      if ($existing_version_info["release_date"] < 20090402)
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

      if ($existing_version_info["release_date"] < 20090510)
      {
      	mysql_query("
          ALTER TABLE {$g_table_prefix}view_fields
          ADD is_searchable ENUM('yes','no') NOT NULL DEFAULT 'yes' AFTER is_editable
            ");
      }

      // bug #117
      if ($existing_version_info["release_date"] < 20090627)
      {
      	mysql_query("
          ALTER TABLE {$g_table_prefix}view_filters`
          CHANGE operator operator ENUM('equals', 'not_equals', 'like', 'not_like', 'before', 'after' )
          CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'equals'
            ");
      }

      if ($existing_version_info["full"] != $g_current_version)
      {
        mysql_query("
          UPDATE {$g_table_prefix}settings
          SET    setting_value = '$g_current_version'
          WHERE  setting_name = 'program_version'
                  ");
        $is_upgraded = true;
      }
    }
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
 * Returns the current core version, whether it's a Beta, release candidate or main release and the beta/rc
 * date.
 *
 * @return array a hash with the following keys:
 *                    "version" => e.g. 2.0.0
 *                    "release_type" => "main", "beta" or "rc"
 *                    "release_date" => e.g. 20081231 or empty, if not a beta or rc
 *                    "full" => e.g. 2.0.0-beta-20081231
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
