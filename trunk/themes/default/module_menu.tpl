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
      <div class="nav_link_submenu"><a href="{$i.url}">&#8212; {$i.display_text}</a></div>
    {/if}
  {/foreach}
  </div>
