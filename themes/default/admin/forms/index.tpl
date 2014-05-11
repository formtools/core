{ft_include file='header.tpl'}

  <table cellpadding="0" cellspacing="0">
  <tr>
    <td width="45"><img src="{$images_url}/icon_forms.gif" width="34" height="34" /></td>
    <td class="title">{$LANG.word_forms}</td>
  </tr>
  </table>

  {ft_include file="messages.tpl"}

  {if $num_forms == 0}
    <div>{$LANG.text_no_forms}</div>
  {else}

    <div id="search_form" class="margin_bottom_large">

      <form action="{$same_page}" method="post">

        <table cellspacing="2" cellpadding="0" id="search_form_table">
        <tr>
          <td class="blue" width="70">{$LANG.word_search}</td>
          {if $clients|@count > 0}
          <td>
            <select name="client_id">
              <option value="" {if $search_criteria.client_id == ""}selected{/if}>{$LANG.phrase_forms_assigned_to_any_account}</option>
              <optgroup label="{$LANG.word_clients}">
                {foreach from=$clients item=client name=row}
                  <option value="{$client.account_id}" {if $search_criteria.client_id == $client.account_id}selected{/if}>{$client.first_name} {$client.last_name}</option>
                {/foreach}
              </optgroup>
            </select>
          </td>
          {/if}
          <td>
            <select name="status">
              <option value="" {if $search_criteria.status == ""}selected{/if}>{$LANG.phrase_all_statuses}</option>
              <option value="online" {if $search_criteria.status == "online"}selected{/if}>{$LANG.word_online}</option>
              <option value="offline" {if $search_criteria.status == "offline"}selected{/if}>{$LANG.word_offline}</option>
              <option value="incomplete" {if $search_criteria.status == "incomplete"}selected{/if}>{$LANG.word_incomplete}</option>
            </select>
          </td>
          <td>
            <input type="text" size="20" name="keyword" value="{$search_criteria.keyword|escape}" />
            <input type="submit" name="search_forms" value="{$LANG.word_search}" />
            <input type="button" name="reset" onclick="window.location='{$same_page}?reset=1'"
              {if $forms|@count < $num_forms}
                value="{$LANG.phrase_show_all} ({$num_forms})" class="bold"
              {else}
                value="{$LANG.phrase_show_all}" class="light_grey" disabled="disabled"
              {/if} />
          </td>
        </tr>
        </table>
      </form>
    </div>

    {if $forms|@count == 0}

      <div class="notify yellow_bg">
        <div style="padding: 8px">
          {$LANG.text_no_forms_found}
        </div>
      </div>

    {else}

      {if $max_forms_reached}
        <div class="notify margin_bottom_large">
          <div style="padding:6px">
            {$notify_max_forms_reached}
          </div>
        </div>
      {/if}

      {$pagination}

      {template_hook location="admin_forms_list_top"}

      <form action="{$same_page}" method="post">

      {assign var="table_group_id" value="1"}

      {* this displays ALL forms on the page, but groups them in separate tables - only one shown
         at a time. The page nav above hides/shows the appropriate page with JS. *}
      {foreach from=$forms item=form_info name=row}
        {assign var='index' value=$smarty.foreach.row.index}
        {assign var='count' value=$smarty.foreach.row.iteration}
        {assign var='form_id' value=$form_info.form_id}
        {assign var='clients' value=$form_info.client_info}

        {* if it's the first row or the start of a new table, open the table & display the headings *}
        {if $count == 1 || $count != 1 && (($count-1) % $settings.num_forms_per_page == 0)}

          {if $table_group_id == "1"}
            {assign var="style" value="display: block"}
          {else}
            {assign var="style" value="display: none"}
          {/if}
          <div id="page_{$table_group_id}" style="{$style}">

            <table class="list_table" width="100%" cellpadding="0" cellspacing="1">
            <tr>
              {assign var="up_down" value=""}
              {if     $order == "form_id-DESC"}
                {assign var=sort_order value="order=form_id-ASC"}
                {assign var=up_down value="<img src=\"`$theme_url`/images/sort_down.gif\" />"}
              {elseif $order == "form_id-ASC"}
                {assign var=sort_order value="order=form_id-DESC"}
                {assign var=up_down value="<img src=\"`$theme_url`/images/sort_up.gif\" />"}
              {else}
                {assign var=sort_order value="order=form_id-DESC"}
              {/if}
              <th width="30" class="sortable_col{if $up_down} over{/if}">
                <a href="{$same_page}?{$sort_order}">{$LANG.word_id|upper} {$up_down}</a>
              </th>

              {assign var="up_down" value=""}
              {if     $order == "form_name-DESC"}
                {assign var=sort_order value="order=form_name-ASC"}
                {assign var=up_down value="<img src=\"`$theme_url`/images/sort_down.gif\" />"}
              {elseif $order == "form_name-ASC"}
                {assign var=sort_order value="order=form_name-DESC"}
                {assign var=up_down value="<img src=\"`$theme_url`/images/sort_up.gif\" />"}
              {else}
                {assign var=sort_order value="order=form_name-DESC"}
              {/if}
              <th class="sortable_col{if $up_down} over{/if}">
                <a href="{$same_page}?{$sort_order}">{$LANG.word_form} {$up_down}</a>
              </th>

              {assign var="up_down" value=""}
              {if     $order == "form_type-DESC"}
                {assign var=sort_order value="order=form_type-ASC"}
                {assign var=up_down value="<img src=\"`$theme_url`/images/sort_down.gif\" />"}
              {elseif $order == "form_type-ASC"}
                {assign var=sort_order value="order=form_type-DESC"}
                {assign var=up_down value="<img src=\"`$theme_url`/images/sort_up.gif\" />"}
              {else}
                {assign var=sort_order value="order=form_type-DESC"}
              {/if}
              <th nowrap class="sortable_col{if $up_down} over{/if}">
                <a href="{$same_page}?{$sort_order}">{$LANG.phrase_form_type} {$up_down}</a>
              </th>
              <th>{$LANG.phrase_who_can_access}</th>

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
              <th width="90" class="sortable_col{if $up_down} over{/if}">
                <a href="{$same_page}?{$sort_order}">{$LANG.word_status} {$up_down}</a>
              </th>
              <th width="90">{$LANG.word_submissions}</th>
              <th class="edit"></th>
              <th class="del"></th>
            </tr>

         {/if}

          <tr>
            <td align="center" class="medium_grey">{$form_id}</td>
            <td class="pad_left_small">
              {if $form_info.form_type == "external"}
                {$form_info.form_name}
                <a href="{$form_info.form_url}" class="show_form" target="_blank" title="{$LANG.phrase_open_form_in_dialog}"></a>
              {else}
                {$form_info.form_name}
              {/if}
            </td>
            <td align="center">
              {if $form_info.form_type == "external"}
                <span class="brown">{$LANG.word_external}</span>
              {elseif $form_info.form_type == "internal"}
                <span class="orange">{$LANG.word_internal}</span>
              {/if}
              {template_hook location="admin_forms_form_type_label"}
            </td>
            <td>

              {* display the list of client associated with this form. If it's a public form, keep it simple
                 and just display "All clients. *}
              {if $form_info.is_complete == 'no'}

              {elseif $form_info.access_type == 'admin'}
                <span class="medium_grey pad_left_small">{$LANG.phrase_admin_only}</span>
              {elseif $form_info.access_type == 'public'}

                {if $form_info.client_omit_list|@count == 0}
                  <span class="pad_left_small blue">{$LANG.phrase_all_clients}</span>
                {else}
                  {clients_dropdown only_show_clients=$form_info.client_omit_list display_single_client_as_text=true
                    include_blank_option=true blank_option="All clients, except:" force_show_blank_option=true}
                {/if}

              {else}

                {if $clients|@count == 0}
                  <span class="pad_left_small light_grey">{$LANG.phrase_no_clients}</span>
                {elseif $clients|@count == 1}
                  <span class="pad_left_small">{$clients[0].first_name} {$clients[0].last_name}</span>
                {else}
                  <select class="clients_dropdown">
                    {foreach from=$clients item=client name=row2}
                      <option>{$client.first_name} {$client.last_name}</option>
                    {/foreach}
                  </select>
                {/if}
              {/if}

            </td>
            <td align="center">
              {if $form_info.is_active == "no"}
                {assign var='status' value="<span style=\"color: orange\">`$LANG.word_offline`</span>"}
              {else}
                {assign var='status' value="<span class=\"light_green\">`$LANG.word_online`</span>"}
              {/if}

              {if $form_info.is_complete == "no"}
                {assign var='status' value="<span style=\"color: red\">`$LANG.word_incomplete`</span>"}
                {assign var='file' value='add/step2.php'}
              {else}
                {assign var='file' value='edit.php'}
              {/if}

              {$status}

            </td>
            <td {if $form_info.is_complete == "no"}align="center"{/if}>
              {if $form_info.is_complete == "yes"}
                <div class="form_info_link">
                {assign var='num_form_submissions' value=form_`$form_id`_num_submissions}
                <a href="submissions.php?form_id={$form_id}">{$LANG.word_view|upper}<span class="num_submissions_box">{$SESSION[$num_form_submissions]}</span></a>
                </div>
              {/if}

              {if $form_info.is_complete != "yes"}
                <a href="{$file}?form_id={$form_id}">{$LANG.word_complete|upper}</a>
              {/if}
            </td>
            <td {if $form_info.is_complete == "yes"}class="edit"{/if}>
              {if $form_info.is_complete == "yes"}
                <a href="{$file}?form_id={$form_id}"></a>
              {/if}
            </td>
            <td class="del"><a href="delete_form.php?form_id={$form_id}"></a></td>
          </tr>

        {if $count != 1 && ($count % $settings.num_forms_per_page) == 0}
          </table></div>
          {assign var='table_group_id' value=$table_group_id+1}
        {/if}

      {/foreach}

      {* if the table wasn't closed, close it! *}
      {if ($forms|@count % $settings.num_forms_per_page) != 0}
        </table></div>
      {/if}

    {/if}

    </form>

  {/if}

  {if !$max_forms_reached}
    <form method="post" action="add/">
      <p>
        <input type="submit" name="new_form" value="{$LANG.phrase_add_form}" />
      </p>
    </form>
  {/if}

  {template_hook location="admin_forms_list_bottom"}

{ft_include file="footer.tpl"}
