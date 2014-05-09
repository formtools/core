{ft_include file='header.tpl'}

  <table cellpadding="0" cellspacing="0" width="100%">
  <tr>
    <td width="45"><a href="./"><img src="{$images_url}/icon_forms.gif" border="0" width="34" height="34" /></a></td>
    <td class="title">{$form_info.form_name|upper}</td>
    <td align="right" valign="top">
      <div style="float:right; padding-left: 6px;">
        <a href="edit.php?form_id={$form_id}"><img src="{$images_url}/edit_small.gif" border="0" alt="{$LANG.phrase_edit_form}"
          title="{$LANG.phrase_edit_form}" /></a>
      </div>
      {if $form_views|@count > 1}
        <select onchange="window.location='{$same_page}?page=1&view=' + this.value">
          <optgroup label="{$LANG.word_views}">
          {foreach from=$form_views key=k item=i}
            <option value="{$i.view_id}" {if $view_id == $i.view_id}selected{/if}>{$i.view_name}</option>
          {/foreach}
          </optgroup>
        </select>
      {/if}
    </td>
  </tr>
  </table>

  {* if there is at least one submission in this form (and not necessary in this current search or View),
     always display the search form *}
  {if $total_form_submissions == 0}
    <p>
      {$LANG.text_no_submissions_found}
    </p>

    {if $view_info.may_add_submissions == "yes"}
      <input type="button" id="add_submission" value="{$LANG.word_add}" onclick="window.location='{$same_page}?add_submission'" />
    {/if}

  {else}

  {ft_include file="messages.tpl"}

  <div id="search_form">

    <form action="{$same_page}" method="post" name="search_form" onsubmit="return rsv.validate(this, rules)">
      <input type="hidden" name="search" value="1" />
      <input type="hidden" name="select_all" value="{if $curr_view_select_all == "yes"}1{/if}"  />

      <table cellspacing="0" cellpadding="0" id="search_form_table">
      <tr>
        <td class="blue" width="70">{$LANG.word_search}</td>
        <td>

          <table cellspacing="2" cellpadding="0">
          <tr>
            <td>
              {form_view_fields_dropdown name_id="search_field" form_id=$form_id view_id=$view_id
                  blank_option_value="all" blank_option_text=$LANG.phrase_all_fields
                  onchange="ms.change_search_field(this.value)" onkeyup="ms.change_search_field(this.value)"
                  default=$curr_search_fields.search_field}
            </td>
            <td>
              <div id="search_dropdown_section"
                {if $curr_search_fields.search_field != "submission_date" &&
                    $curr_search_fields.search_field != "last_modified_date"}style="display: none"{/if}>

                {date_range_search_dropdown name_id="search_date" form_id=$form_id view_id=$view_id
                  default=$curr_search_fields.search_date}
              </div>
            </td>
          </tr>
          </table>

        </td>
        <td width="20" align="center">{$LANG.word_for}</td>
        <td>
          <input type="text" style="width: 120px;" name="search_keyword" value="{$curr_search_fields.search_keyword|escape}" />
        </td>
        <td>
          <input type="submit" name="search" value="{$LANG.word_search}" />
          <input type="button" name="" value="{$LANG.phrase_show_all}" onclick="window.location='submissions.php?page=1&reset=1'"
            {if $search_num_results < $view_num_results}class="bold"{/if} />
        </td>
      </tr>
      </table>
    </form>
  </div>

  <br />

  {$pagination}

  {if $search_num_results == 0}
    <div class="notify yellow_bg">
      <div style="padding:8px">
        {$LANG.text_no_search_results}
      </div>
    </div>
  {else}

    <form name="current_form" action="{$same_page}" method="post">

    {template_hook location="admin_submission_listings_top"}

    <table class="submissions_table" id="submissions_table" cellpadding="1" cellspacing="1" border="0" width="650">
    <tr>
      <th align="center" width="25"> </th>
      {foreach from=$display_fields key=k item=i}

        {if $i.is_sortable == "yes"}

          {assign var="up_down" value=""}

          {* determine the column sorting (if included in query string, reverse) *}
          {if     $order == $i.col_name|cat:'-DESC'}
            {assign var=order_col value="&order=`$i.col_name`-ASC"}
            {assign var=up_down value="<img src=\"`$theme_url`/images/sort_down.gif\" />"}
          {elseif $order == $i.col_name|cat:'-ASC'}
            {assign var=order_col value="&order=`$i.col_name`-DESC"}
            {assign var=up_down value="<img src=\"`$theme_url`/images/sort_up.gif\" />"}
          {else}
            {assign var=order_col value="&order=`$i.col_name`-DESC"}
          {/if}

          {if $i.col_name == "submission_date" || $i.col_name == "last_modified_date"}
            <th class="nowrap pad_right">
          {else}
            <th class="nowrap pad_right">
          {/if}

            <table cellspacing="0" cellpadding="0" align="center">
            <tr>
              <td><a href="{$same_page}?{$pass_along_str}{$order_col}">{$i.field_title}</a></td>
              <td class="pad_left">{$up_down}</td>
            </tr>
            </table>

          </th>
        {else}
          <th>{$i.field_title}</th>
        {/if}

      {/foreach}
      <th width="50">
        {if $view_info.may_edit_submissions == "yes"}
          {$LANG.word_edit|upper}
        {else}
          {$LANG.word_view|upper}
        {/if}
      </th>
    </tr>

    {foreach from=$search_rows key=k item=search_row}
      {assign var=submission_id value=$search_row.submission_id}

        {assign var=precheck value=""}
        {if $submission_id|in_array:$preselected_subids}
          {assign var=precheck value="checked"}
        {/if}

        <tr id="submission_row_{$submission_id}" class="unselected_row_color">
          <td align="center"><input type="checkbox" id="submission_cb_{$submission_id}" name="submissions[]" value="{$submission_id}"
            onchange="ms.select_row({$submission_id}, {$results_per_page})" {$precheck} />&nbsp;</td>

        {* for each search row, loop through the display fields and display the appropriate content for the submission field *}
        {foreach from=$display_fields key=k2 item=curr_field}
          {assign var=field_id value=$curr_field.field_id}
          {assign var=field_type value=$curr_field.field_info.field_type}
          {assign var=col_name value=$curr_field.col_name}

          {assign var=nowrap_rightpad value=""}
          {assign var=ellipsis value="ellipsis"}
          {assign var=td_class value=""}
          {assign var=cell_value value=""}

          {* select and radio buttons show the appropriate display value *}
          {if $field_type == "select" || $field_type == "radio-buttons"}

            {assign var=val value=$search_row.$col_name}

            {foreach from=$curr_field.field_info.options key=k3 item=option}
              {if $option.option_value == $val}
                {assign var=cell_value value=$option.option_name}
              {/if}
            {/foreach}

          {elseif $field_type == "checkboxes" || $field_type == "multi-select"}
            {assign var=value value=$search_row.$col_name}

            {* this helper function displays the values of a multi-select field (checkboxes / multi-select dropdown) *}
            {display_multi_select_field_values options=$curr_field.field_info.options values=$value var_name="cell_value"}

          {elseif $field_type == "system"}

            {if $col_name == "submission_id"}
              {assign var=td_class value="submission_id"}
              {assign var=cell_value value=$submission_id}
            {elseif $col_name == "submission_date"}
              {assign var=td_class value="dates"}
              {assign var=cell_value value=$search_row.submission_date|custom_format_date:$SESSION.account.timezone_offset:$SESSION.account.date_format}
            {elseif $col_name == "last_modified_date"}
              {assign var=td_class value="dates"}
              {assign var=cell_value value=$search_row.last_modified_date|custom_format_date:$SESSION.account.timezone_offset:$SESSION.account.date_format}
            {elseif $col_name == "ip_address"}
              {assign var=td_class value="ip_address"}
              {assign var=cell_value value=$search_row.ip_address}
            {/if}

            {* never restrict the widths of system fields! *}
            {assign var=ellipsis value=""}

            {* only make system fields as wide as they need to be *}
            {assign var=nowrap_rightpad value="nowrap pad_right_small"}

          {elseif $field_type == "image"}

            {* TODO removed: extended_field_info=$image_field_info.$field_id - The function will now to have to retrieve & cache this information
               on it's own. *}
            {module_function name=display_image type="search_results_thumb" field_id=$field_id
              image_info_string=$search_row[$display_field.col_name] var_name="cell_value"}

          {else}
            {assign var=cell_value value=$search_row.$col_name}
          {/if}

          <td class="{$td_class}"><div class="{$nowrap_rightpad} {$ellipsis} {$td_class}">{$cell_value|escape}</td>
        {/foreach}

        <td align="center"><a href="edit_submission.php?form_id={$form_id}&view_id={$view_id}&submission_id={$submission_id}">{if $view_info.may_edit_submissions == "yes"}{$LANG.word_edit|upper}{else}{$LANG.word_view|upper}{/if}</a></td>
      </tr>

    {/foreach}
    </table>

    <div style="padding-top: 5px; padding-bottom: 5px;">
      <div style="float:right; padding:1px" id="display_num_selected_rows"
        {if $preselected_subids|@count == 0}
          class="light_grey"
        {else}
          class="green"
        {/if}>
      </div>

      {template_hook location="admin_submission_listings_buttons1"}

      {if $view_info.may_add_submissions == "yes"}
        <input type="button" id="add_submission" value="{$LANG.word_add}" onclick="window.location='{$same_page}?add_submission'" />
      {/if}

      {template_hook location="admin_submission_listings_buttons2"}

      <input type="button" id="select_button" value="{$LANG.phrase_select_all_on_page}" onclick="ms.select_all_on_page();" />
      <input type="button" id="unselect_button" value="{$LANG.phrase_unselect_all}" onclick="ms.unselect_all()" />

      {template_hook location="admin_submission_listings_buttons3"}

      {if $view_info.may_delete_submissions == "yes"}
        <input type="button" value="{$LANG.word_delete}" class="red" onclick="ms.delete_submissions()" />
      {/if}

      {template_hook location="admin_submission_listings_buttons4"}
    </div>

    {template_hook location="admin_submission_listings_bottom"}

    </form>


    {* display the export options *}
    {module_function name=export_manager_export_options account_type="admin" account_id=$SESSION.account.account_id}

    {/if}

  {/if}

{ft_include file='footer.tpl'}