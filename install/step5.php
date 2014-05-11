<?php

// bit weird, but deliberate. This first line will have $g_defer_init_page = false;
require_once("../global/library.php");
require_once("library.php");

// if required, add the user account
$account_created = false;
if (isset($_POST["add_account"]))
{
  list($account_created, $g_message) = ft_install_create_admin_account($_POST);

  // store the username and (unencrypted) password for later user
  $_SESSION["ft_install"]["email"] = $_POST["email"];
  $_SESSION["ft_install"]["username"] = $_POST["username"];
  $_SESSION["ft_install"]["password"] = $_POST["password"];

  // everything's done! Now just make a few minor updates to the database for this users configuration
  if ($account_created)
  {
    ft_install_update_db_settings();

    // redirect to the final page, which provides a few links to the help doc etc.
    header("location: step6.php");
    exit;
  }
}

$page_vars = array();
$page_vars["step"] = 5;
$page_vars["g_root_url"] = $g_root_url;
$page_vars["account_created"] = $account_created;
$page_vars["head_js"] =<<< EOF
var rules = [];
rules.push("required,first_name,{$LANG["validation_no_first_name"]}");
rules.push("required,last_name,{$LANG["validation_no_last_name"]}");
rules.push("required,email,{$LANG["validation_no_admin_email"]}");
rules.push("valid_email,email,{$LANG["validation_invalid_admin_email"]}");
rules.push("required,username,{$LANG["validation_no_username"]}");
rules.push("is_alpha,username,{$LANG['validation_invalid_admin_username']}");
rules.push("required,password,{$LANG["validation_no_password"]}");
rules.push("required,password_2,{$LANG["validation_no_second_password"]}");
rules.push("same_as,password,password_2,{$LANG["validation_passwords_different"]}")

rsv.displayType = "alert-all";
rsv.errorTextIntro = "{$LANG["phrase_error_text_intro"]}";
EOF;

ft_install_display_page("templates/step5.tpl", $page_vars);