{*
  Messages.tpl
  ------------

  This is a damn cool template. It blurs the lines between the client- and server-side error message
  display so that all UI messages are displayed by the HTML generated here. users won't be able to
  tell the difference between the two since they'll be styled identically. A couple of notes:

  1. If $g_message is populated from the result of some server side code, it displays the message
     doled up by the server - be it be an error or success message.
  2. If there was no error message it still creates a page element with the "ft_message" id, which is
     available to be used by an JS, e.g. validation script to insert the error.
  3. Since every page that includes this template is guaranteed to have an HTML element with ID
     "ft_message", the JS will automatically overwrite any server-side message with dynamically generated
     errors / notifications
  4. Spacing: It ALWAYS has a space of 10px at the bottom. This is nice since you don't have to worry
     about making sure the surrounding elements will be spaced right with or without some messages being
     displayed. [hardcoded inline styles? Jeez...]
*}

{if $g_message}

  {if $g_success}
    {assign var=class value="notify"}
    <script>{literal}$(function() { $("#ft_message_inner").effect("highlight", {color: "#" + g.notify_colours[1] }, 1200); });{/literal}</script>
  {else}
    {assign var=class value="error"}
    <script>{literal}$(function() { $("#ft_message_inner").effect("highlight", {color: "#" + g.error_colours[1] }, 1200); });{/literal}</script>
  {/if}

  <div id="ft_message">
    <div style="height: 8px;"> </div>
    <div class="{$class}" id="ft_message_inner">
      <div style="padding:8px">
        <span style="float: right; padding-left: 5px;"><a href="#" onclick="return ft.hide_message('ft_message')">X</a></span>
        {$g_message}
      </div>
    </div>
  </div>

{else}

  <div id="ft_message" style="width: 100%; display:none">
    <div style="height: 8px;"> </div>
    <div class="{$class}" id="ft_message_inner"></div>
  </div>

{/if}

<div style="height: 10px;"> </div>