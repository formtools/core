{include file='header.tpl'}

  <table cellpadding="0" cellspacing="0" height="35">
  <tr>
    <td width="45"><img src="{$images_url}/icon_themes.gif" width="34" height="29" /></td>
    <td class="title">
      <a href="./">{$LANG.word_themes}</a>
      <span class="joiner">&raquo;</span>
      {$theme_info.theme_name}
    </td>
  </tr>
  </table>

  <div class="subtitle underline margin_top_large">{$LANG.phrase_theme_info|upper}</div>

  {ft_include file='messages.tpl'}

  <table cellspacing="1" cellpadding="1" class="list_table">
  <tr>
    <td width="180" class="pad_left_small">{$LANG.word_theme}</td>
    <td class="pad_left_small bold">{$theme_info.theme_name}</td>
  </tr>
  <tr>
    <td class="pad_left_small">{$LANG.phrase_theme_description}</td>
    <td class="pad_left_small">{$theme_info.description}</td>
  </tr>
  <tr>
    <td class="pad_left_small">{$LANG.phrase_uses_swatches}</td>
    <td class="pad_left_small">
      {if $theme_info.uses_swatches == "yes"}
        {$LANG.word_yes}
      {else}
        {$LANG.word_no}
      {/if}
    </td>
  </tr>
  {if $theme_info.uses_swatches == "yes"}
  <tr>
    <td class="pad_left_small">{$LANG.phrase_available_swatches}</td>
    <td class="pad_left_small">{$theme_info.available_swatches}</td>
  </tr>
  {/if}
  <tr>
    <td class="pad_left_small">{$LANG.word_author}</td>
    <td class="pad_left_small">{$theme_info.author}
      {if $theme_info.author_email != ''}
        &#8212; <a href="mailto:{$theme_info.author_email}">{$theme_info.author_email}</a>
      {/if}
    </td>
  </tr>
  {if $theme_info.theme_link != ''}
    <tr>
      <td class="pad_left_small">{$LANG.phrase_author_link}</td>
      <td class="pad_left_small"><a href="{$theme_info.theme_link}" target="_blank">{$theme_info.theme_link}</a></td>
    </tr>
  {/if}
  <tr>
    <td class="pad_left_small">{$LANG.word_version}</td>
    <td class="pad_left_small">{$theme_info.theme_version}</td>
  </tr>
  </table>

{include file='footer.tpl'}
