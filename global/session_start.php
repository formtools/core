<?php

if ($g_session_type == "database")
  $sess = new SessionManager();

if (!empty($g_session_save_path))
	session_save_path($g_session_save_path);

session_start();
header("Cache-control: private");
header("Content-Type: text/html; charset=utf-8");

$folder = dirname(__FILE__);
require_once("$folder/library.php");
