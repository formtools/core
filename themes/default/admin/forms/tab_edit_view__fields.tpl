    <div class="hint margin_bottom">
      {$LANG.text_view_fields_info}
    </div>

    <div id="no_view_fields_defined" class="margin_bottom" {if $grouped_fields|@count > 0}style="display:none"{/if}>
      <div class="error">
        <div style="padding: 6px">
          {$LANG.text_no_fields_in_view}
        </div>
      </div>
    </div>

    <div id="allow_editable_fields_toggle" class="margin_bottom_large" {if $grouped_fields|@count == 0}style="display:none"{/if}>
      <input type="checkbox" name="may_edit_submissions" id="cmes" value="yes"
        onchange="view_ns.toggle_editable_fields(this.checked)" {if $view_info.may_edit_submissions == "yes"}checked{/if} />
      <label for="cmes">{$LANG.phrase_allow_fields_edited}</label>
    </div>

    <div class="sortable_groups check_areas" id="{$view_fields_sortable_id}">
	  <input type="hidden" class="sortable__add_group_handler" value="view_ns.add_field_group" />
	  <input type="hidden" class="sortable__delete_group_handler" value="view_ns.delete_field_group" />
	  <input type="hidden" class="sortable__class" value="groupable edit_view_fields" />
	  <input type="hidden" class="sortable__new_group_name" value="{$LANG.phrase_view_field_group}" />
	  <input type="hidden" name="deleted_groups" id="deleted_groups" value="" />

      {foreach from=$grouped_fields item=curr_group_info name=group}
        {assign var=group_info value=$curr_group_info.group}
        {assign var=view_fields value=$curr_group_info.fields}

          <div class="sortable_group">
            <div class="sortable_group_header">
              <div class="sort"></div>
              <label>{$LANG.phrase_view_field_group}</label>
              <input type="text" name="group_name_{$group_info.group_id}" class="group_name" value="{eval var=$group_info.group_name}" />
              <select name="group_tab_{$group_info.group_id}" class="tabs_dropdown">
                <optgroup label="{$LANG.phrase_available_tabs}">
                {assign var=has_tabs value=false}
                {foreach from=$view_tabs item=view_tab name=tab_row}
                  {assign var='counter' value=$smarty.foreach.tab_row.iteration}
                  {* this only shows the tabs that have a label *}
                  {if $view_tab.tab_label}
                    {assign var=has_tabs value=true}
                    <option value="{$counter}"{if $counter == $group_info.custom_data} selected{/if}>{$view_tab.tab_label}</option>
                  {/if}
                {/foreach}
                {if !$has_tabs}<option value="">{$LANG.validation_no_tabs_defined}</option>{/if}
                </optgroup>
              </select>
              <div class="delete_group"></div>
              <input type="hidden" class="group_order" value="{$group_info.group_id}" />
              <div class="clear"></div>
            </div>

            <div class="sortable groupable edit_view_fields">
              <ul class="header_row">
                <li class="col1">{$LANG.word_order}</li>
                <li class="col2">{$LANG.word_field}</li>
                <li class="col3">{$LANG.phrase_field_type}</li>
                <li class="col4">{$LANG.word_editable}</li>
                <li class="col5">{$LANG.word_searchable}</li>
                <li class="col6 colN del"></li>
              </ul>
              <div class="clear"></div>
              <ul class="rows connected_sortable">
              <li class="sortable_row empty_group{if $view_fields|@count != 0} hidden{/if}"><div class="clear"></div></li>

              {assign var=previous_item value=""}
              {foreach from=$view_fields item=field name=row}
                {assign var='field_id' value=$field.field_id}
                {assign var='index' value=$smarty.foreach.row.index}
                {assign var='count' value=$smarty.foreach.row.iteration}

                {if $field.view_field_is_new_sort_group == "yes"}
                  {if $previous_item != ""}
                    </div>
                    <div class="clear"></div>
                  </li>
                  {/if}
                  <li class="sortable_row{if $smarty.foreach.row.last} rowN{/if}">
                  {assign var=next_item_is_new_sort_group value=$view_fields[$smarty.foreach.row.iteration].view_field_is_new_sort_group}
                  <div class="row_content{if $next_item_is_new_sort_group == 'no'} grouped_row{/if}">
                {/if}

                {assign var=previous_item value=$field}

                <div class="row_group">
                  <input type="hidden" class="sr_order" value="{$field_id}" />
                  <ul>
                    <li class="col1 sort_col">{$field.list_order}</li>
                    <li class="col2">{$field.field_title}</li>
                    <li class="col3 medium_grey">{$field_types[$field.field_type_id]}</li>
                    <li class="col4 {if $field.col_name != "submission_id" && $field.col_name != "last_modified_date"}check_area{/if}">
                      {* everything except the Submission ID and Last Modified Date is editable *}
                      {if $field.col_name != "submission_id" && $field.col_name != "last_modified_date"}
                        <input type="checkbox" name="editable_fields[]" value="{$field_id}" class="editable_fields" {if $field.is_editable == "yes"}checked{/if}
                          {if $view_info.may_edit_submissions == "no"}disabled{/if} />
                      {/if}
                    </li>
                    <li class="col5 check_area">
                      <input type="checkbox" name="searchable_fields[]" value="{$field_id}" {if $field.is_searchable == "yes"}checked{/if} />
                    </li>
                    <li class="col6 colN del"><a href="#" onclick="return view_ns.remove_view_field({$field_id})"></a></li>
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
        <div class="clear"></div>
        <div class="sortable_group_footer">
          <a href="#" class="add_field_link">{$LANG.phrase_add_fields_rightarrow}</a>
        </div>
      </div>

      <div class="clear"></div>
      {/foreach}
    </div>

    <div>
      <a href="#" class="custom_add_group_link">{$LANG.phrase_add_new_group_rightarrow}</a>
    </div>

    <div class="hidden add_fields_popup" id="add_fields_popup">
      <div class="error margin_bottom_large hidden"><div style="padding: 6px"></div></div>
      <table cellspacing="1" cellpadding="3" width="100%" height="100%">
      <tr>
        <td width="140" valign="top">{$LANG.phrase_available_fields}</td>
        <td>
          <div class="view_fields_list" id="add_fields_popup_available_fields"></div>
          <div class="grey_box two_buttons">
            <input type="button" value="{$LANG.phrase_select_all}" onclick="view_ns.add_fields_select_all()" />
            <input type="button" value="{$LANG.phrase_unselect_all}" onclick="view_ns.add_fields_unselect_all()" />
          </div>
        </td>
      </tr>
      </table>
    </div>

    <div class="hidden add_view_group_popup" id="add_group_popup">
      <input type="hidden" class="add_group_popup_title" value="{$LANG.phrase_create_new_view_group}" />
      <div class="add_field_error hidden error"></div>

      <table cellspacing="1" cellpadding="3" width="100%" height="100%">
      <tr>
        <td width="140">{$LANG.phrase_group_name}</td>
        <td><input type="text" class="new_group_name" placeholder="(optional)" /></td>
      </tr>
      <tr>
        <td valign="top">{$LANG.phrase_available_fields}</td>
        <td>
          <div class="view_fields_list" id="add_group_popup_available_fields"></div>
          <div class="grey_box two_buttons">
            <input type="button" value="{$LANG.phrase_select_all}" onclick="view_ns.add_fields_select_all()" />
            <input type="button" value="{$LANG.phrase_unselect_all}" onclick="view_ns.add_fields_unselect_all()" />
          </div>
        </td>
      </tr>
      </table>
    </div>

    <!-- for the add group functionality -->
    <div id="sortable__new_group_header" class="hidden">
      <ul class="header_row">
        <li class="col1">{$LANG.word_order}</li>
        <li class="col2">{$LANG.word_field}</li>
        <li class="col3">{$LANG.phrase_field_type}</li>
        <li class="col4">{$LANG.word_editable}</li>
        <li class="col5">{$LANG.word_searchable}</li>
        <li class="col6 colN del"></li>
      </ul>
    </div>
    <div id="sortable__new_group_footer" class="hidden">
      <div class="sortable_group_footer">
        <a href="#" class="add_field_link">{$LANG.phrase_add_fields_rightarrow}</a>
      </div>
    </div>
