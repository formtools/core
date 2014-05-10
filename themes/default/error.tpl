{ft_include file="header.tpl"}

  {if $error_type == "error"}
    <div class="error" style="padding: 8px">
      <span class="bold">{$LANG.word_error_c}</span>
  {else}
    <div class="notify" style="padding:8px">
  {/if}

    <div style="padding-top: 10px">
      {$last_error|nl2br}
    </div>

    {if $g_debug}
      {if $error_debug == ""}
        {assign var=error_debug value="No further help available."}
      {/if}

      <p>Debug:</p>
      <p>{$error_debug}</p>
    {/if}

  </div>

  <noscript>
    <br />
    <div class="error" style="padding:8px;">
      {$LANG.text_js_required}
    </div>
  </noscript>

{ft_include file="footer.tpl"}
