  <div class="subtitle underline margin_top_large">{$LANG.phrase_main_settings|upper}</div>

  {ft_include file="messages.tpl"}

  <form method="post" name="add_form" id="add_form" action="{$same_page}" onsubmit="return rsv.validate(this, rules)">
    <input type="hidden" name="form_id" value="{$form_id}" />

    <table class="list_table" width="100%" cellpadding="0" cellspacing="1">
    <tr>
      <td class="red" width="15" valign="top" align="center">*</td>
      <td class="pad_left_small" valign="top">{$LANG.word_status}</td>
      <td>
        <input type="radio" name="active" id="active1" value="yes" {if $form_info.is_active == "yes"}checked{/if} />
          <label for="active1" class="light_green">{$LANG.word_online} {$LANG.phrase_accepting_submissions}</label><br />
        <input type="radio" name="active" id="active2" value="no" {if $form_info.is_active == "no"}checked{/if} />
          <label for="active2" class="orange">{$LANG.word_offline}</label>
      </td>
    </tr>
    <tr>
      <td class="red" align="center">*</td>
      <td class="pad_left_small">{$LANG.phrase_form_name}</td>
      <td><input type="text" name="form_name" value="{$form_info.form_name}" style="width: 99%" /></td>
    </tr>
    <tr>
      <td valign="top" class="red" align="center">*</td>
      <td valign="top" class="pad_left_small">{$LANG.phrase_form_url_sp}</td>
      <td>

        <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <td>
            <input type="hidden" id="original_form_url" value="{$form_info.form_url}" />
            <input type="text" name="form_url" id="form_url" value="{$form_info.form_url}" style="width: 98%"
              onkeyup="mf_ns.unverify_url_field(this.value, $('original_form_url').value, 1)" />
          </td>
          <td width="60" align="center">
            {if $form_info.form_url != ""}
              <input type="button" id="form_url_1_button" class="green" onclick="ft.verify_url($('form_url'), 1)"
                value="{$LANG.word_verified|escape}" />
              <input type="hidden" id="form_url_1_verified" name="form_url_verified" value="yes" />
            {else}
              <input type="button" id="form_url_1_button" class="light_grey" onclick="ft.verify_url($('form_url'), 1)"
                value="{$LANG.phrase_verify_url|escape}" />
              <input type="hidden" id="form_url_1_verified" name="form_url_verified" value="no" />
            {/if}
          </td>
        </tr>
        </table>

        <input type="checkbox" name="is_multi_page_form" id="is_multi_page_form" onclick="mf_ns.toggle_multi_page_form_fields(this.checked)"
          {if $form_info.is_multi_page_form == "yes"}checked{/if} />
          <label for="is_multi_page_form">This a multi-page form</label>

        <input type="hidden" name="num_pages_in_multi_page_form" id="num_pages_in_multi_page_form" value="{$num_pages_in_multi_page_form}" />

        <div id="multi_page_form_urls" {if $form_info.is_multi_page_form != "yes"}style="display:none"{/if} class="margin_bottom_large">
          <table width="100%" cellpadding="0" cellspacing="0" id="multi_page_form_url_table">
          <tbody>
          {foreach from=$form_info.multi_page_form_urls item=url name=r}
            {assign var=curr_page value=$smarty.foreach.r.iteration}
          <tr>
            <td width="70" class="bold">{$LANG.word_page} {$curr_page+1}</td>
            <td>
              <input type="hidden" id="original_form_url_{$curr_page+1}" value="{$url.form_url}" />
              <input type="text" name="form_url_{$curr_page+1}" id="form_url_{$curr_page+1}" value="{$url.form_url}" style="width: 98%"
                onkeyup="mf_ns.unverify_url_field(this.value, $('original_form_url_{$curr_page+1}').value, {$curr_page+1})" />
            </td>
            <td width="60" align="right">
              <input type="button" class="green" id="form_url_{$curr_page+1}_button"
                onclick="ft.verify_url('form_url_{$curr_page+1}', {$curr_page+1})" value="{$LANG.word_verified|escape}" />
              <input type="hidden" id="form_url_{$curr_page+1}_verified" name="form_url_verified" value="yes" />
            </td>
          </tr>
          {/foreach}
          </tbody>
          </table>

          <table width="100%" cellpadding="0" cellspacing="0" class="margin_top_small">
          <tr>
            <td width="70"> </td>
            <td><input type="button" value="{$LANG.phrase_add_row}" onclick="mf_ns.add_multi_page_form_page(this.form)" /></td>
          </tr>
          </table>

        </div>
      </td>
    </tr>
    <tr>
      <td valign="top" class="red" align="center"> </td>
      <td valign="top" class="pad_left_small">{$LANG.phrase_redirect_url}</td>
      <td>
          <table width="100%" cellpadding="0" cellspacing="0">
          <tr>
            <td>
              <input type="hidden" id="original_redirect_url" value="{$form_info.redirect_url}" />
              <input type="text" name="redirect_url" id="redirect_url" value="{$form_info.redirect_url}" style="width: 99%;"
                onkeyup="mf_ns.unverify_url_field(this.value, $('original_redirect_url').value, 'redirect')" />
            </td>
            <td width="60" align="center">
              {if $form_info.redirect_url != ""}
              <input type="button" id="form_url_redirect_button" class="green" value="{$LANG.word_verified}"
                onclick="ft.verify_url('redirect_url', 'redirect')" />
              <input type="hidden" id="form_url_redirect_verified" value="yes" />
            {else}
              <input type="button" id="form_url_redirect_button" class="light_grey" value="{$LANG.phrase_verify_url}"
                onclick="ft.verify_url('redirect_url', 'redirect')" />
              <input type="hidden" id="form_url_redirect_verified" value="no" />
            {/if}
            </td>
          </tr>
          </table>
        </td>
      </tr>
    <tr>
      <td class="red" valign="top" align="center">*</td>
      <td class="pad_left_small" valign="top" width="160">{$LANG.word_access}</td>
      <td>

        <table cellspacing="1" cellpadding="0" >
        <tr>
          <td>
            <input type="radio" name="access_type" id="at1" value="admin" {if $form_info.access_type == 'admin'}checked{/if}
              onclick="mf_ns.toggle_access_type(this.value)" />
              <label for="at1">{$LANG.phrase_admin_only}</label>
          </td>
        </tr>
        <tr>
          <td>
            <div style="float:right;margin-left: 20px">
              <input type="button" id="client_omit_list_button"
                value="Manage Client Omit List{if $form_info.access_type == 'public'} ({$num_clients_on_omit_list}){/if}"
                onclick="window.location='edit.php?page=public_form_omit_list&form_id={$form_id}'"
                {if $form_info.access_type != 'public'}disabled{/if} /><br />
            </div>
            <input type="radio" name="access_type" id="at2" value="public" {if $form_info.access_type == 'public'}checked{/if}
              onclick="mf_ns.toggle_access_type(this.value)" />
              <label for="at2">{$LANG.word_public} <span class="light_grey">{$LANG.phrase_all_clients_have_access}</span></label>
          </td>
        </tr>
        <tr>
          <td>
            <input type="radio" name="access_type" id="at3" value="private" {if $form_info.access_type == 'private'}checked{/if}
              onclick="mf_ns.toggle_access_type(this.value)" />
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
                clients=$selected_client_ids size="4" style="width: 140px"}
            </td>
            <td align="center" valign="middle" width="100">
              <input type="button" value="{$LANG.word_add_uc_rightarrow}"
                onclick="ft.move_options(this.form['available_client_ids[]'], this.form['selected_client_ids[]']);" /><br />
              <input type="button" value="{$LANG.word_remove_uc_leftarrow}"
                onclick="ft.move_options(this.form['selected_client_ids[]'], this.form['available_client_ids[]']);" />
            </td>
            <td>
              {clients_dropdown name_id="selected_client_ids[]" multiple="true" multiple_action="show"
                clients=$selected_client_ids size="4" style="width: 140px"}
            </td>
          </tr>
          </table>
        </div>

      </td>
    </tr>
    <tr>
      <td class="red" valign="top" align="center">*</td>
      <td class="pad_left_small" valign="top" width="160">Edit Submission Page Label</td>
      <td><input type="text" name="edit_submission_page_label" value="{$form_info.edit_submission_page_label|escape}" style="width: 99%" /></td>
    </tr>
    <tr>
      <td class="red" align="center">*</td>
      <td class="pad_left_small">{$LANG.phrase_delete_uploaded_fields_with_submission}</td>
      <td>
        <input type="radio" name="auto_delete_submission_files" id="auto_delete_submission_files1" value="yes" {if $form_info.auto_delete_submission_files == "yes"}checked{/if} />
          <label for="auto_delete_submission_files1">{$LANG.word_yes}</label>
        <input type="radio" name="auto_delete_submission_files" id="auto_delete_submission_files2" value="no" {if $form_info.auto_delete_submission_files == "no"}checked{/if} />
          <label for="auto_delete_submission_files2">{$LANG.word_no}</label>
      </td>
    </tr>
    <tr>
      <td class="red" align="center">*</td>
      <td class="pad_left_small">{$LANG.phrase_strip_tags_in_submissions}</td>
      <td>
        <input type="radio" name="submission_strip_tags" id="sst1" value="yes" {if $form_info.submission_strip_tags == "yes"}checked{/if} />
          <label for="sst1">{$LANG.word_yes}</label>
        <input type="radio" name="submission_strip_tags" id="sst2" value="no" {if $form_info.submission_strip_tags == "no"}checked{/if} />
          <label for="sst2">{$LANG.word_no}</label>
      </td>
    </tr>
    </table>

    <p>
      <input type="submit" name="update_main" value="{$LANG.word_update|upper}" />
    </p>

   </form>
