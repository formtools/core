<?php

session_start();
header("Cache-control: private");
header("Content-Type: text/html; charset=utf-8");

// constants used throughout the installation script
define("DEFAULT_LANGUAGE", "en_us.php");
define("INSTALLATION_FOLDER", __DIR__);


// all session information for the installation script is stored in the $_SESSION["ft_install"] key
if (!isset($_SESSION["ft_install"])) {
	$_SESSION["ft_install"] = array();
}

// include the language file
if (!isset($_SESSION["ft_install"]["lang_file"])) {
	$_SESSION["ft_install"]["lang_file"] = DEFAULT_LANGUAGE;
}

require_once("../global/library.php");
require_once("files/sql.php");

// suppress any hook processing for the duration of the installation process
$g_hooks_enabled = false;

// http://www.smarty.net/docs/en/api.set.plugins.dir.tpl
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$lang_file = ft_load_field("lang_file", "lang_file", DEFAULT_LANGUAGE, "ft_install");
require_once("../global/lang/{$_SESSION["ft_install"]["lang_file"]}");
