
  <form action="{$same_page}" id="option_list_form" method="post">
    <input type="hidden" name="update_page" value="1" />
    <input type="hidden" name="num_rows" id="num_rows" value="{$total_options}" />

    {ft_include file='messages.tpl'}

    {if $num_fields_using_option_list > 1}
      <div class="hint margin_bottom_large">
        {$text_option_list_used_by_fields}
      </div>
    {/if}

    <table cellspacing="1" cellpadding="1" class="margin_bottom_large">
    <tr>
      <td valign="top" width="160" class="pad_left_small">
        <label for="option_list_name">{$LANG.phrase_option_list_name}</label>
      </td>
      <td>
        <input type="text" name="option_list_name" id="option_list_name" maxlength="100" style="width:300px" value="{$list_info.option_list_name|escape}" />
        <div class="light_grey">{$LANG.text_group_name_explanation}</div>
      </td>
    </tr>
    <tr>
      <td valign="top" class="pad_left_small">{$LANG.phrase_group_options_q}</td>
      <td>
        <input type="radio" name="is_grouped" id="go1" value="yes" {if $list_info.is_grouped == "yes"}checked{/if} />
          <label for="go1">{$LANG.word_yes}</label>
        <input type="radio" name="is_grouped" id="go2" value="no" {if $list_info.is_grouped == "no"}checked{/if} />
          <label for="go2">{$LANG.word_no}</label>
        <div class="light_grey">{$LANG.text_option_list_group_explanation}</div>
      </td>
    </tr>
    </table>

    <div class="sortable_groups" id="{$sortable_id}">
      <input type="hidden" class="sortable__class" value="edit_option_list groupable" />
      <input type="hidden" class="sortable__delete_tooltip" value="{$L.phrase_delete_row}" />
      <input type="hidden" class="sortable__new_group_name" value="{$LANG.phrase_group_name}" />
      <input type="hidden" class="sortable__delete_group_handler" value="sf_ns.delete_group" />

      {* the duplicate markup sucks, but it's simple this way *}
      {if $list_info.options|@count == 0}
        <div class="sortable_group">
          <div class="sortable_group_header{if $list_info.is_grouped == "no"} hidden{/if}">
            <div class="sort"></div>
            <label>{$LANG.phrase_group_name}</label>
            <input type="text" name="group_name_NEW1" class="group_name" value="{if $group_info.group_name}{eval var=$group_info.group_name}{/if}" />
            <div class="delete_group"></div>
            <input type="hidden" class="group_order" value="NEW1" />
            <div class="clear"></div>
          </div>
          <div class="sortable groupable edit_option_list">
            <ul class="header_row">
              <li class="col1">{$LANG.word_order}</li>
              <li class="col2">{$LANG.phrase_field_value}</li>
              <li class="col3">{$LANG.phrase_display_text}</li>
              <li class="col4 colN del"></li>
            </ul>
            <div class="clear"></div>
            <ul class="rows">
              <li class="sortable_row empty_group"><div class="clear"></div></li>
            </ul>
          </div>
          <div class="sortable_group_footer padded_footer{if $list_info.is_grouped == "no"} hidden{/if}">
            <div class="right pad_right pad_bottom">
              {$LANG.word_add} <input type="text" class="num_rows_to_add_to_group" size="3" value="1" />
              <input type="button" class="add_rows_to_group_button" value="{$LANG.word_field_sp}" />
              <div class="clear"></div>
            </div>
          </div>
        </div>
      {/if}

      {assign var="running_row_count" value=0}
      {foreach from=$list_info.options item=curr_group_info name=group}
        {assign var=group_info value=$curr_group_info.group_info}
        {assign var=options value=$curr_group_info.options}

        <div class="sortable_group">
          <div class="sortable_group_header{if $list_info.is_grouped == "no"} hidden{/if}">
            <div class="sort"></div>
            <label>{$LANG.phrase_group_name}</label>
            <input type="text" name="group_name_{$group_info.group_id}" class="group_name" value="{eval var=$group_info.group_name}" />
            <div class="delete_group"></div>
            <input type="hidden" class="group_order" value="{$group_info.group_id}" />
            <div class="clear"></div>
          </div>
          <div class="sortable groupable edit_option_list">
            <ul class="header_row">
              <li class="col1">{$LANG.word_order}</li>
              <li class="col2">{$LANG.phrase_field_value}</li>
              <li class="col3">{$LANG.phrase_display_text}</li>
              <li class="col4 colN del"></li>
            </ul>
            <div class="clear"></div>
            <ul class="rows connected_sortable">
            <li class="sortable_row empty_group{if $options|@count != 0} hidden{/if}"><div class="clear"></div></li>
            {assign var=previous_item value=""}
            {foreach from=$options item=option name=row}
              {assign var="running_row_count" value=$running_row_count+1}
              {assign var=count value=$smarty.foreach.row.iteration}
                {if $option.is_new_sort_group == "yes"}
                  {if $previous_item != ""}
                    </div>
                    <div class="clear"></div>
                  </li>
                  {/if}
                  <li class="sortable_row">
                   {assign var=next_item_is_new_sort_group value=$options[$smarty.foreach.row.iteration].is_new_sort_group}
                   <div class="row_content{if $next_item_is_new_sort_group == 'no'} grouped_row{/if}">
                {/if}

                {assign var=previous_item value=$i}

                <div class="row_group{if $smarty.foreach.row.last} rowN{/if}">
                  <input type="hidden" class="sr_order" value="{$running_row_count}" />
                  <ul>
                    <li class="col1 sort_col">{$count}</li>
                    <li class="col2"><input type="text" name="field_option_value_{$running_row_count}" value="{$option.option_value|escape}" /></li>
                    <li class="col3"><input type="text" name="field_option_text_{$running_row_count}" value="{$option.option_name|escape}" /></li>
                    <li class="col4 colN del"></li>
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
            <div class="clear"></div>
          </div>
          <div class="sortable_group_footer padded_footer{if $list_info.is_grouped == "no"} hidden{/if}">
            <div class="right pad_right pad_bottom">
              {$LANG.word_add} <input type="text" class="num_rows_to_add_to_group" size="3" value="1" />
              <input type="button" class="add_rows_to_group_button" value="{$LANG.word_field_sp}" />
            </div>
          </div>
        </div>
      {/foreach}
      <div class="clear"></div>
    </div>

    <div class="add_group_section {if $list_info.is_grouped == "no"} hidden{/if}">
      <a href="#" class="add_group_link">{$LANG.phrase_add_new_group_rightarrow}</a>
    </div>

    <div style="float: left" class="margin_top_large">
      <input type="submit" name="update" value="{$LANG.word_update}" />
      {template_hook location="edit_option_list_main"}
    </div>
  </form>

  <div class="margin_top right add_ungrouped_rows{if $list_info.is_grouped == "yes"} hidden{/if}">
    <form action="" method="post">
      {$LANG.word_add} <input type="text" id="num_rows_to_add" size="3" value="1" />
      <input type="submit" id="add_rows_button" value="{$LANG.word_field_sp}" />
    </form>
  </div>


  <div class="clear"></div>

  <div class="grey_box margin_top_large">
    <div><a href="#" id="option_lists_advanced_settings_link">{$LANG.phrase_import_option_list_rightarrow}</a></div>
    <div class="hidden" id="option_lists_advanced_settings">
      <div id="smart_fill_messages"></div>
      <table cellspacing="1" cellpadding="0" width="100%" class="margin_bottom_large" height="40">
      <tr>
        <td> </td>
        <td class="light_grey">{$LANG.phrase_form_field_name}</td>
        <td class="light_grey">{$LANG.phrase_form_url}</td>
        <td colspan="2"> </td>
      </tr>
      <tr>
        <td nowrap>{$LANG.phrase_smart_fill_fields_from_c}</td>
        <td><input type="text" id="smart_fill_source_form_field" style="width:150px" /></td>
        <td><input type="text" id="smart_fill_source_url" style="width:250px" /></td>
        <td><input type="button" value="{$LANG.phrase_smart_fill|upper}" onclick="sf_ns.smart_fill_field()" /></td>
        <td width="50" align="center">
          <div id="ajax_activity" style="display:none"><img src="{$images_url}/ajax_activity_light_grey.gif" /></div>
          <div id="ajax_no_activity"><img src="{$images_url}/ajax_no_activity_light_grey.gif" /></div>
        </td>
      </tr>
      </table>

      <div class="margin_top_large">
        <div style="float:right"><a href="http://docs.formtools.org/userdoc2_1/index.php?page=fog_editing" target="_blank">{$LANG.phrase_smart_fill_user_documentation}</a></div>
      </div>
      <div class="clear"></div>
    </div>
  </div>

  <div id="upload_files_text" style="display:none">
    {$LANG.text_smart_fill_option_list_problem}

    <form action="{$g_root_url}/global/code/actions.php?action=upload_scraped_page_for_smart_fill"
      target="hidden_iframe" method="post" enctype="multipart/form-data"
      onsubmit="return sf_ns.validate_upload_file(this)">
      <input type="hidden" name="num_pages" value="1" />

      <table cellspacing="0" cellpadding="0" class="margin_top margin_bottom">
      <tr>
        <td width="90">{$LANG.word_page}</td>
        <td><input type="file" name="form_page_1" /></td>
      </tr>
      <tr>
        <td> </td>
        <td><input type="submit" value="{$LANG.phrase_upload_file}" class="margin_top_small" /></td>
      </tr>
      </table>
    </form>
  </div>

  <div class="hidden add_group_popup" id="add_group_popup">
    <input type="hidden" class="add_group_popup_title" value="{$LANG.phrase_create_new_option_list_group}" />
    <input type="hidden" class="sortable__add_group_handler" value="sf_ns.add_group" />
    <div class="add_field_error hidden error"></div>
    <table cellspacing="1" cellpadding="3" width="100%">
    <tr>
      <td width="140">{$LANG.phrase_group_name}</td>
      <td><input type="text" class="new_group_name" /></td>
    </tr>
    </table>
  </div>

  <!-- for the add group functionality -->
  <div id="sortable__new_group_header" class="hidden">
    <ul class="header_row">
      <li class="col1">{$LANG.word_order}</li>
      <li class="col2">{$LANG.phrase_field_value}</li>
      <li class="col3">{$LANG.phrase_display_text}</li>
      <li class="col4 colN del"></li>
    </ul>
  </div>
  <div id="sortable__new_group_footer" class="hidden">
    <div class="sortable_group_footer padded_footer">
	    <div class="right pad_right pad_bottom">
	      {$LANG.word_add} <input type="text" class="num_rows_to_add_to_group" size="3" value="1" />
	      <input type="button" class="add_rows_to_group_button" value="{$LANG.word_field_sp}" />
	    </div>
    </div>
  </div>

  <iframe name="hidden_iframe" id="hidden_iframe" src="" style="width: 0px; height: 0px" frameborder="0"
    onload="sf_ns.log_form_page_as_loaded()"></iframe>

