<?php

require_once("../global/library.php");
require_once("library.php");

// the home-stretch! populate the hooks table
ft_update_available_hooks();

// add whatever themes and modules are in the modules and themes folders
ft_update_module_list();
ft_update_theme_list();

// install the Core field types
list($success, $message) = ft_install_core_field_types("core_field_types");

$modules = ft_get_modules();

foreach ($modules as $module_info)
{
  $module_id     = $module_info["module_id"];
  $is_installed  = $module_info["is_installed"];
  $module_folder = $module_info["module_folder"];

  if ($is_installed == "yes")
    continue;

  $info = array("install" => $module_id);

  // if this was a premium module, pass along the appropriate encrypted info to allow installation
  if (isset($_SESSION["ft_install"]["premium_module_keys"]) && array_key_exists($module_folder, $_SESSION["ft_install"]["premium_module_keys"]))
  {
    $_POST["k"]  = $_SESSION["ft_install"]["premium_module_keys"][$module_folder]["k"];
    $_POST["ek"] = $_SESSION["ft_install"]["premium_module_keys"][$module_folder]["ek"];
    $info["k"]   = $_POST["k"];
  }

  // this will run the installation scripts for any module in the /modules folder. Note: the special "Core Field Types"
  // module has a dummy installation function that gets called here. That ensures the module is marked as "enabled", etc.
  // even though we actually installed it above.
  ft_install_module($info);
}

// send "Welcome to Form Tools" email
if (!isset($_SESSION["ft_install"]["email_notification_sent"]))
{
  $email    = $_SESSION["ft_install"]["email"];
  $username = $_SESSION["ft_install"]["username"];
  $password = $_SESSION["ft_install"]["password"];

  ft_install_send_welcome_email($email, $username, $password);
  $_SESSION["ft_install"]["email_notification_sent"] = true;
}


$page_vars = array();
$page_vars["step"] = 6;
$page_vars["g_root_url"] = $g_root_url;

ft_install_display_page("templates/step6.tpl", $page_vars);
