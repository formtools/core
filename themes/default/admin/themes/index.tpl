{include file='header.tpl'}

  <table cellpadding="0" cellspacing="0" height="35">
  <tr>
    <td width="45"><img src="{$images_url}/icon_themes.gif" width="34" height="29" /></td>
    <td class="title">{$LANG.word_themes}</td>
  </tr>
  </table>

  {include file='messages.tpl'}

  <div class="margin_bottom_large">
    {$LANG.text_theme_page_intro}
  </div>

  <form action="{$same_page}" method="post" onsubmit="return rsv.validate(this, rules)">
    <table cellspacing="0" cellpadding="1" class="margin_bottom_large">
      <tr>
        <td width="180">{$LANG.phrase_administrator_theme}</td>
        <td>{themes_dropdown name_id="admin_theme" default=$admin_theme default_swatch=$admin_theme_swatch}</td>
      </tr>
      <tr>
        <td>{$LANG.phrase_default_client_account_theme}</td>
        <td>
          {themes_dropdown name_id="default_client_theme" default=$client_theme default_swatch=$client_theme_swatch}
          <span class="medium_grey">{$LANG.text_also_default_login_page_theme}</span>
        </td>
      </tr>
      </table>

      {if $themes|@count == 0}
        <div>{$LANG.text_no_themes}</div>
      {else}

        <table cellspacing="1" cellpadding="0" width="100%" class="list_table check_areas">
        <tr>
          <th width="200">{$LANG.word_image}</th>
          <th>{$LANG.phrase_theme_info}</th>
          <th width="70">{$LANG.word_enabled}</th>
        </tr>

        {foreach from=$themes item=theme name=row}
          {assign var='index' value=$smarty.foreach.row.index}
          {assign var='theme_info' value=$themes[$index]}
          <tr>
            <td valign="top">
              <a href="{$g_root_url}/themes/{$theme_info.theme_folder}/about/screenshot.gif" class="fancybox"
                title="{$theme_info.theme_name|escape}"><img src="{$g_root_url}/themes/{$theme_info.theme_folder}/about/thumbnail.gif" border="0" /></a>
            </td>
            <td valign="top" class="pad_left">
              <div>
                <span class="bold">{$theme_info.theme_name}</span>
                <span class="pad_right_large">{$theme_info.theme_version}</span>
                [<a href="about.php?theme_id={$theme_info.theme_id}">{$LANG.word_about|upper}</a>]
              </div>
              {if $theme_info.uses_swatches == "yes"}
                <div>{$LANG.phrase_available_swatches_c} <span class="medium_grey">{$theme_info.available_swatches}</span></div>
              {/if}
              {if $theme_info.author}<div>{$LANG.word_author_c} {$theme_info.author}</div>{/if}
              {if $theme_info.description}<p>{$theme_info.description}</p>{/if}
              {if !$theme_info.cache_folder_writable}
                <div class="error">
                  <div style="padding: 6px">
                    {eval_smarty_string placeholder_str=$LANG.notify_theme_cache_folder_not_writable
                      folder="`$g_root_dir`/themes/`$theme_info.theme_folder`/cache/"}
                  </div>
                </div>
              {/if}
            </td>
            <td valign="top" align="center" class="check_area">
              <input type="checkbox" name="is_enabled[]" value="{$theme_info.theme_folder}"
                {if $theme_info.is_enabled == 'yes'}checked="checked"{/if}
                {if !$theme_info.cache_folder_writable}disabled="disabled"{/if} />
            </td>
          </tr>
        {/foreach}
        {template_hook location="admin_settings_themes_bottom"}
        </table>

      {/if}

      <p>
        <input type="submit" name="update" value="{$LANG.word_update}" />
        <input type="submit" name="refresh_theme_list" class="blue" value="{$LANG.phrase_refresh_theme_list}" />
      </p>
    </form>

{include file='footer.tpl'}
