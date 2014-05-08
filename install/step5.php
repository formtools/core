<?php
session_start();
header("Cache-control: private");
header("Content-Type: text/html; charset=utf-8");
require("../global/library.php");
require("library.php");

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
rules.push("required,first_name,Please enter your first name.");
rules.push("required,last_name,Please enter your last name.");
rules.push("required,email,Please enter the administrator email address.");
rules.push("valid_email,email,Please enter a valid administrator email address.")
rules.push("required,username,Please enter your username.");
rules.push("is_alpha,username,Please make sure your username consist of alphanumeric (a-Z and 0-9) characters only.");
rules.push("required,password,Please enter your password.");
rules.push("is_alpha,password,Please make sure your password consist of alphanumeric (a-Z and 0-9) characters only.");
rules.push("required,password_2,Please re-enter your password.");
rules.push("same_as,password,password_2,Please ensure the passwords are the same.")

rsv.displayType = "alert-all";
rsv.errorTextIntro = "{$LANG["phrase_error_text_intro"]}";
EOF;

ft_install_display_page("templates/step5.tpl", $page_vars);