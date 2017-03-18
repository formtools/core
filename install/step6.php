<?php

require_once("../global/library.php");
require_once("library.php");

use FormTools\Core;
use FormTools\Hooks;
use FormTools\Installation;
use FormTools\Modules;
use FormTools\Themes;

Core::init();


// the home-stretch! populate the hooks table
Hooks::updateAvailableHooks();

// add whatever themes and modules are in the modules and themes folders
Modules::updateModuleList();
Themes::updateThemeList();

// install the Core field types
//list($success, $message) = Installation::installCoreFieldTypes("core_field_types");

//$modules = Modules::get();
//foreach ($modules as $module_info) {
//	$module_id     = $module_info["module_id"];
//	$is_installed  = $module_info["is_installed"];
//	$module_folder = $module_info["module_folder"];
//
//	if ($is_installed == "yes") {
//        continue;
//    }
//
//	$info = array("install" => $module_id);
//
//	// this will run the installation scripts for any module in the /modules folder. Note: the special "Core Field Types"
//	// module has a dummy installation function that gets called here. That ensures the module is marked as "enabled", etc.
//	// even though we actually installed it above.
//	ft_install_module($info);
//}

// send "Welcome to Form Tools" email
//if (!isset($_SESSION["ft_install"]["email_notification_sent"])) {
//	$email    = $_SESSION["ft_install"]["email"];
//	$username = $_SESSION["ft_install"]["username"];
//	$password = $_SESSION["ft_install"]["password"];
//
//	ft_install_send_welcome_email($email, $username, $password);
//	$_SESSION["ft_install"]["email_notification_sent"] = true;
//}


$page = array(
    "step" => 6,
    "g_root_url" => Core::getRootURL()
);

Installation::displayPage("templates/step6.tpl", $page);
