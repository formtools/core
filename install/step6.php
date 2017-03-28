<?php

require_once("../global/library.php");

use FormTools\Core;
use FormTools\Hooks;
use FormTools\Installation;
use FormTools\Modules;
use FormTools\Themes;

Core::setHooksEnabled(false);
Core::init();

/// -------------------------------------

// the home-stretch! populate the hooks table
Hooks::updateAvailableHooks();

// add whatever themes and modules are in the modules and themes folders
Modules::updateModuleList();
Themes::updateThemeList();

Installation::installCoreFieldTypes("core_field_types");

// now actually install
Modules::installModules();


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
