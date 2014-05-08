<?php

$folder = dirname(__FILE__);
require_once("$folder/library.php");

if ($g_session_type == "database")
  $sess = new SessionManager();


session_start();
header("Cache-control: private");
header("Content-Type: text/html; charset=utf-8");
