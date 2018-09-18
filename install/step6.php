<?php

require_once("../global/library.php");

use FormTools\Core;
use FormTools\Hooks;
use FormTools\Installation;
use FormTools\Modules;
use FormTools\Sessions;
use FormTools\Settings;
use FormTools\Themes;

Core::setHooksEnabled(false);
Core::initNoLogout();

Installation::checkInstallationComplete();

// the home-stretch! populate the hooks table
Hooks::updateAvailableHooks();

// add whatever themes and modules are in the modules and themes folders
Modules::updateModuleList();
Themes::updateThemeList();

Installation::installCoreFieldTypes();

// now actually install the modules
Modules::installModules();

Settings::set(array("installation_complete" => "yes"), "core");

// send "Welcome to Form Tools" email
if (!Sessions::exists("install_email_notification_sent")) {
	$email    = Sessions::get("install_email");
	$username = Sessions::get("install_username");

	Installation::sendWelcomeEmail($email, $username, $password);
	Sessions::set("install_email_notification_sent", true);
}

$page = array(
    "step" => 6,
    "g_root_url" => Core::getRootUrl()
);

Installation::displayPage("templates/step6.tpl", $page);
