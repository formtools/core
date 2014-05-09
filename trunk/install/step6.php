<?php
session_start();
header("Cache-control: private");
header("Content-Type: text/html; charset=utf-8");
require("../global/library.php");
require("library.php");

// add whatever themes and modules are in the modules and themes folders
ft_update_module_list();
ft_update_theme_list();

$modules = ft_get_modules();

foreach ($modules as $module_info)
{
	$module_id    = $module_info["module_id"];
	$is_installed = $module_info["is_installed"];

	if ($is_installed == "yes")
	  continue;

	ft_install_module($module_id);
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
