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
      <input type="hidden" name="num_rows" id="num_rows" value="{$menu.menu_items|@count}" />

      <table id="menu_table" class="list_table" cellspacing="1" cellpadding="1" width="100%">
      <tbody>
      <tr>
        <th width="40">{$LANG.word_order}</th>
        <th>{$LANG.word_page}</th>
        <th width="130">{$LANG.phrase_display_text}</th>
        <th>{$LANG.word_options}</th>
        <th width="75">{$LANG.word_submenu}</th>
        <th class="del" width="70">{$LANG.word_remove|upper}</th>
      </tr>
      {foreach from=$menu.menu_items key=k item=i}
        <tr id="row_{$i.list_order}">
          <td align="center"><input type="text" style="width:30px" name="menu_row_{$i.list_order}_order" id="menu_row_{$i.list_order}_order" value="{$i.list_order}" /></td>
          <td width="120">{pages_dropdown menu_type="admin" default=$i.page_identifier name_id="page_identifier_`$i.list_order`" onchange="mm.change_page(`$i.list_order`, this.value)" is_building_menu=true}</td>
          <td width="120"><input type="text" name="display_text_{$i.list_order}" id="display_text_{$i.list_order}" value="{$i.display_text|escape}" style="width:120px" /></td>
          <td class="nowrap"><div id="row_{$i.list_order}_options" class="nowrap pad_left_small">
            {if $i.page_identifier == "custom_url"}
              URL:&nbsp;<input type="text" name="custom_options_{$i.list_order}" id="custom_options_{$i.list_order}" value="{$i.custom_options|escape}" style="width:160px" />
            {elseif $i.page_identifier == "form_submissions" ||
                    $i.page_identifier == "edit_form_main" ||
                    $i.page_identifier == "edit_form_fields" ||
                    $i.page_identifier == "edit_form_views" ||
                    $i.page_identifier == "edit_form_emails" ||
                    $i.page_identifier == "edit_form_database" ||
                    $i.page_identifier == "edit_form_add_fields"}
              {$LANG.word_form_c}&nbsp;{forms_dropdown name_id="custom_options_`$i.list_order`" style="width:155px" default=$i.custom_options}
            {elseif $i.page_identifier == "edit_client"}
              {$LANG.word_client_c}&nbsp;{clients_dropdown name_id="custom_options_`$i.list_order`" style="width:150px" default=$i.custom_options}
            {else}
              <span class="medium_grey">{$LANG.word_na}</span>
            {/if}
            </div></td>
          <td align="center"><input type="checkbox" name="submenu_{$i.list_order}" {if $i.is_submenu == "yes"}checked{/if} /></td>
          <td align="center" class="del"><a href="#" onclick="return mm.remove_menu_item_row({$i.list_order})">{$LANG.word_remove|upper}</a></td>
        </tr>
      {/foreach}
      </tbody></table>

      <script type="text/javascript">
      mm.num_rows = {$menu.menu_items|@count};
      </script>

      <p>
        <a href="#" onclick="return mm.add_menu_item_row()">{$LANG.phrase_add_row}</a>
      </p>

      <div id="menu_options" style="display:none">
        {pages_dropdown menu_type="admin" name_id="page_identifier_%%X%%" is_building_menu=true}
      </div>

      <div id="form_dropdown_template" style="display:none">
        {forms_dropdown name_id="custom_options_%%X%%" style="width:155px"}
      </div>

      <div id="client_dropdown_template" style="display:none">
        {clients_dropdown name_id="custom_options_%%X%%" style="width:150px"}
      </div>

      <p>
        <input type="submit" name="update_admin_menu" value="{$LANG.word_update}" />
      </p>
    </form>
