{include file='header.tpl'}

  <table cellpadding="0" cellspacing="0" height="35" class="margin_bottom_large">
  <tr>
    <td width="45"><img src="{$images_url}/icon_modules.gif" width="34" height="34" /></td>
    <td class="title">
      <a href="./">{$LANG.word_modules}</a> <span class="joiner">&raquo;</span> {$LANG.phrase_module_info}
    </td>
  </tr>
  </table>


  <table cellspacing="1" cellpadding="1" class="list_table">
  <tr>
    <td width="140" class="pad_left_small">{$LANG.word_module}</td>
    <td class="pad_left_small bold">{$module_info.module_name}</td>
  </tr>
  {if $module_info.is_premium == "yes"}
    <tr>
      <td class="pad_left_small">{$LANG.phrase_license_key}</td>
      <td class="pad_left_small">
        {if $module_info.module_key}
          <span class="medium_grey">{$module_info.module_key}</span>
        {else}
          <span class="light_grey">{$LANG.phrase_not_entered_yet}</span>
        {/if}
      </td>
    </tr>
  {/if}
  <tr>
    <td class="pad_left_small">{$LANG.phrase_module_description}</td>
    <td class="pad_left_small">{$module_info.description}</td>
  </tr>
  <tr>
    <td class="pad_left_small">{$LANG.word_version}</td>
    <td class="pad_left_small">{$module_info.version}</td>
  </tr>
  <tr>
    <td class="pad_left_small">{$LANG.word_author}</td>
    <td class="pad_left_small">{$module_info.author}
      {if $module_info.author_email != ''}
        &#8212; <a href="mailto:{$module_info.author_email}">{$module_info.author_email}</a>
      {/if}
    </td>
  </tr>
  {if $module_info.author_link != ''}
    <tr>
      <td class="pad_left_small">{$LANG.phrase_author_link}</td>
      <td class="pad_left_small"><a href="{$module_info.author_link}" target="_blank">{$module_info.author_link}</a></td>
    </tr>
  {/if}
  </table>

  <p>
    <a href="index.php">{$LANG.word_back_leftarrow}</a>
  </p>

{include file='footer.tpl'}