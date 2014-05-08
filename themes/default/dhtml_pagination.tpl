{*
  Template: dhtml_pagination.tpl
  Purpose:  This is used to display DHTML navigation on certain pages; namely, the client list and the form
            list in the admin pages.
*}

<div class="margin_bottom_large">
  {$LANG.phrase_total_results_c} <b>{$num_results}</b>&nbsp;

  {* if there's more than one page, display a message with what rows are being shown *}
  {$viewing_range}

  {if $total_pages > 1}
    <div id="list_nav">{$LANG.word_page_c}

    {* always show a "<<" (previous page) link. Its contents are changed with JS *}
    <span id="nav_previous_page">
      {if $current_page != 1}
        {assign var='previous_page' value=$current_page-1}
        <a href="javascript:ft.display_dhtml_page_nav({$num_results}, {$num_per_page}, {$previous_page})">&laquo;</a>
      {else}
        &laquo;
      {/if}
    </span>

    {section name=counter start=1 loop=$total_pages+1}
      {assign var="page" value=$smarty.section.counter.index}

      <span id="nav_page_{$page}">
        {if $page == $current_page}
          <span id="list_current_page"><b>{$page}</b></span>
        {else}
          <span class="pad_right_small"><a href="javascript:ft.display_dhtml_page_nav({$num_results}, {$num_per_page}, {$page})">{$page}</a></span>
        {/if}
      </span>
    {/section}

    {* always show a ">>" (next page) link. Its content is changed with JS *}
    <span id="nav_next_page">

      {if $current_page != $total_pages}
        {assign var='next_page' value=$current_page+1}
        <a href="javascript:ft.display_dhtml_page_nav({$num_results}, {$num_per_page}, {$next_page})">&raquo;</a>
      {else}
        <span id="nav_next_page">&raquo;</span>
      {/if}

    </span>

    </div>

  {/if}

</div>