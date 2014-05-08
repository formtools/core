<?php

session_start();
header("Cache-control: private");
header("Content-Type: text/html; charset=utf-8");

require_once("library.php");
require_once("files/sql.php");

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

// ------------------------------------------------------------------------------------------------

$page_vars = array();
$page_vars["step"] = 4;
$page_vars["config_file"] = $_SESSION["ft_install"]["config_file"];
$page_vars["config_file_generated"] = $config_file_generated;

ft_install_display_page("templates/step4.tpl", $page_vars);