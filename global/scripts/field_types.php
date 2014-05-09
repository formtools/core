<?php

// currently this file is told to never cache. Need a better solution in the long term
header("Content-Type: text/javascript");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");

$g_check_ft_sessions = false;
require_once("../library.php");

echo ft_get_field_type_resources("js");