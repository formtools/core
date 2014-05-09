{ft_include file='header.tpl'}

  <table cellpadding="0" cellspacing="0">
  <tr>
    <td width="45"><img src="{$images_url}/icon_forms.gif" width="34" height="34" /></td>
    <td class="title">{$LANG.word_forms|upper}</td>
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
          <td>
            <select name="client_id">
              <option value="" {if $search_criteria.client_id == ""}selected{/if}>{$LANG.phrase_all_clients}</option>
              {foreach from=$clients item=client name=row}
                <option value="{$client.account_id}" {if $search_criteria.client_id == $client.account_id}selected{/if}>{$client.first_name} {$client.last_name}</option>
              {/foreach}
            </select>
          </td>
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
            <input type="button" name="reset" value="{$LANG.phrase_show_all}" onclick="window.location='{$same_page}?reset=1'"
              {if $forms|@count < $num_forms}
                class="bold"
              {else}
                class="light_grey" disabled
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

      {$pagination}

      {template_hook location="admin_forms_list_top"}

      <form action="{$same_page}" method="post">

      {assign var="table_group_id" value="1"}

      {* this displays ALL forms on the page, but groups them in separate tables - only one shown
         at a time. The page nav above hides/shows the appropriate page with JS. Sorry the Smarty logic
         is so dense...! *}
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
              <th width="30">

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

                <table cellspacing="0" cellpadding="0" align="center" class="pad_left_small">
                <tr>
                  <td><a href="{$same_page}?{$sort_order}">{$LANG.word_id|upper}</a></td>
                  <td class="pad_left">{$up_down}</td>
                </tr>
                </table>

              </th>
              <th>

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

                <table cellspacing="0" cellpadding="0" align="center" class="pad_left_small">
                <tr>
                  <td><a href="{$same_page}?{$sort_order}">{$LANG.word_form}</a></td>
                  <td class="pad_left">{$up_down}</td>
                </tr>
                </table>

              </th>
              <th>{$LANG.phrase_who_can_access}</th>
              <th width="70">

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

                <table cellspacing="0" cellpadding="0" align="center" class="pad_left_small">
                <tr>
                  <td><a href="{$same_page}?{$sort_order}">{$LANG.word_status}</a></td>
                  <td class="pad_left">{$up_down}</td>
                </tr>
                </table>

              </th>
              <th width="90">{$LANG.word_submissions|upper}</th>
              <th width="70">{$LANG.word_edit|upper}</th>
              <th class="del" width="70">{$LANG.word_delete|upper}</th>
            </tr>

        {/if}

          <tr>
            <td align="center" class="medium_grey">{$form_id}</td>
            <td class="pad_left_small"><a href="{$form_info.form_url}" target="_blank">{$form_info.form_name}</a></td>
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
                  <span class="pad_left_small blue">All clients, except: </span>
                  {clients_dropdown only_show_clients=$form_info.client_omit_list display_single_client_as_text=true}
                {/if}

              {else}

                {if $clients|@count == 0}
                  <span class="pad_left_small light_grey">{$LANG.phrase_no_clients}</span>
                {elseif $clients|@count == 1}
                  <span class="pad_left_small">{$clients[0].first_name} {$clients[0].last_name}</span>
                {else}
                  <select>
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
                {assign var='file' value='add/step1.php'}
              {else}
                {assign var='file' value='edit.php'}
              {/if}

              {$status}

            </td>
            <td align="center">

              {if $form_info.is_complete == "yes"}
                {assign var='num_form_submissions' value=form_`$form_id`_num_submissions}
                ({$SESSION[$num_form_submissions]})&nbsp;<a href="submissions.php?form_id={$form_id}">{$LANG.word_view|upper}</a>
              {/if}

            </td>
            <td align="center">

              {if $form_info.is_complete == "yes"}
                <a href="{$file}?form_id={$form_id}">{$LANG.word_edit|upper}</a>
              {else}
                <a href="{$file}?form_id={$form_id}">{$LANG.word_complete|upper}</a>
              {/if}

            </td>
            <td class="del"><a href="delete_form.php?form_id={$form_id}">{$LANG.word_delete|upper}</a></td>
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

  <p>
    <form method="post" action="add/step1.php">
      <input type="submit" value="{$LANG.phrase_add_form|upper}" />
    </form>
  </p>

  {template_hook location="admin_forms_list_bottom"}

{ft_include file="footer.tpl"}