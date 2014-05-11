  <div class="subtitle underline margin_top_large">{$LANG.word_fields|upper}</div>

  {ft_include file='messages.tpl'}

  <div class="margin_bottom_large">
    {$text_fields_tab_summary}
  </div>

  <form action="{$same_page}" method="post">
    <div class="underline pad_bottom margin_bottom_large">
      <div style="float:right">{$pagination}</div>
      <span class="margin_right_large medium_grey">{$LANG.word_show}</span>
      <select name="num_fields_per_page">
        <option value="all"{if $num_fields_per_page == "all"} selected{/if}>{$LANG.phrase_all_fields|lower}</option>
        <option value="10"{if $num_fields_per_page == "10"} selected{/if}>{$LANG.phrase_10_per_page}</option>
        <option value="15"{if $num_fields_per_page == "15"} selected{/if}>{$LANG.phrase_15_per_page}</option>
        <option value="20"{if $num_fields_per_page == "20"} selected{/if}>{$LANG.phrase_20_per_page}</option>
        <option value="25"{if $num_fields_per_page == "25"} selected{/if}>{$LANG.phrase_25_per_page}</option>
        <option value="50"{if $num_fields_per_page == "50"} selected{/if}>{$LANG.phrase_50_per_page}</option>
        <option value="100"{if $num_fields_per_page == "100"} selected{/if}>{$LANG.phrase_100_per_page}</option>
      </select>
      <input type="submit" value="{$LANG.word_update}" />
    </div>
  </form>

  <form action="{$same_page}" name="display_form" id="display_form" method="post">
    <input type="hidden" name="page" value="fields" />

    <div class="scroll-pane ui-corner-all" style="border: 0px; margin-left: 239px; margin-bottom: -2px">
      <div class="scroll-bar-wrap ui-widget-content ui-corner-top" style="border: 1px solid #aaaaaa; border-bottom: 1px solid white; margin: 0px">
        <div class="scroll-bar-top"></div>
      </div>
    </div>

    <div class="clear"></div>
    {strip}
    <div class="sortable groupable scrollable edit_fields" id="{$sortable_id}">
      {* this optional field tells the sortable JS code to automatically tabindex the contents of the sortable
         table by column. The JS re-tabindexes everything after a re-ordering. *}
      <input type="hidden" class="tabindex_col_selectors" value=".rows .col2 input|.rows .col3 .sub_col1 input|.rows .col3 .sub_col2 select|.rows .col3 .sub_col3 input|.rows .col3 .sub_col4 select|.rows .col3 .sub_col5 select|.rows .col3 .sub_col6 input" />
      <input type="hidden" class="sortable__edit_tooltip" value="{$LANG.phrase_edit_field}" />
      <input type="hidden" class="sortable__delete_tooltip" value="{$LANG.phrase_delete_field}" />
      <input type="hidden" class="sortable__custom_delete_handler" value="fields_ns.delete_field" />
      <input type="hidden" name="sortable_row_offset" class="sortable__row_offset" value="{$order_start_number}" />

      <ul class="header_row">
        <li class="col1">{$LANG.word_order}</li>
        <li class="col2">{$LANG.phrase_display_text}</li>
        <li class="col3 scrollable">
          <ul>
            <li class="splitter"></li>
            <li class="subcol_header">
              <ul class="scroll-content">
                <li class="sub_col1">{$LANG.phrase_form_field}</li>
                <li class="sub_col2">{$LANG.phrase_field_type}</li>
                <li class="sub_col3">{$LANG.phrase_pass_on}</li>
                <li class="sub_col4">{$LANG.phrase_field_size}</li>
                <li class="sub_col5">{$LANG.phrase_sort_as}</li>
                <li class="sub_col6">{$LANG.phrase_db_column}<span class="pad_right">&nbsp;</span><input type="button" value="{$LANG.phrase_smart_fill}"
                  onclick="return fields_ns.smart_fill()" class="bold"/></li>
              </ul>
            </li>
            <li class="splitter"></li>
          </ul>
        </li>
        <li class="col4 edit"></li>
        <li class="col5 colN {if $field.is_system_field == "no"}del{/if}"></li>
      </ul>
      <div class="clear"></div>
      <ul class="rows check_areas" id="rows">
        {assign var=previous_item value=""}
        {foreach from=$form_fields item=field name=row}
          {assign var='count' value=$smarty.foreach.row.index}
          {assign var='field_id' value=$field.field_id}

          {if $field.is_new_sort_group == "yes" || $count == 0}
            {if $previous_item != ""}
              </div>
              <div class="clear"></div>
            </li>
            {/if}
            <li class="sortable_row">
              {assign var=next_item_is_new_sort_group value=$form_fields[$smarty.foreach.row.iteration].is_new_sort_group}
              <div class="row_content{if $next_item_is_new_sort_group == 'no'} grouped_row{/if}">
          {/if}

          {assign var=previous_item value=$field}

            <div class="row_group{if $field.is_system_field == "yes"} system_field{/if} {if $smarty.foreach.row.last} rowN{/if}">
              <input type="hidden" class="sr_order" value="{$field_id}" />
              <ul>
                <li class="col1 sort_col">{$count+$order_start_number}</li>
                <li class="col2">
                  <input type="text" name="field_{$field_id}_display_name" id="field_{$field_id}_display_name"
                    value="{$field.field_title|escape}" class="display_text" />
                </li>
                <li class="splitter"></li>
                <li class="col3 scrollable">
                  <ul class="scroll-content">
                    <li class="sub_col1">
                      {if $field.is_system_field == "yes"}
                        <span class="pad_left_small medium_grey">{$LANG.word_na}</span>
                      {else}
                        <input type="text" name="field_{$field_id}_name" id="field_{$field_id}_name" value="{$field.field_name}"
                          class="field_names" />
                      {/if}
                    </li>
                    <li class="sub_col2">
                      <input type="hidden" name="old_field_{$field_id}_type_id" id="old_field_{$field_id}_type_id" class="system_field_type_id" value="{$field.field_type_id}" />
                      {if $field.is_system_field == "yes"}
                        <span class="pad_left_small medium_grey system_field_type_label">
                        {if     $field.col_name == "ip_address"}
                          {$LANG.phrase_ip_address}
                        {elseif $field.col_name == "submission_date"}
                          {$LANG.phrase_submission_date}
                        {elseif $field.col_name == "last_modified_date"}
                          {$LANG.phrase_last_modified_date}
                        {elseif $field.col_name == "submission_id"}
                          {$LANG.phrase_submission_id}
                        {/if}
                        </span>
                        <input type="hidden" name="system_fields[]" value="{$field_id}" />
                      {else}
                        {display_field_types_dropdown name="field_`$field_id`_type_id" id="field_`$field_id`_type_id" default=$field.field_type_id class="field_types"}
                      {/if}
                    </li>
                    <li class="sub_col3 check_area">
                      <input type="checkbox" name="field_{$field_id}_include_on_redirect" id="field_{$field_id}_include_on_redirect"
                        {if $field.include_on_redirect == "yes"}checked="checked"{/if} class="pass_on" />
                    </li>
                    <li class="sub_col4">
                      {if $field.is_system_field == "yes"}
                        <span class="pad_left_small medium_grey">{$LANG.word_na}</span>
                      {else}
                        <input type="hidden" name="old_field_{$field_id}_size" id="old_field_{$field_id}_size" value="{$field.field_size}" />
                        <div class="field_sizes_div">
                        {field_sizes_dropdown name="field_`$field_id`_size" id="field_`$field_id`_size" default=$field.field_size
                          field_type_id=$field.field_type_id class="field_sizes"}
                        </div>
                      {/if}
                    </li>
                    <li class="sub_col5">
                      {if $field.is_system_field == "yes"}
                        <span class="pad_left_small medium_grey">{$LANG.word_na}</span>
                      {else}
                        <select name="field_{$field_id}_data_type" id="field_{$field_id}_data_type" class="data_types">
                          <option {if $field.data_type == "string"}selected{/if} value="string">{$LANG.word_string}</option>
                          <option {if $field.data_type == "number"}selected{/if} value="number">{$LANG.word_number}</option>
                        </select>
                      {/if}
                    </li>
                    <li class="sub_col6">
                      {if $field.is_system_field == "yes"}
                        <span class="pad_left_small medium_grey system_field_db_column">{$field.col_name}</span>
                      {else}
                        <input type="hidden" name="old_col_{$field_id}_name" id="old_col_{$field_id}_name" value="{$field.col_name}" />
                        <input type="text" name="col_{$field_id}_name" id="col_{$field_id}_name" class="db_column" value="{$field.col_name}" maxlength="64" />
                      {/if}
                    </li>
                  </ul>
                </li>
                <li class="splitter"></li>
                <li class="col4 edit"></li>
                <li class="col5 colN {if $field.is_system_field == "no"}del{/if}"></li>
              </ul>
              <div class="clear"></div>
            </div>

          {if $smarty.foreach.row.last}
            </div>
            <div class="clear"></div>
          </li>
          {/if}

        {/foreach}
      </ul>
    </div>

    <div class="scroll-pane ui-corner-all" style="border: 0px; margin-left: 239px; margin-top: -2px">
      <div class="scroll-bar-wrap ui-widget-content ui-corner-bottom" style="border: 1px solid #aaaaaa; border-top: 0px; margin: 0px; height:20px">
        <div class="scroll-bar-bottom"></div>
      </div>
    </div>
    {/strip}

    <div class="clear"></div>

    <div class="margin_top_large">
      <input type="submit" name="update_fields" value="{$LANG.word_update}" />
      {template_hook location="admin_edit_form_fields_tab_button_row"}
    </div>
  </form>

  <form onsubmit="return fields_ns.add_fields()">
    <table cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-top: -23px">
    <tr>
      <td align="right">
        {$LANG.word_add} <input type="text" id="add_num_fields" size="3" value="1" /> {$LANG.word_field_sp}
        <select id="new_field_position">
          <option value="end">{$LANG.phrase_at_end}</option>
          <option value="start">{$LANG.phrase_at_start}</option>
          <optgroup label="{$LANG.word_after}" id="add_fields_list">
            {foreach from=$form_fields item=field name=row}
              <option value="{$field.field_id}">{$field.field_title}</option>
            {/foreach}
          </optgroup>
        </select>
        <input type="checkbox" id="group_new_fields" />
          <label for="group_new_fields">{$LANG.phrase_group_rows}</label>
        <input type="submit" name="add_field" id="add_field" value="{$LANG.word_add}" />
      </td>
    </tr>
    </table>
    {if $limit_fields}
      <div class="right medium_grey italic">{$LANG.text_limit_fields_info}</div>
    {/if}
    <div class="clear"></div>

  </form>

  <div class="hidden" id="new_row_template">
    <div class="row_group">
      <input type="hidden" class="sr_order" value="%%ROW%%" />
      <ul>
        <li class="col0"></li>
        <li class="col1 sort_col"></li>
        <li class="col2"><input type="text" name="field_%%ROW%%_display_name" id="field_%%ROW%%_display_name" value="" class="display_text" /></li>
        <li class="splitter"></li>
        <li class="col3 scrollable">
          <ul class="scroll-content">
            <li class="sub_col1">
              <input type="text" name="field_%%ROW%%_name" id="field_%%ROW%%_name" value="" class="field_names" />
            </li>
            <li class="sub_col2">
              {display_field_types_dropdown name="field_%%ROW%%_type_id" id="field_%%ROW%%_type_id" class="field_types"}
            </li>
            <li class="sub_col3 check_area">
              <input type="checkbox" name="field_%%ROW%%_include_on_redirect" id="field_%%ROW%%_include_on_redirect" class="pass_on" />
            </li>
            <li class="sub_col4">
              <div class="field_sizes_div">
                {field_sizes_dropdown name="field_%%ROW%%_size" id="field_%%ROW%%_size" field_type_id="" default="medium" class="field_sizes"}
              </div>
            </li>
            <li class="sub_col5">
              <select name="field_%%ROW%%_data_type" class="data_types">
                <option value="string">{$LANG.word_string}</option>
                <option value="number">{$LANG.word_number}</option>
              </select>
            </li>
            <li class="sub_col6">
              <input type="text" name="col_%%ROW%%_name" id="col_%%ROW%%_name" class="db_column" value="" maxlength="64" />
            </li>
          </ul>
        </li>
        <li class="splitter"></li>
        <li class="col4 edit"></li>
        <li class="col5 colN del"> </li>
      </ul>
      <div class="clear"></div>
    </div>
  </div>

  <div class="hidden tabbed_dialog" id="edit_field_template">
    <div id="edit_field_template_message" class="margin_bottom_small hidden"></div>
    <div id="edit_field_template_new_field" class="margin_bottom_small notify hidden">
      <div style="padding: 8px">
        {$LANG.notify_edit_field_new_field}
      </div>
    </div>
    <div class="inner_tabset ft_dialog" id="edit_field">
      <div class="tab_row threeCols">
        <div class="inner_tab1 selected">{$LANG.phrase_main_settings}</div>
        <div class="inner_tab2"></div>
        <div class="inner_tab3">Validation</div>
      </div>
      <div class="inner_tab_content">
        <div class="inner_tab_content1">
          <form id="edit_field_form_tab1">
            <table cellspacing="0" cellpadding="0">
            <tr>
              <td width="180"><label for="edit_field__display_text">{$LANG.phrase_display_text}</label></td>
              <td>
                <input type="text" id="edit_field__display_text" name="edit_field__display_text" />
              </td>
            </tr>
            <tr>
              <td><label for="edit_field__field_name">{$LANG.phrase_form_field}</label></td>
              <td>
                <div class="edit_field__non_system"><input type="text" id="edit_field__field_name" name="edit_field__field_name" /></div>
                <div class="edit_field__system medium_grey">{$LANG.word_na}</div>
              </td>
            </tr>
            <tr>
              <td><label for="edit_field__field_type">{$LANG.phrase_field_type}</label></td>
              <td>
                <div class="edit_field__non_system">
                  {display_field_types_dropdown id="edit_field__field_type" name="edit_field__field_type" default=$field.field_type_id}
                </div>
                <div id="edit_field__field_type_system" class="edit_field__system medium_grey"></div>
              </td>
            </tr>
            <tr>
              <td><label for="edit_field__pass_on">{$LANG.phrase_pass_on}</label></td>
              <td>
                <input type="checkbox" id="edit_field__pass_on" name="edit_field__pass_on" />
              </td>
            </tr>
            <tr>
              <td>{$LANG.phrase_field_size}</td>
              <td>
                <div class="edit_field__non_system" id="edit_field__field_size_div"></div>
                <div class="edit_field__system medium_grey">{$LANG.word_na}</div>
              </td>
            </tr>
            <tr>
              <td>{$LANG.phrase_data_type}</td>
              <td>
                <div class="edit_field__non_system">
                  <select id="edit_field__data_type" name="edit_field__data_type">
                    <option value="string">{$LANG.word_string}</option>
                    <option value="number">{$LANG.word_number}</option>
                  </select>
                </div>
                <div class="edit_field__system medium_grey">{$LANG.word_na}</div>
              </td>
            </tr>
            <tr>
              <td><label for="edit_field__db_column">{$LANG.phrase_db_column}</label></td>
              <td>
                <div class="edit_field__non_system" id="edit_field__db_column_div">
                  <input type="text" id="edit_field__db_column" name="edit_field__db_column" maxlength="64" />
                </div>
                <div id="edit_field__db_column_div_system" class="edit_field__system medium_grey"></div>
              </td>
            </tr>
            </table>
          </form>
        </div>
        <div class="inner_tab_content2" style="display:none">
          <form id="edit_field_form_tab2">
            <div id="edit_field__field_settings_loading" class="medium_grey">{$LANG.phrase_loading_ellipsis}</div>
            <div id="edit_field__field_settings"></div>
          </form>
        </div>
        <div class="inner_tab_content3" style="display:none">
          <form id="edit_field_form_tab3">
            <div class="edit_field__non_system" id="validation_table"></div>
	          <div class="edit_field__system medium_grey"><i>{$LANG.phrase_system_fields_no_validation}</i></div>
          </form>
        </div>
      </div>
    </div>
    <a class="prev_field field_nav">{$LANG.phrase_previous_field}</a>
    <a class="next_field field_nav">{$LANG.phrase_next_field}</a>
  </div>
