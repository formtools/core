<table cellpadding="0" cellspacing="0" border="0" width="100%">
<tr>
  <td>

    <table border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
      {foreach from=$tabs key=curr_tab_key item=curr_tab}

        {* we show a tab as enabled if:
          (a) the page var is the same as the current tab key (e.g. page=main in the query string), OR
          (b) if curr_tab.pages is specified as an array, and $page is included in the array (used for "sub-pages" in tabs) OR
          (c) $tab_number is specified and it's equal to $curr_tab_key
        *}
        {if $curr_tab_key == $page || (is_array($curr_tab.pages) && $page|in_array:$curr_tab.pages) || $tab_number == $curr_tab_key}
          <td width="10" height="26"><img src="{$images_url}/left_tab_selected.gif" width="12" height="26" alt=""></td>
          <td class="tab_selected nowrap" width="80"><div class="pad_left pad_right nowrap"><a href="{$curr_tab.tab_link}" style="display:block">{$curr_tab.tab_label}</a></div></td>
          <td width="10" height="26"><img src="{$images_url}/right_tab_selected.gif" width="10" height="26" alt=""></td>
        {else}
          <td width="10" height="26"><img src="{$images_url}/left_tab_not_selected.gif" width="12" height="26" alt=""></td>
          <td class="tab_not_selected" width="80"><div class="pad_left pad_right nowrap"><a href="{$curr_tab.tab_link}" style="display:block">{$curr_tab.tab_label}</a></div></td>
          <td width="10" height="26"><img src="{$images_url}/right_tab_not_selected.gif" width="10" height="26" alt=""></td>
        {/if}

        <td width="1" height="26" style="border-bottom: 1px solid #cfcfcf"> </td>
      {/foreach}

      <td height="26" style="border-bottom: 1px solid #cfcfcf;">
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
      </td>
    </tr>
    </table>

  </td>
</tr>
<tr>
  <td class="tab_content">
