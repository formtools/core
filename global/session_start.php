<?php

session_start();
header("Cache-control: private");
header("Content-Type: text/html; charset=utf-8");

$folder = dirname(__FILE__);
require_once("$folder/library.php");
