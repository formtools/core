<?php

$folder = dirname(__FILE__);
$g_defer_init_page = true;
require_once("$folder/library.php");

if ($g_session_type == "database")
{
  $g_link = ft_db_connect(); // TODO confirm
	$sess = new SessionManager();
}

if (!empty($g_session_save_path))
	session_save_path($g_session_save_path);

session_start();
header("Cache-control: private");
header("Content-Type: text/html; charset=utf-8");

$folder = dirname(__FILE__);
require_once("$folder/init_page.php");