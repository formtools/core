{*
  module_nav.tpl
  --------------

  This page is used to render the custom navigation menus for all modules.
*}

  <div class="menu_items">
  {foreach from=$module_nav key=k item=i}
    {if $i.is_submenu == "no"}
      <div class="nav_link"><a href="{$i.url}"{$link_id}>{$i.display_text}</a></div>
    {else}
      <div class="nav_link_submenu"><a href="{$i.url}"><img src="{$images_url}/submenu_item.gif" border="0" style="margin-right: 4px" /> {$i.display_text}</a></div>
    {/if}
  {/foreach}
  </div>

  <div class="nav_link"><a href="{$g_root_url}/admin/modules/">&laquo; {$LANG.word_modules}</a></div>