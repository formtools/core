{ft_include file='header.tpl'}

  <table cellpadding="0" cellspacing="0" class="margin_bottom_large">
  <tr>
    <td width="45"><a href="../"><img src="{$images_url}/icon_forms.gif" border="0" width="34" height="34" /></a></td>
    <td class="title"><a href="../">{$LANG.word_forms|upper}</a>: {$LANG.phrase_add_form|upper}</td>
  </tr>
  </table>

  <table cellpadding="0" cellspacing="0" width="100%" class="add_form_nav margin_bottom_large">
  <tr>
    <td class="selected"><a href="step1.php{$form_query_str}">{$LANG.word_checklist}</a></td>
    <td class="selected">{$LANG.phrase_form_info}</td>
    <td class="unselected">{$LANG.phrase_test_submission}</td>
    <td class="unselected">{$LANG.phrase_database_setup}</td>
    <td class="unselected">{$LANG.phrase_field_types}</td>
    <td class="unselected">{$LANG.phrase_finalize_form}</td>
  </tr>
  </table>

  <div>
    <div class="subtitle underline" style="position:relative">
      {$LANG.phrase_form_info_2|upper}
    </div>

    {ft_include file='messages.tpl'}

    <form method="post" id="add_form" name="add_form" action="{$same_page}" onsubmit="return rsv.validate(this, rules)">
      {$page_values.hidden_fields}
      {foreach from=$page_values.multi_page_form_urls item=url name=r}
        {assign var=curr_page value=$smarty.foreach.r.iteration}
        <input type="hidden" id="form_url_{$curr_page+1}_verified" name="form_url_{$curr_page+1}_verified" value="yes" />
      {/foreach}

      <table width="100%" class="list_table" cellpadding="1" cellspacing="1">
      <tr>
        <td class="red" align="center" width="15">*</td>
        <td class="pad_left_small" width="120">{$LANG.phrase_form_name}</td>
        <td><input type="text" name="form_name" value="{$page_values.form_name|escape}" style="width: 99%" /></td>
      </tr>
      <tr>
        <td valign="top" class="red" align="center">*</td>
        <td valign="top" class="pad_left_small">{$LANG.phrase_form_url_sp}</td>
        <td>

          <table width="100%" cellpadding="0" cellspacing="0">
          <tr>
            <td>
              <input type="hidden" id="original_form_url" value="{$page_values.form_url|escape}" />
              <input type="text" name="form_url" id="form_url" value="{$page_values.form_url}" style="width: 98%"
                onkeyup="mf_ns.unverify_url_field(this.value, $('original_form_url').value, 1)" />
            </td>
            <td width="60" align="center">
              {if $page_values.form_url != ""}
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

          <input type="checkbox" name="is_multi_page_form" id="is_multi_page_form" onchange="mf_ns.toggle_multi_page_form_fields(this.checked)"
            {if $page_values.is_multi_page_form == "yes"}checked{/if} />
            <label for="is_multi_page_form">This a multi-page form</label>

          <input type="hidden" name="num_pages_in_multi_page_form" id="num_pages_in_multi_page_form" value="{$num_pages_in_multi_page_form}" />

          <div id="multi_page_form_urls" {if $page_values.is_multi_page_form != "yes"}style="display:none"{/if} class="margin_bottom_large">
	          <table width="100%" cellpadding="0" cellspacing="0" id="multi_page_form_url_table">
	          <tbody>
	          {foreach from=$page_values.multi_page_form_urls item=url name=r}
	            {assign var=curr_page value=$smarty.foreach.r.iteration}
		          <tr>
		            <td width="70" class="bold">{$LANG.word_page} {$curr_page+1}</tdm>
		            <td>
                  <input type="hidden" id="original_form_url_{$curr_page+1}" value="{$url.form_url}" />
		              <input type="text" name="form_url_{$curr_page+1}" id="form_url_{$curr_page+1}" value="{$url.form_url}" style="width: 98%"
		                onkeyup="mf_ns.unverify_url_field(this.value, $('original_form_url_{$curr_page+1}').value, {$curr_page+1})" />
		            </td>
		            <td width="60" align="right">
		              <input type="button" class="green" id="form_url_{$curr_page+1}_button"
		                onclick="ft.verify_url('form_url_{$curr_page+1}', {$curr_page+1})" value="{$LANG.word_verified|escape}" />
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

          <div class="medium_grey">
            {$LANG.text_add_form_step_2_text_1}
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
              <input type="hidden" id="original_redirect_url" value="{$page_values.redirect_url}" />
              <input type="text" name="redirect_url" id="redirect_url" value="{$page_values.redirect_url}" style="width: 99%;"
                onkeyup="mf_ns.unverify_url_field(this.value, $('original_redirect_url').value, 'redirect')" />
            </td>
            <td width="60" align="center">
              {if $page_values.redirect_url != ""}
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
          <div class="medium_grey">
            {$LANG.text_add_form_step_2_text_2}
          </div>
        </td>
      </tr>
      <tr>
        <td class="red" align="center" valign="top">*</td>
        <td class="pad_left_small" valign="top" width="160">{$LANG.phrase_who_can_access}</td>
        <td>

          <table cellspacing="1" cellpadding="0" >
          <tr>
            <td>
              <input type="radio" name="access_type" id="at1" value="admin" {if $page_values.access_type == 'admin'}checked{/if}
                onclick="mf_ns.toggle_access_type(this.value)" />
                <label for="at1">{$LANG.phrase_admin_only}</label>
            </td>
          </tr>
          <tr>
            <td>
              <input type="radio" name="access_type" id="at2" value="public" {if $page_values.access_type == 'public'}checked{/if}
                onclick="mf_ns.toggle_access_type(this.value)" />
                <label for="at2">{$LANG.word_public} <span class="light_grey">{$LANG.phrase_all_clients_have_access}</span></label>
            </td>
          </tr>
          <tr>
            <td>
              <input type="radio" name="access_type" id="at3" value="private" {if $page_values.access_type == 'private'}checked{/if}
                onclick="mf_ns.toggle_access_type(this.value)" />
                <label for="at3">{$LANG.word_private} <span class="light_grey">{$LANG.phrase_only_specific_clients_have_access}</span></label>
            </td>
          </tr>
          </table>

          <div id="custom_clients" {if $page_values.access_type != 'private'}style="display:none"{/if} class="margin_top">
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
      </table>

      <p>
        {$LANG.text_form_contains_file_fields}
        <input type="radio" name="uploading_files" id="uploading_files1" value="yes" {if $SESSION.uploading_files == "yes"}checked{/if} />
          <label for="uploading_files1">{$LANG.word_yes}</label>
        <input type="radio" name="uploading_files" id="uploading_files2" value="no" {if $SESSION.uploading_files == "no"}checked{/if} />
          <label for="uploading_files2">{$LANG.word_no}</label>
      </p>

      <p>
        <input type="submit" name="submit" class="next_step" value="{$LANG.word_next_step_rightarrow}" />
      </p>

    </form>

  </div>

{ft_include file='footer.tpl'}