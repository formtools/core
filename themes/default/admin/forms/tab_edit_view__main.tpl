  <table cellspacing="0" cellpadding="0" width="100%" class="margin_bottom_large">
  <tr>
    <td width="180" class="pad_left">{$LANG.phrase_view_name}</td>
    <td>
      <input type="text" maxlength="100" style="width: 300px;" name="view_name" value="{$view_info.view_name|escape}" />
    </td>
  </tr>
  <tr>
    <td class="pad_left">{$LANG.phrase_submissions_per_page}</td>
    <td><input type="text" size="3" name="num_submissions_per_page" value="{$view_info.num_submissions_per_page}" /></td>
  </tr>
  <tr>
    <td class="pad_left">{$LANG.phrase_default_sort_order}</td>
    <td>
      <table cellpadding="0" cellspacing="0">
      <tr>
        <td>
          {form_fields_dropdown name_id="default_sort_field" form_id=$form_id view_id=$view_id default=$view_info.default_sort_field}
        </td>
        <td>
          <select name="default_sort_field_order">
            <option value="asc" {if $view_info.default_sort_field_order == "asc"}selected{/if}>{$LANG.word_asc}</option>
            <option value="desc" {if $view_info.default_sort_field_order == "desc"}selected{/if}>{$LANG.word_desc}</option>
          </select>
        </td>
      </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td class="pad_left" width="180" valign="top">{$LANG.word_access}</td>
    <td>
      <table cellspacing="0" cellpadding="0">
      <tr>
        <td>
          <input type="radio" name="access_type" id="at1" value="admin" {if $view_info.access_type == 'admin'}checked{/if} />
          <label for="at1">{$LANG.phrase_admin_only}</label>
        </td>
      </tr>
      <tr>
        <td>
          <div style="float:right;margin-left: 20px">
            <input type="button" id="client_omit_list_button"
              value="Manage Client Omit List{if $view_info.access_type == 'public'} ({$num_clients_on_omit_list}){/if}"
              onclick="window.location='edit.php?page=public_view_omit_list&view_id={$view_id}'"
              {if $view_info.access_type != 'public'}disabled{/if} />
          </div>
          <input type="radio" name="access_type" id="at2" value="public" {if $view_info.access_type == 'public'}checked{/if} />
          <label for="at2">{$LANG.word_public} <span class="light_grey">{$LANG.phrase_all_clients_have_access}</span></label>
        </td>
      </tr>
      <tr>
        <td>
          <input type="radio" name="access_type" id="at3" value="private" {if $view_info.access_type == 'private'}checked{/if} />
          <label for="at3">{$LANG.word_private} <span class="light_grey">{$LANG.phrase_only_specific_clients_have_access}</span></label>
        </td>
      </tr>
      <tr>
        <td>
          <input type="radio" name="access_type" id="at4" value="hidden" {if $view_info.access_type == 'hidden'}checked{/if} />
          <label for="at4">{$LANG.word_hidden}</label>
        </td>
      </tr>
      </table>

      {if $form_info.access_type == "admin" || $form_info.access_type == "private"}
        <div class="hint">
          {if $form_info.access_type == "admin"}
            {$LANG.text_form_view_permission_info_admin}
          {elseif $form_info.access_type == "private"}
            {$LANG.text_form_view_permission_info_private}
          {/if}
          <a href="?page=main">{$LANG.phrase_edit_form_access_type_b}</a>
        </div>
      {/if}

      <div id="custom_clients" {if $view_info.access_type != 'private'}style="display:none"{/if} class="margin_top">
        <table cellpadding="1" cellspacing="0" class="list_table">
        <tr>
          <td class="medium_grey pad_left_small">{$LANG.phrase_available_clients}</td>
          <td></td>
          <td class="medium_grey pad_left_small">{$LANG.phrase_selected_clients}</td>
        </tr>
        <tr>
          <td>
            <select name="available_user_ids[]" multiple size="4" style="width: 180px">
              {$available_users}
            </select>
          </td>
          <td align="center" valign="center" width="100">
            <input type="button" value="{$LANG.word_add_uc_rightarrow}"
              onclick="ft.move_options(this.form['available_user_ids[]'], this.form['selected_user_ids[]']);" /><br />
            <input type="button" value="{$LANG.word_remove_uc_leftarrow}"
              onclick="ft.move_options(this.form['selected_user_ids[]'], this.form['available_user_ids[]']);" />
          </td>
          <td>
            <select id="selected_user_ids" name="selected_user_ids[]" multiple size="4" style="width: 180px">
              {$selected_users}
            </select>
          </td>
        </tr>
        </table>
      </div>

      <div class="margin_bottom_large"> </div>
    </td>
  </tr>
  <tr>
    <td class="pad_left" valign="top">{$LANG.phrase_may_delete_submissions}</td>
    <td valign="top">
      <input type="radio" name="may_delete_submissions" value="yes" id="cmds1" {if $view_info.may_delete_submissions == "yes"}checked{/if} />
      <label for="cmds1">{$LANG.word_yes}</label>
      <input type="radio" name="may_delete_submissions" value="no" id="cmds2" {if $view_info.may_delete_submissions == "no"}checked{/if} />
      <label for="cmds2">{$LANG.word_no}</label>
      <div class="hint margin_bottom">
        {$LANG.text_delete_view_submissions}
      </div>
    </td>
  </tr>
  <tr>
    <td class="pad_left">{$LANG.phrase_may_add_submissions}</td>
    <td valign="top">
      <input type="radio" name="may_add_submissions" value="yes" id="cmas1" {if $view_info.may_add_submissions == "yes"}checked{/if} />
      <label for="cmas1">{$LANG.word_yes}</label>
      <input type="radio" name="may_add_submissions" value="no" id="cmas2" {if $view_info.may_add_submissions == "no"}checked{/if} />
      <label for="cmas2">{$LANG.word_no}</label>
    </td>
  </tr>
  <tbody id="add_submission_default_values" {if $view_info.may_add_submissions == "no"}style="display: none"{/if}>
    <tr>
      <td width="180" valign="top" class="pad_left">{$LANG.phrase_default_values_new_submissions}</td>
      <td>
        <div class="hint margin_bottom">
          {$LANG.text_default_values_in_view}
        </div>
        <div id="no_new_submission_default_values" {if $new_view_submission_defaults|@count > 0}class="hidden"{/if}>
          <a href="">{$LANG.phrase_add_default_settings_rightarrow}</a>
        </div>

        <div id="new_submission_default_values" {if $new_view_submission_defaults|@count == 0}class="hidden"{/if}>
          <table cellspacing="1" cellpadding="0" class="list_table" width="100%" id="new_view_default_submission_vals">
          <tbody><tr>
            <th>{$LANG.word_field}</th>
            <th width="200">{$LANG.phrase_default_value}</th>
            <th class="del" width="18"></th>
          </tr>
          {* display all the existing filters for this View *}
          {foreach from=$new_view_submission_defaults item=filter name=row}
            {assign var=count value=$smarty.foreach.row.iteration}
            {assign var=field_id value=$filter.field_id}
            {assign var=curr_val value=$filter.default_value}
            <tr id="standard_row_{$count}">
              <td>
                <select name="new_submissions[]" class="new_submission_default_val_fields"
                  onchange="view_ns.change_standard_filter_field({$count})">
                  {foreach from=$form_fields item=field name=field_row}
                    {assign var=curr_field_id value=$field.field_id}
                    {if $field_id == $curr_field_id}
                      {assign var="selected" value="selected"}
                    {else}
                      {assign var="selected" value=""}
                    {/if}
                    <option value="{$curr_field_id}" {$selected}>{$field.field_title}</option>
                  {/foreach}
                </select>
              </td>
              <td>
                <input type="text" name="new_submissions_vals[]" class="new_submission_default_vals" value="{$curr_val|escape}" />
              </td>
              <td class="del"><a href="#" onclick="return view_ns.delete_new_view_submission_vals(this)"></a></td>
            </tr>
            {/foreach}
            </tbody>
          </table>

          <div>
            <a href="#" onclick="return view_ns.add_default_values_for_submission()">{$LANG.phrase_add_row}</a>
          </div>
        </div>

      </td>
    </tr>
  </tbody>
  </table>
