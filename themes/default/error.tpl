{*
  This file was repurposed in 2.1.0 to display the old ft_handle_error() errors but also the
  old messages from the Add Form processes. It's a little klutzy, in that it uses an if-else
  to determine the context, but it's much better having it in a single location.
*}
{include file="header.tpl"}

  {if $context == "error_page"}

    {if $message_type == "error"}
      <div class="error" style="padding: 8px">
        <span class="bold">{$LANG.word_error_c}</span>
    {else}
      <div class="notify" style="padding:8px">
    {/if}

      <div style="padding-top: 10px">
        {$message|nl2br}
      </div>

      {if $g_debug}
        {if $error_debug == ""}
          {assign var=error_debug value="No further help available."}
        {/if}

        <p>Debug:</p>
        <p>{$error_debug}</p>
      {/if}
    </div>

  {else}

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
  {/if}

  <noscript>
    <br />
    <div class="error" style="padding:8px;">
      {$LANG.text_js_required}
    </div>
  </noscript>

{include file="footer.tpl"}
