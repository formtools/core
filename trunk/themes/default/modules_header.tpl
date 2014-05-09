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

  <script type="text/javascript">
  //<![CDATA[
  var g = {literal}{}{/literal};
  g.root_url = "{$g_root_url}";
  g.error_colours = ["ffbfbf", "ffeded"];
  g.notify_colours = ["c6e2ff", "f2f8ff"];
  //]]>
  </script>

  <link type="text/css" rel="stylesheet" href="{$g_root_url}/global/css/main.css">
  <link type="text/css" rel="stylesheet" href="{$theme_url}/css/styles.css">
  <script type="text/javascript" src="{$g_root_url}/global/scripts/prototype.js"></script>
  <script type="text/javascript" src="{$g_root_url}/global/scripts/scriptaculous.js"></script>
  <script type="text/javascript" src="{$g_root_url}/global/scripts/effects.js"></script>
  <script type="text/javascript" src="{$g_root_url}/global/scripts/general.js"></script>
  <script type="text/javascript" src="{$g_root_url}/global/scripts/rsv.js"></script>

  {$head_string}
  {$head_js}
  {$head_css}

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
             <b>v{$settings.program_version}</b>
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
