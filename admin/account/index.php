<?php

require("../../global/session_start.php");
ft_check_permission("admin");

// update the administrator's account
if (isset($_POST) && !empty($_POST))
{
  list($g_success, $g_message) = ft_update_admin_account($_POST, $_SESSION["ft"]["account"]["account_id"]);

  // if the user just changed their language file, reset the value in sessions and refresh the page
  if ($g_success && ($_POST["old_ui_language"] != $_POST["ui_language"]))
  {
    $_SESSION["ft"]["ui_language"] = $_POST["ui_language"];
    session_write_close();
    header("location: index.php?updated");
  }
}

// here, the user has just changed their ui language
if (isset($_GET["updated"]))
{
  $g_success = true;
  $g_message = $LANG["notify_account_updated"];
}

$admin_info = ft_get_admin_info();

$replacement_info = array("datefunctionlink" => '<a href="http://ca3.php.net/manual/en/function.date.php" target="_blank">date()</a>');

// ------------------------------------------------------------------------------------------------

// compile the theme variables
$page_vars = array();
$page_vars["page"] = "your_account";
$page_vars["page_url"] = ft_get_page_url("your_account");
$page_vars["head_title"] = $LANG["phrase_your_account"];
$page_vars["admin_info"] = $admin_info;
$page_vars["text_date_formatting_link"] = ft_eval_smarty_string($LANG["text_date_formatting_link"], $replacement_info);
$page_vars["head_js"] =<<<END
  var rules = [];
  rules.push("required,first_name,{$LANG["validation_no_first_name"]}");
  rules.push("required,last_name,{$LANG["validation_no_last_name"]}");
  rules.push("required,email,{$LANG["validation_no_email"]}");
  rules.push("required,theme,{$LANG["validation_no_theme"]}");
  rules.push("function,validate_swatch");
  rules.push("required,login_page,{$LANG["validation_no_login_page"]}");
  rules.push("required,logout_url,{$LANG["validation_no_account_logout_url"]}");
  rules.push("required,ui_language,{$LANG["validation_no_ui_language"]}");
  rules.push("required,sessions_timeout,{$LANG["validation_no_sessions_timeout"]}");
  rules.push("required,date_format,{$LANG["validation_no_date_format"]}");
  rules.push("required,username,{$LANG["validation_no_username"]}");
  rules.push("function,validate_username");
  rules.push("if:password!=,required,password_2,{$LANG["validation_no_account_password_confirmed"]}");
  rules.push("if:password!=,same_as,password,password_2,{$LANG["validation_passwords_different"]}");

  function validate_swatch() {
    var theme     = $("#theme").val();
    var swatch_id = "#" + theme + "_theme_swatches";
    if ($(swatch_id).length > 0 && $(swatch_id).val() == "") {
      return [[$(swatch_id)[0], "{$LANG["validation_no_theme_swatch"]}"]];
    }
    return true;
  }
  function validate_username() {
    var username = $("input[name=username]").val();
    if (username.match(/[^\.@a-zA-Z0-9_]/)) {
      return [[$("input[name=username]")[0], "{$LANG['validation_invalid_admin_username']}"]];
    }
    return true;
  }

  $(function() { document.login_info.first_name.focus(); });
END;

ft_display_page("admin/account/index.tpl", $page_vars);
