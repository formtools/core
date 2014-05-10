{ft_include file='header.tpl'}

  <table cellpadding="0" cellspacing="0">
  <tr>
    <td width="45"><img src="{$images_url}/icon_accounts.gif" width="34" height="34" /></td>
    <td class="title">{$LANG.word_clients}</td>
  </tr>
  </table>

  {ft_include file="messages.tpl"}

  {template_hook location="admin_list_clients_top"}

  {if $num_clients == 0}

    <div>{$LANG.text_no_clients}</div>

  {else}

    <div id="search_form" class=" margin_bottom_large">
      <form action="{$same_page}" method="post">
        <table cellspacing="2" cellpadding="0" id="search_form_table">
        <tr>
          <td class="blue" width="70">{$LANG.word_search}</td>
          <td>
            <select name="status">
              <option value="" {if $search_criteria.status == ""}selected{/if}>{$LANG.phrase_all_statuses}</option>
              <option value="active" {if $search_criteria.status == "active"}selected{/if}>{$LANG.word_active}</option>
              <option value="pending" {if $search_criteria.status == "pending"}selected{/if}>{$LANG.word_pending}</option>
              <option value="disabled" {if $search_criteria.status == "disabled"}selected{/if}>{$LANG.word_disabled}</option>
            </select>
          </td>
          <td>
            <input type="text" size="20" name="keyword" value="{$search_criteria.keyword|escape}" />
            <input type="submit" name="search_forms" value="{$LANG.word_search}" />
            <input type="button" name="reset" onclick="window.location='{$same_page}?reset=1'"
              {if $clients|@count < $num_clients}
                value="{$LANG.phrase_show_all} ({$num_clients})"  class="bold"
              {else}
                value="{$LANG.phrase_show_all}" class="light_grey" disabled
              {/if} />
          </td>
        </tr>
        </table>
      </form>
    </div>

    {if $clients|@count == 0}

      <div class="notify yellow_bg">
        <div style="padding: 8px">
          {$LANG.text_no_clients_found}
        </div>
      </div>

    {else}

      {$pagination}

      <form action="{$same_page}" method="post">
      {assign var="table_group_id" value="1"}

      {* this displays ALL clients on the page, but groups them in separate tables - only one shown
         at a time. The page nav above hides/shows the appropriate page with JS. Sorry the Smarty logic
         is so dense... *}
      {foreach from=$clients item=client name=row}

        {assign var='index' value=$smarty.foreach.row.index}
        {assign var='count' value=$smarty.foreach.row.iteration}
        {assign var='client_id' value=$clients[$index].account_id}
        {assign var='client_info' value=$clients[$index]}

        {* if it's the first row or the start of a new table, open the table & display the headings *}
        {if $count == 1 || $count != 1 && (($count-1) % $settings.num_clients_per_page == 0)}

          {if $table_group_id == "1"}
            {assign var="style" value="display: block"}
          {else}
            {assign var="style" value="display: none"}
          {/if}

          <div id="page_{$table_group_id}" style="{$style}">

            <table class="list_table" width="100%" cellpadding="0" cellspacing="1">
            <tr>
              {assign var="up_down" value=""}
              {if     $order == "client_id-DESC"}
                {assign var=sort_order value="order=client_id-ASC"}
                {assign var=up_down value="<img src=\"`$theme_url`/images/sort_down.gif\" />"}
              {elseif $order == "client_id-ASC"}
                {assign var=sort_order value="order=client_id-DESC"}
                {assign var=up_down value="<img src=\"`$theme_url`/images/sort_up.gif\" />"}
              {else}
                {assign var=sort_order value="order=client_id-DESC"}
              {/if}
              <th width="30" class="sortable_col{if $up_down} over{/if}">
                <a href="{$same_page}?{$sort_order}">{$LANG.word_id|upper} {$up_down}</a>
              </th>

              {assign var="up_down" value=""}
              {if     $order == "first_name-DESC"}
                {assign var=sort_order value="order=first_name-ASC"}
                {assign var=up_down value="<img src=\"`$theme_url`/images/sort_down.gif\" />"}
              {elseif $order == "first_name-ASC"}
                {assign var=sort_order value="order=first_name-DESC"}
                {assign var=up_down value="<img src=\"`$theme_url`/images/sort_up.gif\" />"}
              {else}
                {assign var=sort_order value="order=first_name-DESC"}
              {/if}
              <th class="sortable_col{if $up_down} over{/if}">
                <a href="{$same_page}?{$sort_order}">{$LANG.phrase_first_name} {$up_down}</a>
              </th>

              {assign var="up_down" value=""}
              {if     $order == "last_name-DESC"}
                {assign var=sort_order value="order=last_name-ASC"}
                {assign var=up_down value="<img src=\"`$theme_url`/images/sort_down.gif\" />"}
              {elseif $order == "last_name-ASC"}
                {assign var=sort_order value="order=last_name-DESC"}
                {assign var=up_down value="<img src=\"`$theme_url`/images/sort_up.gif\" />"}
              {else}
                {assign var=sort_order value="order=last_name-DESC"}
              {/if}
              <th class="sortable_col{if $up_down} over{/if}">
                <a href="{$same_page}?{$sort_order}">{$LANG.phrase_last_name} {$up_down}</a>
              </th>

              {assign var="up_down" value=""}
              {if     $order == "email-DESC"}
                {assign var=sort_order value="order=email-ASC"}
                {assign var=up_down value="<img src=\"`$theme_url`/images/sort_down.gif\" />"}
              {elseif $order == "email-ASC"}
                {assign var=sort_order value="order=email-DESC"}
                {assign var=up_down value="<img src=\"`$theme_url`/images/sort_up.gif\" />"}
              {else}
                {assign var=sort_order value="order=email-DESC"}
              {/if}
              <th class="sortable_col{if $up_down} over{/if}">
                <a href="{$same_page}?{$sort_order}">{$LANG.word_email} {$up_down}</a>
              </td>

              {assign var="up_down" value=""}
              {if     $order == "status-DESC"}
                {assign var=sort_order value="order=status-ASC"}
                {assign var=up_down value="<img src=\"`$theme_url`/images/sort_down.gif\" />"}
              {elseif $order == "status-ASC"}
                {assign var=sort_order value="order=status-DESC"}
                {assign var=up_down value="<img src=\"`$theme_url`/images/sort_up.gif\" />"}
              {else}
                {assign var=sort_order value="order=status-DESC"}
              {/if}
              <th width="70" class="sortable_col{if $up_down} over{/if}">
                <a href="{$same_page}?{$sort_order}">{$LANG.word_status} {$up_down}</a>
              </th>

              {assign var="up_down" value=""}
              {if     $order == "last_logged_in-DESC"}
                {assign var=sort_order value="order=last_logged_in-ASC"}
                {assign var=up_down value="<img src=\"`$theme_url`/images/sort_down.gif\" />"}
              {elseif $order == "last_logged_in-ASC"}
                {assign var=sort_order value="order=last_logged_in-DESC"}
                {assign var=up_down value="<img src=\"`$theme_url`/images/sort_up.gif\" />"}
              {else}
                {assign var=sort_order value="order=last_logged_in-DESC"}
              {/if}
              <th class="sortable_col{if $up_down} over{/if}">
                <a href="{$same_page}?{$sort_order}">{$LANG.phrase_last_logged_in} {$up_down}</a>
              </th>
              <th width="70">{$LANG.word_login|upper}</th>
              <th class="edit"></th>
              <th class="del"></th>
            </tr>

          {/if}

          <tr>
            <td align="center" class="medium_grey">{$client_id}</td>
            <td class="pad_left_small">{$client_info.first_name}</td>
            <td class="pad_left_small">{$client_info.last_name}</td>
            <td class="pad_left_small"><a href="mailto:{$client_info.email}">{$client_info.email}</a></td>
            <td align="center">
              {if $client_info.account_status == "active"}
                <span class="light_green">{$LANG.word_active}</span>
              {elseif $client_info.account_status == "disabled"}
                <span style="color: red">{$LANG.word_disabled}</span>
              {elseif $client_info.account_status == "pending"}
                <span style="color: orange">{$LANG.word_pending}</span>
              {/if}
            </td>
            <td class="pad_left_small">
              {if $client_info.last_logged_in != ""}
               {$client_info.last_logged_in|custom_format_date:$SESSION.account.timezone_offset:$SESSION.account.date_format}
              {else}
                <span class="medium_grey">{$LANG.word_never}</span>
              {/if}
            </td>
            <td align="center"><a href="{$same_page}?login={$client_id}">{$LANG.word_login|upper}</a></td>
            <td class="edit"><a href="edit.php?client_id={$client_id}"> </a></td>
            <td class="del"><a href="#" onclick="return page_ns.delete_client({$client_id})"></a></td>
          </tr>

        {if $count != 1 && ($count % $settings.num_clients_per_page) == 0}
          </table></div>
          {assign var='table_group_id' value=$table_group_id+1}
        {/if}

      {/foreach}

      {* if the table wasn't closed, close it! *}
      {if ($clients|@count % $settings.num_clients_per_page) != 0}
        </table></div>
      {/if}

    {/if}
    </form>

  {/if}

  {template_hook location="admin_list_clients_bottom"}

  <form method="post" action="add.php">
    <p>
      <input type="submit" value="{$LANG.phrase_add_client}" />
    </p>
  </form>

{ft_include file="footer.tpl"}