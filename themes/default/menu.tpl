{*
  menu.tpl
  --------

  This page is used to render the navigation menu for all accounts - administrator and clients.
*}


  {assign var=is_current_parent_menu value=false}

  <div class="menu_items">
  {foreach from=$SESSION.menu.menu_items key=k item=i}

    {assign var=link_id value=""}

    {* main menu item *}
    {if $i.is_submenu == "no"}

      {* if this parent menu contains the page that is currently being viewed, show the submenu options *}
      {if $i.url == $nav_parent_page_url}
        {assign var=is_current_parent_menu value=true}
      {else}
        {assign var=is_current_parent_menu value=false}
      {/if}

      <div class="nav_link"><a href="{$i.url}"{$link_id} class="no_border">{$i.display_text}</a></div>

    {* child menu item *}
    {else}
      <div class="nav_link_submenu"><a href="{$i.url}"{$link_id} class="no_border">&#8212; {$i.display_text}</a></div>
    {/if}

  {/foreach}
  </div>