<?php

/**
 * This file defines all language/translation-related functions used in Form Tools. Note: the
 * ft_get_date function also permits localization of dates, but the general.php file seemed a
 * better location for it.
 *
 * @copyright Benjamin Keen 2012
 * @author Benjamin Keen <ben.keen@gmail.com>
 * @package 2-2-x
 * @subpackage Languages
 */


// -------------------------------------------------------------------------------------------------


/**
 * This special function is called on every page load within library.php, and is used to determine
 * the language for the current user.
 *
 * @return string $language the language string, e.g. "en_us", corresponding to the appropriate
 *          language file that will be loaded.
 */
function ft_get_ui_language()
{
  global $g_table_prefix, $g_root_dir, $g_root_url;

  // always set the default language to en_us
  $language = "en_us";

  // if the user isn't logged in, retrieve whatever language is most appropriate. Note that this
  // doesn't store the language ID in sessions: it keeps getting checked for as long as they're not
  // logged in
  if (!isset($_SESSION["ft"]["account"]["account_id"]))
  {
    if (!empty($g_root_url))
    {
      // this may fail if the DB isn't set up (i.e. during installation)
      $default_lang_result = @mysql_query("
        SELECT setting_value as lang
        FROM {$g_table_prefix}settings
        WHERE setting_name = 'default_language'
          ");

      if ($default_lang_result)
      {
        $query_info = mysql_fetch_assoc($default_lang_result);

        if (!empty($query_info))
          $language = $query_info["lang"];
      }
    }
  }

  // here they're logged in
  else
  {
    if (isset($_SESSION["ft"]["account"]["ui_language"]) && !empty($_SESSION["ft"]["account"]["ui_language"]))
    {
      // final check: check the language is a valid language file
      if (is_file("$g_root_dir/global/lang/{$_SESSION["ft"]["account"]["ui_language"]}.php"))
        $language = $_SESSION["ft"]["account"]["ui_language"];
    }
    else
    {
      // we check to see $g_root_url is defined for the very rare case where a person attempted
      // a previous installation and $_SESSION["ft"]["login_user_id"] is set, but the config.php file
      // no longer exists. This wards against an error occurring in that scenario
      if (!empty($g_root_url))
      {
        // this may fail if the DB isn't set up (i.e. during upgrading)
        $lang_result = @mysql_query("
          SELECT ui_language
          FROM   {$g_table_prefix}accounts
          WHERE  user_id = {$_SESSION["ft"]["account"]["account_id"]}
            ");

        if ($lang_result)
        {
          $query_info = mysql_fetch_assoc($lang_result);

          // see comment on previous if-statement
          if (!empty($query_info))
          {
            $language = $query_info["ui_language"];
            $_SESSION["ft"]["account"]["ui_language"] = $language;
          }
        }
      }
    }
  }

  return $language;
}


/**
 * Refreshes the list of available language files found in the /global/lang folder. This
 * function parses the folder and stores the language info in the "available_languages"
 * settings in the settings table.
 *
 * @return array [0]: true/false (success / failure)
 *               [1]: message string
 */
function ft_refresh_language_list()
{
  global $g_root_dir, $g_table_prefix, $LANG;

  $language_folder_dir = "$g_root_dir/global/lang";

  $available_language_info = array();
  if ($handle = opendir($language_folder_dir))
  {
    while (false !== ($filename = readdir($handle)))
    {
      if ($filename != '.' && $filename != '..' && $filename != "index.php" &&
        ft_get_filename_extension($filename, true) == "php")
      {
        list($lang_file, $lang_display) = _ft_get_language_file_info("$language_folder_dir/$filename");
        $available_language_info[$lang_file] = $lang_display;
      }
    }
    closedir($handle);
  }

  // sort the languages alphabetically
  ksort($available_language_info);

  // now piece everything together in a single string for storing in the database
  $available_languages = array();
  while (list($key,$val) = each($available_language_info))
    $available_languages[] = "$key,$val";
  $available_language_str = join("|", $available_languages);

  mysql_query("
    UPDATE {$g_table_prefix}settings
    SET    setting_value = '$available_language_str'
    WHERE  setting_name = 'available_languages'
      ");

  // update the values in sessions
  $_SESSION["ft"]["settings"]["available_languages"] = $available_language_str;

  return array(true, $LANG["notify_lang_list_updated"]);
}


/**
 * Helper function which examines a particular language file and returns the language
 * filename (en_us, fr_ca, etc) and the display name ("English (US), French (CA), etc).
 *
 * @param string $file the full path of the language file
 * @return array [0] the language file name<br />
 *               [1] the language display name
 */
function _ft_get_language_file_info($file)
{
  @include($file);

  $defined_vars = get_defined_vars();
  $language_display = $defined_vars["LANG"]["special_language_locale"];

  // now return the filename component, minus the .php
  $pathinfo = pathinfo($file);
  $lang_file = preg_replace("/\.php$/", "", $pathinfo["basename"]);

  return array($lang_file, $language_display);
}

