  <ul class="main_tabset">
    {foreach from=$tabs key=curr_tab_key item=curr_tab}
      {* we show a tab as enabled if:
        (a) the page var is the same as the current tab key (e.g. page=main in the query string), OR
        (b) if curr_tab.pages is specified as an array, and $page is included in the array (used for "sub-pages" in tabs) OR
        (c) $tab_number is specified and it's equal to $curr_tab_key *}
      {if $curr_tab_key == $page || (is_array($curr_tab.pages) && $page|in_array:$curr_tab.pages) || $tab_number == $curr_tab_key}
        <li class="selected"><a href="{$curr_tab.tab_link}">{$curr_tab.tab_label}</a></li>
      {else}
        <li><a href="{$curr_tab.tab_link}">{$curr_tab.tab_label}</a></li>
      {/if}
    {/foreach}
  </ul>

  <div class="prevnext_links">
    {if $show_tabset_nav_links}
      {assign var=prev_label value=$prev_tabset_link_label|default:$LANG.word_previous_leftarrow}
      {if $prev_tabset_link}
        <span><a href="{$prev_tabset_link}">{$prev_label}</a></span>
      {else}
        <span class="no_link">{$prev_label}</span>
      {/if}

      {assign var=next_label value=$next_tabset_link_label|default:$LANG.word_next_rightarrow}
      {if $next_tabset_link}
        <span><a href="{$next_tabset_link}">{$next_label}</a></span>
      {else}
        <span class="no_link">{$next_label}</span>
      {/if}
    {/if}
  </div>

  <div class="clear"></div>
  <div class="tab_content">