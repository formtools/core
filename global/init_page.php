<?php

// our Smarty instance, used for rendering the webpages
$g_smarty = new Smarty();

// if the config file exists, we can assume the user isn't installed
$g_link = ft_db_connect();

// load the appropriate language file
$g_language = ft_get_ui_language();
require_once("$folder/lang/{$g_language}.php");

if (isset($_GET["logout"]))
  ft_logout_user();
