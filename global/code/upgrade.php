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
 * This function upgrades Form Tools to the latest version of Form Tools - from 1.5.0 or 1.5.1 at
 * the earliest. Other versions need to upgrade to 1.5.x first.
 *
 * @return boolean is_upgraded a boolean indicating whether or not the program was just upgraded.
 */
function ft_upgrade_form_tools()
{
	return false;
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
 * Helper function to return the current version of Form Tools as a number. The assumption is that
 * all Form Tools versions have three sections, e.g. 1.5.0 or 2.0.0.
 *
 * @return integer The current Form Tools version as a number (e.g. 145 -> 1.4.5)
 */
function ft_get_version_as_number()
{
	$settings = ft_get_settings();
	$version = $settings['program_version'];
	$version = preg_replace("/\D/", "", $version);

	return $version;
}

