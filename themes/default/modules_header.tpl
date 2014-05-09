{*
  This page is included in every administration page to generate the start of each the HTML pages, from the
  opening DOCTYPE to the head, and the opening structure of the pages.

  $head_title  - the <title> of the page
  $theme       - the theme folder name
  $logo_link   -
  $head_js     - anything that will be included within <script></script> tags.
  $head_css    - any CSS to be included within <style> tags.
  $nav_page    - the current page, used for the navigation column on the left
  $head_string - anything else to be outputted as is within the <head></head>
  $version     - the program version
*}
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html dir="{$LANG.special_text_direction}">
<head>
  <title>{$head_title}</title>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
  <link rel="shortcut icon" href="{$theme_url}/images/favicon.ico" >
  {template_hook location="modules_head_top"}
  <script>
  //<![CDATA[
  var g = {literal}{{/literal}
    root_url:       "{$g_root_url}",
    error_colours:  ["ffbfbf", "ffb5b5"],
    notify_colours: ["c6e2ff", "97c7ff"]
  {literal}}{/literal};
  //]]>
  </script>
  <link type="text/css" rel="stylesheet" href="{$g_root_url}/global/css/main.css?v=2_1_0">
  <link type="text/css" rel="stylesheet" href="{$theme_url}/css/styles.css?v=2_1_0">
  <link href="{$g_root_url}/global/css/smoothness/jquery-ui-1.8.6.custom.css?v=2_1_0" rel="stylesheet" type="text/css"/>
  <script type="text/javascript" src="{$g_root_url}/global/scripts/jquery.js?v=2_1_0"></script>
  <script type="text/javascript" src="{$g_root_url}/global/scripts/jquery-ui-1.8.6.custom.min.js"></script>
  <script type="text/javascript" src="{$g_root_url}/global/scripts/general.js?v=2_1_0"></script>
  <script type="text/javascript" src="{$g_root_url}/global/scripts/rsv.js?v=2_1_0"></script>
  {$head_string}
  {$head_js}
  {$head_css}
  {template_hook location="modules_head_top"}
</head>
<body>

<div id="container">

  <div id="header">

    <div style="float:right">
      <table cellspacing="0" cellpadding="0" height="25">
      <tr>
        <td><img src="{$theme_url}/images/account_section_left.jpg" border="0" /></td>
        <td id="account_section">
          {if $SESSION.account.is_logged_in}
            {if $settings.release_type == "alpha"}
              <b>{$settings.program_version}-alpha-{$settings.release_date}</b>
					  {elseif $settings.release_type == "beta"}
  	          <b>{$settings.program_version}-beta-{$settings.release_date}</b>
						{else}
						  <b>{$settings.program_version}</b>
						{/if}
            |
            <a href="#" onclick="return ft.check_updates()" class="update_link">{$LANG.word_update}</a>
          {/if}
        </td>
        <td><img src="{$theme_url}/images/account_section_right.jpg" border="0" /></td>
      </tr>
      </table>
    </div>

    <span style="float:left; padding-top: 8px; padding-right: 10px">
      <a href="{$settings.logo_link}"><img src="{$theme_url}/images/logo.jpg" border="0" width="220" height="61" /></a>
    </span>
  </div>

  <div id="content">

    <table cellspacing="0" cellpadding="0" width="100%">
    <tr>
      <td width="180" valign="top">

        <div id="left_nav">
          <div class="nav_heading">{$LANG.phrase_module_nav}</div>
          <div id="module_nav">
            {ft_include file="module_menu.tpl"}
          </div>

          <div id="nav_separator"> </div>

          <div class="nav_heading">
            {$LANG.phrase_main_nav}
          </div>
          <div id="main_nav">
            {ft_include file="menu.tpl"}
          </div>
        </div>

      </td>
      <td valign="top">
