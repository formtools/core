  <div class="previous_page_icon">
    <a href="edit.php?page=database&form_id={$form_id}"><img src="{$images_url}/up.jpg" title="{$LANG.phrase_previous_page}" alt="{$LANG.phrase_previous_page}" border="0" /></a>
  </div>

  <div class="subtitle underline margin_top_large">
    {$LANG.phrase_add_fields|upper}
  </div>

  {include file="messages.tpl"}

  <form action="{$same_page}?page=add_fields" method="post" name="add_fields_form"
    onsubmit="return add_fields_ns.check_fields(this)">
    <input type="hidden" name="form_id" value="{$form_id}" />
    <input type="hidden" name="num_fields" value="{$num_fields}" />

    <table cellpadding="0" cellspacing="0" border="0" width="100%">
    <tr>
      <td valign="top">
        <table cellspacing="0" cellpadding="0">
        <tr>
          <td><input type="checkbox" name="auto_generate_col_names" id="auto_generate_col_names" tabindex="1" checked onchange="add_fields_ns.toggle_db_column_fields(this.checked)" /></td>
          <td><label for="auto_generate_col_names">{$LANG.phrase_auto_generate_db_col_names}</label></td>
        </tr>
        </table>
      </td>
      <td align="right">
        <div id="existing_column_name_info" style="display: none;">
          {$LANG.phrase_existing_col_names_c}
          {form_fields_dropdown name_id="existing_columns" display_column_names=true form_id=$form_id tabindex="2"}
        </div>
      </td>
    </tr>
    </table>

    <br />

    <table class="list_table" cellpadding="0" cellspacing="1" width="100%" id="add_fields_table">
      <tbody><tr>
        <th width="60">{$LANG.phrase_pass_on}</th>
        <th>{$LANG.phrase_form_field_name}</th>
        <th>{$LANG.phrase_display_text}</th>
        <th>{$LANG.phrase_field_size}</th>
        <th>{$LANG.phrase_data_type}</th>
        <th>{$LANG.phrase_database_column}</th>
        <th width="50" class="del">{$LANG.word_delete|upper}</th>
      </tr></tbody>
    </table>

    <br />

    <table cellspacing="0" cellpadding="0" width="100%">
    <tr>
      <td>
        {$LANG.word_add} <input type="text" name="num_rows" value="1" size="2" />
        <input type="button" value="{$LANG.word_row_sp}" onclick="add_fields_ns.add_fields(document.add_fields_form.num_rows.value)" />
      </td>
    </tr>
    </table>

    <p>
      <input type="submit" name="update_add_field" value="{$LANG.phrase_add_fields|upper}" />
    </p>

  </form>