{*
  A special template used to output general error / notifications, such as those pages
  when adding a form. Accepts the following variables:

  head_string
  head_js
  head_css
  message_type: "error" / "notification"
  message: text to display
  title: the title to display; if not specified, defaults to "NOTIFICATION" or "ERROR" depending on message type
  error_code / error_codes
  debugging: boolean (false by default)

*}
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html dir="{$LANG.special_text_direction}">
<head>
  <title>{$LANG.special_form_tools}</title>
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
  <script type="text/javascript" src="{$g_root_url}/global/scripts/scriptaculous.js?load=effects"></script>
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

    {if $SESSION.account.is_logged_in}
	    <div style="float:right">
	      <table cellspacing="0" cellpadding="0" height="25">
	      <tr>
	        <td><img src="{$theme_url}/images/account_section_left.jpg" border="0" /></td>
	        <td id="account_section">
	          <b>{$settings.program_version}</b>
	        </td>
	        <td><img src="{$theme_url}/images/account_section_right.jpg" border="0" /></td>
	      </tr>
	      </table>
	    </div>
    {/if}

    <span style="float:left; padding-top: 7px; padding-right: 10px">
      <img src="{$theme_url}/images/logo.jpg" border="0" width="220" height="61" />
    </span>
  </div>

  <div id="content">

    <div class="title underline">
			{if $message_type == "error"}
			  <span class="red bold">
			    {if $title}
			      {$title|upper}
			    {else}
			      {$LANG.word_error|upper}
			    {/if}
			  </span>
			{else}
	      <span class="blue bold">
	        {if $title}
	          {$title|upper}
	        {else
	          {$LANG.word_notification|upper}
	        {/if}
	      </span>
			{/if}
    </div>

    {if isset($message)}
	    <p>{$message}</p>
    {/if}

    {if isset($error_code)}
      <p>
        <b>{$LANG.phrase_type_c}
          {if $error_type == "system"}
            <span class="red">{$LANG.word_system}</span>
          {else}
            <span class="green">{$LANG.word_user}</span>
          {/if}<br />
        <b>{$LANG.phrase_code_c} #{$error_code}</b> &#8212;
        <a href="http://docs.formtools.org/api/index.php?page=error_codes#{$error_code}" target="_blank" />{$LANG.phrase_error_learn_more}</a>
      </p>
    {/if}

    {if isset($error_codes)}
      <p>
        <div>{$LANG.phrase_errors_learn_more}</div>

        <b>{$LANG.phrase_codes_c}</b>

        {foreach from=$error_codes item=row}
          <a href="http://docs.formtools.org/api/index.php?page=error_codes#{$row}" target="_blank" />{$row}</a>
        {/foreach}
      </p>
    {/if}

    {if isset($debugging)}
      <h4>{$LANG.word_debugging_c}</h4>
      <p>
        {$debugging}
      </p>
    {/if}

	</td>
</tr>
</table>

</div>


{* only display the footer area if there is some text entered for it *}
{if $account.settings.footer_text != ""}
  <div id="footer">
    <div style="padding-top:3px;">{$account.settings.footer_text}</div>
  </div>
{/if}

</body>
</html>
