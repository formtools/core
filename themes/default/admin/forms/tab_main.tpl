  <div class="subtitle underline margin_top_large">{$LANG.phrase_main_settings|upper}</div>

  {ft_include file="messages.tpl"}

  <form method="post" name="edit_form" id="edit_form" action="{$same_page}" onsubmit="return rsv.validate(this, rules)">
    <input type="hidden" name="form_id" id="form_id" value="{$form_id}" />
    <table class="list_table margin_bottom_large" width="100%" cellpadding="0" cellspacing="1">
    <tr>
      <td class="pad_left_small" width="200" valign="top">{$LANG.word_status}</td>
      <td>
        <input type="radio" name="active" id="active1" value="yes" {if $form_info.is_active == "yes"}checked{/if} />
          <label for="active1" class="light_green">{$LANG.word_online} {$LANG.phrase_accepting_submissions}</label><br />
        <input type="radio" name="active" id="active2" value="no" {if $form_info.is_active == "no"}checked{/if} />
          <label for="active2" class="orange">{$LANG.word_offline}</label>
      </td>
    </tr>
    <tr>
      <td class="pad_left_small">{$LANG.phrase_form_name}</td>
      <td><input type="text" name="form_name" value="{$form_info.form_name|escape}" style="width: 99%" /></td>
    </tr>
    <tr>
      <td valign="top" class="pad_left_small">{$LANG.phrase_form_type}</td>
      <td>
        <select name="form_type" id="form_type">
          <option value="external" {if $form_info.form_type == "external"}selected="selected"{/if}>{$LANG.phrase_external_your_own_form}</option>
          <option value="internal" {if $form_info.form_type == "internal"}selected="selected"{/if}>{$LANG.word_internal}</option>
          {template_hook location="admin_edit_form_main_tab_form_type_dropdown"}
        </select>
      </td>
    </tr>
    </table>

    <div id="form_settings__external" class="form_type_specific_options" {if $form_info.form_type != "external"}style="display:none"{/if}>
      <div class="subtitle underline margin_bottom_large margin_top_large">{$LANG.phrase_external_form_info|upper}</div>

      <table class="list_table margin_bottom_large" width="100%" cellpadding="0" cellspacing="1">
      <tr>
        <td class="pad_left_small" width="200"><label for="submission_type">{$LANG.phrase_submission_type}</label></td>
        <td>
          <select name="submission_type" id="submission_type">
            <option value="direct" {if $form_info.submission_type == "direct"}selected{/if}>{$LANG.word_direct}</option>
            <option value="code" {if $form_info.submission_type == "code"}selected{/if}>{$LANG.word_code} (API)</option>
          </select>
        </td>
      </tr>
      <tbody id="multi_page_form_row" {if $form_info.submission_type == 'direct'}style="display:none"{/if}>
        <tr>
          <td class="pad_left_small"><label for="is_multi_page_form">{$LANG.phrase_is_multi_page_form_q}</label></td>
          <td>
            <input type="radio" class="is_multi_page_form" name="is_multi_page_form" id="impf1" value="yes"
              {if $form_info.is_multi_page_form == "yes"}checked{/if} />
              <label for="impf1">{$LANG.word_yes}</label>
            <input type="radio" class="is_multi_page_form" name="is_multi_page_form" id="impf2" value="no"
              {if $form_info.is_multi_page_form == "no"}checked{/if} />
              <label for="impf2">{$LANG.word_no}</label>
          </td>
        </tr>
      </tbody>
      <tr>
        <td valign="top" class="pad_left_small">
          <span id="form_label_single" {if $form_info.is_multi_page_form == "yes"}style="display:none"{/if}>{$LANG.phrase_form_url}</span>
          <span id="form_label_multiple" {if $form_info.is_multi_page_form == "no"}style="display:none"{/if}>{$LANG.phrase_form_urls}</span>
        </td>
        <td>
          <div id="form_url_single" {if $form_info.is_multi_page_form == "yes"}style="display:none"{/if}>
            <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
              <td><input type="text" name="form_url" id="form_url" value="{$form_info.form_url}" style="width: 98%" /></td>
              <td width="60"><input type="button" class="check_url" id="check_url__form_url" value="{$LANG.phrase_check_url|escape}" /></td>
            </tr>
            </table>
          </div>
          <div id="form_url_multiple" {if $form_info.is_multi_page_form == "no" || $form_info.submission_info == "direct"}style="display:none"{/if}>
            <div class="sortable multi_page_form_list" id="{$sortable_id}">
              <ul class="header_row">
                <li class="col1">{$LANG.word_page}</li>
                <li class="col2">{$LANG.phrase_form_url}</li>
                <li class="col3"></li>
                <li class="col4 colN del"></li>
              </ul>
              <div class="clear"></div>
              <ul class="rows">
                {assign var=previous_item value=""}
                {foreach from=$form_info.multi_page_form_urls item=i name=row}
                  {assign var=count value=$smarty.foreach.row.iteration}
                  <li class="sortable_row{if $smarty.foreach.row.last} rowN{/if}">
                    <div class="row_content">
                      <div class="row_group{if $smarty.foreach.row.last} rowN{/if}">
                        <input type="hidden" class="sr_order" value="{$count}" />
                        <ul>
                          <li class="col1 sort_col">{$count}</li>
                          <li class="col2"><input type="text" name="multi_page_urls[]" id="mp_url_{$count}" value="{$i.form_url|escape}" /></li>
                          <li class="col3"><input type="button" class="check_url" id="check_url__mp_url_{$count}" value="{$LANG.phrase_check_url|escape}" /></li>
                          <li class="col4 colN del"></li>
                        </ul>
                        <div class="clear"></div>
                      </div>
                    </div>
                    <div class="clear"></div>
                  </li>
                {/foreach}
                {if $form_info.multi_page_form_urls|@count == 0}
                  <li class="sortable_row">
                    <div class="row_content">
                      <div class="row_group rowN">
                        <input type="hidden" class="sr_order" value="1" />
                        <ul>
                          <li class="col1 sort_col">1</li>
                          <li class="col2"><input type="text" name="multi_page_urls[]" id="mp_url_0" /></li>
                          <li class="col3"><input type="button" class="check_url" id="check_url__mp_url_0" value="{$LANG.phrase_check_url|escape}" /></li>
                          <li class="col4 colN del"></li>
                        </ul>
                        <div class="clear"></div>
                      </div>
                    </div>
                    <div class="clear"></div>
                  </li>
                {/if}
              </ul>
            </div>
            <div class="clear"></div>
            <div>
              <a href="#" onclick="return mf_ns.add_multi_page_form_page()">{$LANG.phrase_add_row}</a>
            </div>
          </div>
        </td>
      </tr>
      <tbody id="redirect_url_row" {if $form_info.submission_type == "code"}style="display:none"{/if}>
        <tr>
          <td valign="top" width="200" class="pad_left_small">{$LANG.phrase_redirect_url}</td>
          <td>
            <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
              <td><input type="text" name="redirect_url" id="redirect_url" value="{$form_info.redirect_url}" style="width: 98%" /></td>
              <td width="60"><input type="button" class="check_url" id="check_url__redirect_url" value="{$LANG.phrase_check_url|escape}" /></td>
            </tr>
            </table>
          </td>
        </tr>
      </tbody>
      </table>
    </div>

    {template_hook location="admin_edit_form_main_tab_after_main_settings"}

    <div class="subtitle underline margin_bottom_large margin_top_large">{$LANG.phrase_permissions_other_settings|upper}</div>

    <table class="list_table margin_bottom_large" width="100%" cellpadding="0" cellspacing="1">
    <tr>
      <td class="pad_left_small" valign="top" width="200">{$LANG.word_access}</td>
      <td>
        <table cellspacing="1" cellpadding="0" >
        <tr>
          <td>
            <input type="radio" name="access_type" id="at1" value="admin" {if $form_info.access_type == 'admin'}checked{/if} />
              <label for="at1">{$LANG.phrase_admin_only}</label>
          </td>
        </tr>
        <tr>
          <td>
            <div style="float:right;margin-left: 20px">
              <input type="button" id="client_omit_list_button"
                value="{$LANG.phrase_manage_client_omit_list}{if $form_info.access_type == 'public'} ({$num_clients_on_omit_list}){/if}"
                onclick="window.location='edit.php?page=public_form_omit_list&form_id={$form_id}'"
                {if $form_info.access_type != 'public'}disabled{/if} /><br />
            </div>
            <input type="radio" name="access_type" id="at2" value="public" {if $form_info.access_type == 'public'}checked{/if} />
              <label for="at2">{$LANG.word_public} <span class="light_grey">{$LANG.phrase_all_clients_have_access}</span></label>
          </td>
        </tr>
        <tr>
          <td>
            <input type="radio" name="access_type" id="at3" value="private" {if $form_info.access_type == 'private'}checked{/if} />
              <label for="at3">{$LANG.word_private} <span class="light_grey">{$LANG.phrase_only_specific_clients_have_access}</span></label>
          </td>
        </tr>
        </table>

        <div id="custom_clients" {if $form_info.access_type != 'private'}style="display:none"{/if} class="margin_top">
          <table cellpadding="0" cellspacing="0" class="subpanel">
          <tr>
            <td class="medium_grey">{$LANG.phrase_available_clients}</td>
            <td></td>
            <td class="medium_grey">{$LANG.phrase_selected_clients}</td>
          </tr>
          <tr>
            <td>
              {clients_dropdown name_id="available_client_ids[]" multiple="true" multiple_action="hide"
                clients=$selected_client_ids size="4" style="width: 202px"}
            </td>
            <td align="center" valign="middle" width="100">
              <input type="button" value="{$LANG.word_add_uc_rightarrow}"
                onclick="ft.move_options(this.form['available_client_ids[]'], this.form['selected_client_ids[]']);" /><br />
              <input type="button" value="{$LANG.word_remove_uc_leftarrow}"
                onclick="ft.move_options(this.form['selected_client_ids[]'], this.form['available_client_ids[]']);" />
            </td>
            <td>
              {clients_dropdown name_id="selected_client_ids[]" multiple="true" multiple_action="show"
                clients=$selected_client_ids size="4" style="width: 202px"}
            </td>
          </tr>
          </table>
        </div>

      </td>
    </tr>
    <tr>
      <td class="pad_left_small">{$LANG.phrase_delete_uploaded_fields_with_submission}</td>
      <td>
        <input type="radio" name="auto_delete_submission_files" id="auto_delete_submission_files1" value="yes" {if $form_info.auto_delete_submission_files == "yes"}checked{/if} />
          <label for="auto_delete_submission_files1">{$LANG.word_yes}</label>
        <input type="radio" name="auto_delete_submission_files" id="auto_delete_submission_files2" value="no" {if $form_info.auto_delete_submission_files == "no"}checked{/if} />
          <label for="auto_delete_submission_files2">{$LANG.word_no}</label>
      </td>
    </tr>
    <tr>
      <td class="pad_left_small">{$LANG.phrase_strip_tags_in_submissions}</td>
      <td>
        <input type="radio" name="submission_strip_tags" id="sst1" value="yes" {if $form_info.submission_strip_tags == "yes"}checked{/if} />
          <label for="sst1">{$LANG.word_yes}</label>
        <input type="radio" name="submission_strip_tags" id="sst2" value="no" {if $form_info.submission_strip_tags == "no"}checked{/if} />
          <label for="sst2">{$LANG.word_no}</label>
      </td>
    </tr>
    <tr>
      <td class="pad_left_small" valign="top">{$LANG.phrase_edit_submission_label}</td>
      <td><input type="text" name="edit_submission_page_label" value="{$form_info.edit_submission_page_label|escape}"
        class="lang_placeholder_field lang_field_full" /></td>
    </tr>
    <tr>
      <td class="pad_left_small" valign="top">{$LANG.phrase_add_submission_button}</td>
      <td>
        <input type="text" name="add_submission_button_label" value="{$form_info.add_submission_button_label|escape}"
          class="lang_placeholder_field lang_field_full" />
        <div class="medium_grey">{$LANG.text_add_submission_button}</div>
      </td>
    </tr>
    </table>

    <p>
      <input type="submit" name="update_main" value="{$LANG.word_update}" />
      {template_hook location="admin_edit_form_main_tab_button_row"}
    </p>

   </form>
