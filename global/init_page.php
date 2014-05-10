<?php

// our Smarty instance, used for rendering the webpages
$g_smarty = new Smarty();

$config_file_exists = false;
if (is_file(dirname(__FILE__) . "/config.php"))
{
  $config_file_exists = true;
  include_once(dirname(__FILE__) . "/config.php");
}

if ($config_file_exists)
{
  // if the config file exists, we can assume the user isn't installed
  $g_link = ft_db_connect();

  // load the appropriate language file
  $g_language = ft_get_ui_language();
  require_once(dirname(__FILE__) . "/lang/{$g_language}.php");

  if (isset($_GET["logout"]))
    ft_logout_user();
}