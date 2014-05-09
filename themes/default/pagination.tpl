{*
  Template: pagination.tpl
  Purpose:  This is used to display clickable 1 2 3 >> navigation.
  Needs:    $num_results  - the total number of results in a search
            $current_page - the current page number
            $first_page   - the first page to show in the pagination
            $last_page    - the last numbered page to show in the pagination
            $total_pages  - the total number of pages that may be viewed
*}

<div class="margin_bottom_large">
  {if $show_total_results}
    {$LANG.phrase_total_results_c} <b>{$num_results}</b>&nbsp;

    {* if there's more than one page, display a message with what rows are being shown *}
    {$viewing_range}
  {/if}

  {if $total_pages > 1}
    <div id="list_nav">
      {if $show_page_label}
        {$LANG.word_page_c}
      {/if}

      {* if we're not on the first page, provide a "<<" (previous page) link *}
      {if $current_page != 1}
        {assign var='previous_page' value=$current_page-1}
        <a href="{$same_page}?{$page_str}={$previous_page}{$query_str}">&laquo;</a>
      {/if}

      {* bewildering bloody tag! This loops through every possible page link and only shows the required ones *}
      {section name=counter start=1 loop=$total_pages+1}
        {assign var="page" value=$smarty.section.counter.index}

        {if $page >= $first_page && $page <= $last_page}
          {if $page == $current_page}
            <span id="list_current_page"><b>{$page}</b></span>
          {else}
            <span class="pad_right_small"><a href="{$same_page}?{$page_str}={$page}{$query_str}">{$page}</a></span>
          {/if}
        {/if}
      {/section}

      {* if required, add a final ">>" (next page) link *}
      {if $current_page < $total_pages}
        {assign var='next_page' value=$current_page+1}
        <a href="{$same_page}?{$page_str}={$next_page}{$query_str}">&raquo;</a>
      {/if}

    </div>

  {/if}

</div>