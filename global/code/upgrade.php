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
 * This function does exactly what you'd expect: it builds custom upgrade link for this particular
 * installation and caches it in sessions. This function is called on (admin) login, when themes and
 * modules are installed or deleted.
 *
 * The link contains all the versions of the various components of the installation. The information
 * is then processed on the Form Tools site to determine what, if anything, can be upgraded, and
 * determine compatibility conflicts, etc.
 */
function ft_build_and_cache_upgrade_link()
{
	$settings = ft_get_settings();

  // get the main build version
  $program_version = $settings["program_version"];
  $is_beta = $settings["is_beta"];
  $query_string = "m={$program_version}&beta={$is_beta}";

  if ($is_beta == "yes")
  {
    $beta_version = $settings["beta_version"];
    $query_string .= "&bv=$beta_version";
  }

  // get the theme info
  $themes = ft_get_themes();
  $count = 1;
  foreach ($themes as $theme_info)
  {
	  $theme_folder  = $theme_info["theme_folder"];
	  $theme_version = $theme_info["theme_version"];
    $query_string .= "&t{$count}=$theme_folder&tv{$count}=$theme_version";
    $count++;
  }

	// get the module info
  $modules = ft_get_modules();
  $count = 1;
  foreach ($modules as $module_info)
  {
	  $module_folder  = $module_info["module_folder"];
	  $module_version = $module_info["version"];
    $query_string .= "&m{$count}=$module_folder&mv{$count}=$module_version";
    $count++;
  }

  // finally, add the API version
  $query_string .= "&api={$settings["api_version"]}";


	// save the link
  $link = "http://ft2.formtools.org/upgrade.php?$query_string";
  $_SESSION["ft"]["upgrade_link"] = $link;
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

