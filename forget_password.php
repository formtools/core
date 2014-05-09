<?php

require("global/session_start.php");

$settings = ft_get_settings();
$g_title = $settings['program_name'];
$g_theme = $settings['default_theme'];
$admin_info = ft_get_admin_info();
$admin_email = $admin_info["email"];

// if a user id is included in the query string, use it to determine the appearance of the
// interface (including logo)
$id = ft_load_field("id", "id", "");

if (!empty($id))
{
  $info = ft_get_account_info($id);

  if (!empty($info))
  {
    $g_theme  = $info['theme'];
    $language = $info["ui_language"];
    include_once("global/lang/{$language}.php");
  }
}

// if trying to send password
if (isset($_POST) && !empty($_POST))
  list($g_success, $g_message) = ft_send_password($_POST);

$username = (isset($_POST["username"]) && !empty($_POST["username"])) ? $_POST["username"] : "";
$username = ft_strip_chars($username);

// --------------------------------------------------------------------------------------------

$replacements = array("site_admin_email" => "<a href=\"mailto:$admin_email\">$admin_email</a>");

$page_vars = array();
$page_vars["text_forgot_password"] = ft_eval_smarty_string($LANG["text_forgot_password"], $replacements);
$page_vars["head_title"] = $settings['program_name'];
$page_vars["page"] = "forgot_password";
$page_vars["page_url"] = ft_get_page_url("forgot_password");
$page_vars["settings"] = $settings;
$page_vars["username"] = $username;
$page_vars["head_js"] =<<<END
var rules = [];
rules.push("required,username,{$LANG['validation_no_username']}");
$(function() { document.forget_password.username.focus(); });
END;

ft_display_page("forget_password.tpl", $page_vars, $g_theme);