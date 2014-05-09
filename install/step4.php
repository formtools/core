<?php

require_once("library.php");
$_SESSION["ft_install"]["config_file"] = ft_install_get_config_file_contents();

$config_file_generated = "";
if (isset($_POST["generate_file"]))
{
  $config_file_generated = ft_install_generate_config_file();

  if ($config_file_generated)
  {
    header("location: step5.php");
    exit;
  }
}
if (isset($_POST["check_config_contents"]))
{
  list ($g_success, $g_message) = ft_install_check_config_file_exists();

  // great: the user has managed to manually create the file. Continue to the next step.
  if ($g_success)
  {
    header("location: step5.php");
    exit;
  }
  $config_file_generated = false;
}

// ------------------------------------------------------------------------------------------------

$page_vars = array();
$page_vars["step"] = 4;
$page_vars["config_file"] = $_SESSION["ft_install"]["config_file"];
$page_vars["config_file_generated"] = $config_file_generated;

ft_install_display_page("templates/step4.tpl", $page_vars);