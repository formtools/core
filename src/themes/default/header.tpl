<!DOCTYPE html>
<html dir="{$LANG.special_text_direction}">
<head>
    {if !$swatch}{assign var=swatch value="green"}{/if}
    <title>{$head_title}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <link rel="shortcut icon" href="{$theme_url}/images/favicon.ico">
    {template_hook location="head_top"}
    <script>
        //<![CDATA[
        var g = {literal}{{/literal}
            root_url: "{$g_root_url}",
            error_colours: ["ffbfbf", "ffb5b5"],
            notify_colours: ["c6e2ff", "97c7ff"],
            js_debug:       {$g_js_debug}
            {literal}}{/literal};
        //]]>
    </script>
    <link type="text/css" rel="stylesheet" href="{$g_root_url}/global/css/main.css?v=3_0_3">
    <link type="text/css" rel="stylesheet" href="{$theme_url}/css/styles.css?v=3_0_3">
    <link type="text/css" rel="stylesheet" href="{$theme_url}/css/swatch_{$swatch}.css?v=3_0_3">
    <link href="{$theme_url}/css/smoothness/jquery-ui-1.8.6.custom.css" rel="stylesheet" type="text/css"/>
    <script src="{$g_root_url}/global/scripts/jquery.js"></script>
    <script src="{$theme_url}/scripts/jquery-ui.js"></script>
    <script src="{$g_root_url}/global/scripts/general.js?v=3_0_15"></script>
    <script src="{$g_root_url}/global/scripts/rsv.js?v=3_0_15"></script>
    {$head_string}
    {$head_js}
    {$head_css}
    {template_hook location="head_bottom"}
</head>
<body>
<div id="container">
    <div id="header">
        {if $is_logged_in && isset($settings.program_version)}
            <div style="float:right; display: flex">
                <img src="{$theme_url}/images/account_section_left_{$swatch}2x.png" border="0" height="25" width="8"/>
                <div id="account_section">
                    {if $settings.release_type == "alpha"}
                        <b>{$settings.program_version}-alpha-{$settings.release_date}</b>
                    {elseif $settings.release_type == "beta"}
                        <b>{$settings.program_version}-beta-{$settings.release_date}</b>
                    {else}
                        <b>{$settings.program_version}</b>
                    {/if}
                    {if $account.account_type == "admin" && !$g_hide_upgrade_link}
                        <span class="delimiter">|</span>
                        <a href="#" onclick="return ft.check_updates()" class="update_link">{$LANG.word_update}</a>
                    {/if}
                </div>
                <img src="{$theme_url}/images/account_section_right_{$swatch}2x.png" border="0" height="25" width="8"/>
            </div>
        {/if}

        <span style="float:left; padding-top: 4px">
      {if isset($settings) && isset($settings.logo_link)}
            <a href="{$settings.logo_link}">{/if}<img src="{$theme_url}/images/logo_{$swatch}2x.png" border="0"
                                                      width="220" height="67" />
                {if isset($settings) && isset($settings.logo_link)}</a>{/if}
    </span>
    </div>
    <div id="content">
        <table cellspacing="0" cellpadding="0" width="100%">
            <tr>
                <td width="180" valign="top">
                    <div id="left_nav">
                        {ft_include file="menu.tpl"}
                    </div>
                </td>
                <td valign="top">
                    <div style="width:740px">
