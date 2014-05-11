    <div class="previous_page_icon">
      <a href="index.php?page=menus"><img src="{$images_url}/up.jpg" title="{$LANG.phrase_previous_page}" alt="{$LANG.phrase_previous_page}" border="0" /></a>
    </div>

    <div class="subtitle underline margin_top_large">{$LANG.phrase_edit_admin_menu|upper}</div>

    {ft_include file='messages.tpl'}

    <div class="pad_bottom_large">
      {$LANG.text_edit_admin_menu_page}
    </div>

    {template_hook location="admin_settings_admin_menu_top"}

    <form action="{$same_page}" method="post" onsubmit="return mm.update_admin_menu_submit(this)">
      <input type="hidden" name="page" value="edit_admin_menu" />
      <input type="hidden" name="menu_id" value="{$menu.menu_id}" />

      <div class="sortable groupable edit_menu" id="{$sortable_id}">
        <ul class="header_row">
          <li class="col1">{$LANG.word_order}</li>
          <li class="col2">{$LANG.word_page}</li>
          <li class="col3">{$LANG.phrase_display_text}</li>
          <li class="col4">{$LANG.word_options}</li>
          <li class="col5">{$LANG.word_submenu}</li>
          <li class="col6 colN del"></li>
        </ul>
        <div class="clear"></div>
        <ul class="rows check_areas" id="rows">
        {assign var=previous_item value=""}
        {foreach from=$menu.menu_items key=k item=i name=admin_menu_items}
          {if $i.is_new_sort_group == "yes"}
            {if $previous_item != ""}
              </div>
              <div class="clear"></div>
            </li>
            {/if}
            <li class="sortable_row">
            {assign var=next_item_is_new_sort_group value=$menu.menu_items[$smarty.foreach.admin_menu_items.iteration].is_new_sort_group}
            <div class="row_content{if $next_item_is_new_sort_group == 'no'} grouped_row{/if}">
          {/if}

          {assign var=previous_item value=$i}

            <div class="row_group{if $smarty.foreach.admin_menu_items.last} rowN{/if}">
              <input type="hidden" class="sr_order" value="{$i.list_order}" />
              <ul>
                <li class="col1 sort_col">{$i.list_order}</li>
                <li class="col2">
                  {pages_dropdown menu_type="admin" default=$i.page_identifier name_id="page_identifier_`$i.list_order`"
                    onchange="mm.change_page(`$i.list_order`, this.value)" onkeyup="mm.change_page(`$i.list_order`, this.value)"
                    is_building_menu=true class="page_type"}
                </li>
                <li class="col3"><input type="text" name="display_text_{$i.list_order}" id="display_text_{$i.list_order}" value="{$i.display_text|escape}" /></li>
                <li class="col4" id="row_{$i.list_order}_options">
                  {if $i.page_identifier == "custom_url"}
                    URL:&nbsp;<input type="text" name="custom_options_{$i.list_order}" id="custom_options_{$i.list_order}" value="{$i.custom_options|escape}" style="width:155px" />
                  {elseif $i.page_identifier == "form_submissions" ||
                          $i.page_identifier == "edit_form" ||
                          $i.page_identifier == "edit_form_main" ||
                          $i.page_identifier == "edit_form_fields" ||
                          $i.page_identifier == "edit_form_views" ||
                          $i.page_identifier == "edit_form_emails"}
                    {forms_dropdown name_id="custom_options_`$i.list_order`" default=$i.custom_options
                      include_blank_option=true blank_option_is_optgroup=true}
                  {elseif $i.page_identifier == "edit_client"}
                    {clients_dropdown name_id="custom_options_`$i.list_order`" default=$i.custom_options
                      include_blank_option=true blank_option_is_optgroup=true}
                  {else}
                    <span class="medium_grey">{$LANG.word_na}</span>
                  {/if}
                </li>
                <li class="col5 check_area"><input type="checkbox" name="submenu_{$i.list_order}" {if $i.is_submenu == "yes"}checked="checked"{/if} /></li>
                <li class="col6 colN del"></li>
              </ul>
              <div class="clear"></div>
            </div>

          {if $smarty.foreach.admin_menu_items.last}
            </div>
            <div class="clear"></div>
          </li>
          {/if}

        {/foreach}
        </ul>
      </div>

      <script>
      mm.num_rows = {$menu.menu_items|@count};
      </script>

      <p>
        <a href="#" onclick="return mm.add_menu_item_row()">{$LANG.phrase_add_row}</a>
      </p>

      <div id="menu_options" class="hidden">
        {pages_dropdown menu_type="admin" name_id="page_identifier_%%X%%" is_building_menu=true class="page_type"}
      </div>

      <div id="form_dropdown_template" class="hidden">
        {forms_dropdown name_id="custom_options_%%X%%" include_blank_option=true blank_option_is_optgroup=true}
      </div>

      <div id="client_dropdown_template" class="hidden">
        {clients_dropdown name_id="custom_options_%%X%%" include_blank_option=true blank_option_is_optgroup=true}
      </div>

      <p>
        <input type="submit" name="update_admin_menu" value="{$LANG.word_update}" />
      </p>
    </form>
