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
  g.error_colours = ["FFFC05", "ffffcc"];
  g.notify_colours = ["FFFC05", "ffffdd"];
  //]]>
  </script>

  <link type="text/css" rel="stylesheet" href="{$g_root_url}/global/css/main.css">
  <link type="text/css" rel="stylesheet" href="{$theme_url}/css/layout.css">
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

<div id="container" class="admin_container">

	<div id="header"><span
	  style="float:right"><img src="{$theme_url}/images/header_right_shadow.jpg" width="7" height="71" /></span><span
	  style="float:right"><img src="{$theme_url}/images/header_right.jpg" width="10" height="71" /></span><a
	  href="{$settings.logo_link}"><img src="{$theme_url}/images/header_logo.jpg" width="200" height="71" border="0" /></a></div>

	  <div class="outer">
	  <div class="inner">
	    <div class="float-wrap">
	    <div id="content">
	      <div id="main_top"></div>
	      <span style="float:right"><img src="{$theme_url}/images/main_right_shadow.jpg" width="7" height="292" /></span>

			  <div class="content_wrap">

			    <div id="page_content">
