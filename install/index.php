<?php

require("library.php");

if (isset($_POST["next"]))
{
  header("location: step2.php");
  exit;
}

$page_vars = array();
$page_vars["step"] = 1;
$page_vars["available_languages"] = ft_install_get_languages();
$page_vars["lang_file"] = $lang_file;

ft_install_display_page("templates/index.tpl", $page_vars);