<?php

$g_defer_init_page = true;
require_once(dirname(__FILE__) . "/library.php");

if ($g_session_type == "database")
{
  $g_link = ft_db_connect();
  $sess = new SessionManager();
}

if (!empty($g_session_save_path))
  session_save_path($g_session_save_path);

session_start();
header("Cache-control: private");
header("Content-Type: text/html; charset=utf-8");

require_once(dirname(__FILE__) . "/init_page.php");
