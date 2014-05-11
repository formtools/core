  <div class="subtitle underline margin_top_large">
    {$LANG.word_views|upper}
  </div>

  {ft_include file='messages.tpl'}

  <div class="margin_bottom_large">
    {$LANG.text_view_tab_summary}
  </div>

  <form action="{$same_page}" method="post" id="views_form">
    <input type="hidden" name="page" value="views" />
    <input type="hidden" id="form_id" value="{$form_id}" />

    {if $grouped_views|@count == 0}
      <div class="error yellow_bg" class="margin_bottom_large">
        <div style="padding:8px">
          {$LANG.notify_no_views_defined}
        </div>
      </div>
      <div class="margin_top_large">
        <input type="submit" name="recreate_initial_view" value="{$LANG.phrase_create_default_view}" />
      </div>
    {else}
      <div class="sortable_groups" id="{$sortable_id}">
        <input type="hidden" class="sortable__custom_delete_handler" value="view_ns.delete_view" />

      {foreach from=$grouped_views item=curr_group_info name=group}
        {assign var=group_info value=$curr_group_info.group}
        {assign var=views value=$curr_group_info.views}

        <div class="sortable_group">
          <div class="sortable_group_header">
            <div class="sort"></div>
            <label>{$LANG.phrase_view_group}</label>
            <input type="text" name="group_name_{$group_info.group_id}" class="group_name" value="{eval var=$group_info.group_name}" />
            <div class="delete_group"></div>
            <input type="hidden" class="group_order" value="{$group_info.group_id}" />
            <div class="clear"></div>
          </div>

          <div class="sortable groupable view_list">
            <ul class="header_row">
              <li class="col0"> </li>
              <li class="col1">{$LANG.word_order}</li>
              <li class="col2">{$LANG.phrase_view_id}</li>
              <li class="col3">{$LANG.phrase_view_name}</li>
              <li class="col4">{$LANG.phrase_who_can_access}</li>
              <li class="col5"><div title="{$LANG.word_columns_sp}"></div></li>
              <li class="col6"><div title="{$LANG.word_fields_sp}"></div></li>
              <li class="col7"><div title="{$LANG.word_tabs_sp}"></div></li>
              <li class="col8"><div title="{$LANG.word_filters_sp}"></div></li>
              <li class="col9 edit"></li>
              <li class="col10 colN del"></li>
            </ul>
            <div class="clear"></div>
            <ul class="rows connected_sortable">
              <li class="sortable_row empty_group{if $views|@count != 0} hidden{/if}"><div class="clear"></div></li>

            {assign var=previous_item value=""}
            {foreach from=$views key=k item=view name=row}

              {assign var='index' value=$smarty.foreach.row.index}
              {assign var='count' value=$smarty.foreach.row.iteration}
              {assign var='view_id' value=$view.view_id}

              {if $view.is_new_sort_group == "yes"}
                {if $previous_item != ""}
                  </div>
                  <div class="clear"></div>
                </li>
                {/if}
                <li class="sortable_row">
                {assign var=next_item_is_new_sort_group value=$views[$smarty.foreach.row.iteration].is_new_sort_group}
                <div class="row_content{if $next_item_is_new_sort_group == 'no'} grouped_row{/if}">
              {/if}

              {assign var=previous_item value=$view}

                <div class="row_group{if $smarty.foreach.row.last} rowN{/if}">
                  <input type="hidden" class="sr_order" value="{$view.view_id}" />
                  <ul>
                    <li class="col0"></li>
                    <li class="col1 sort_col">{$count}</li>
                    <li class="col2">{$view.view_id}</li>
                    <li class="col3">{$view.view_name}</li>
                    <li class="col4">
                      {if $view.access_type == 'admin'}
                        <span class="pad_left_small medium_grey">{$LANG.phrase_admin_only}</span>
                      {elseif $view.access_type == 'public'}
                        {if $view.client_omit_list|@count == 0}
                          <span class="pad_left_small blue">{$LANG.phrase_all_clients}</span>
                        {else}
                          {clients_dropdown name_id="" only_show_clients=$view.client_omit_list
                            include_blank_option=true blank_option=$LANG.phrase_all_clients_except_c}
                        {/if}
                      {elseif $view.access_type == 'hidden'}
                        <span class="pad_left_small light_grey italic">{$LANG.word_none} - {$LANG.word_hidden}</span>
                      {elseif $view.client_info|@count > 0}
                        {if $view.client_info|@count == 1}
                          {$view.client_info[0].first_name} {$view.client_info[0].last_name}
                        {else}
                          <select>
                            {foreach from=$view.client_info item=user name=user_row}
                              <option>{$user.first_name} {$user.last_name}</option>
                            {/foreach}
                          </select>
                        {/if}
                      {else}
                        <span class="pad_left_small light_grey">{$LANG.phrase_no_clients}</span>
                      {/if}
                    </li>
                    <li class="col5"><a href="edit.php?page=edit_view&view_id={$view_id}&edit_view_tab=2" title="{$view.columns|@count} {$LANG.word_columns_sp|lower}">{$view.columns|@count}</a></li>
                    <li class="col6"><a href="edit.php?page=edit_view&view_id={$view_id}&edit_view_tab=3" title="{$view.fields|@count} {$LANG.word_fields_sp|lower}">{$view.fields|@count}</a></li>
                    <li class="col7"><a href="edit.php?page=edit_view&view_id={$view_id}&edit_view_tab=4" title="{$view.tabs|@count} {$LANG.word_tabs_sp|lower}">{$view.tabs|@count}</a></li>
                    <li class="col8"><a href="edit.php?page=edit_view&view_id={$view_id}&edit_view_tab=5" title="{$view.filters|@count} {$LANG.word_filters_sp|lower}">{$view.filters|@count}</a></li>
                    <li class="col9 edit"><a href="edit.php?page=edit_view&view_id={$view_id}"></a></li>
                    <li class="col10 colN del"> </li>
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
          <a href="#" class="add_field_link">{$LANG.phrase_add_view_rightarrow}</a>
        </div>
      </div>

      <div class="clear"></div>
      {/foreach}
    </div>

    <div class="margin_bottom_large">
      <a href="#" class="add_group_link">{$LANG.phrase_add_new_group_rightarrow}</a>
    </div>

    <p>
      <input type="submit" name="update_views" value="{$LANG.word_update}" />
      {template_hook location="admin_edit_form_views_tab_button_row"}
    </p>

    {/if}
  </form>

  <div id="new_view_dialog" class="ft_dialog hidden">
    <table>
    <tr>
      <td width="140">{$LANG.phrase_view_name}</td>
      <td>
        <input type="text" id="new_view_name" />
      </td>
    </tr>
    {if $num_views > 0}
      <tr>
        <td>{$LANG.phrase_base_view_on}</td>
        <td>
          {views_dropdown name_id="create_view_from_view_id" show_empty_label=true form_id=$form_id
            create_view_dropdown=true show_empty_label=false}
        </td>
      </tr>
    {/if}
    </table>
  </div>

  <!-- for the add group functionality -->
  <input type="hidden" class="sortable__new_group_name" value="{$LANG.phrase_view_group}" />
  <input type="hidden" class="sortable__class" value="view_list" />
  <div id="sortable__new_group_header" class="hidden">
    <ul class="header_row">
      <li class="col0"> </li>
      <li class="col1">{$LANG.word_order}</li>
      <li class="col2">{$LANG.phrase_view_id}</li>
      <li class="col3">{$LANG.phrase_view_name}</li>
      <li class="col4">{$LANG.phrase_who_can_access}</li>
      <li class="col5"><div title="{$LANG.word_columns_sp}"></div></li>
      <li class="col6"><div title="{$LANG.word_fields_sp}"></div></li>
      <li class="col7"><div title="{$LANG.word_tabs_sp}"></div></li>
      <li class="col8"><div title="{$LANG.word_filters_sp}"></div></li>
      <li class="col9 edit"></li>
      <li class="col10 colN del"></li>
    </ul>
  </div>
  <div id="sortable__new_group_footer" class="hidden">
    <div class="sortable_group_footer">
      <a href="#" class="add_field_link">{$LANG.phrase_add_view_rightarrow}</a>
    </div>
  </div>

  <div class="hidden add_group_popup" id="add_group_popup">
    <input type="hidden" class="add_group_popup_title" value="{$LANG.phrase_create_new_view_group}" />
    <input type="hidden" class="sortable__add_group_handler" value="view_ns.create_new_group" />
    <div class="add_field_error hidden error"></div>
    <table cellspacing="1" cellpadding="3" width="100%">
    <tr>
      <td width="140">{$LANG.phrase_group_name}</td>
      <td><input type="text" class="new_group_name" /></td>
    </tr>
    </table>
  </div>
