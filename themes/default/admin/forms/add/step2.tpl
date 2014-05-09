{ft_include file='header.tpl'}

  <table cellpadding="0" cellspacing="0" class="margin_bottom_large">
  <tr>
    <td width="45"><a href="../"><img src="{$images_url}/icon_forms.gif" border="0" width="34" height="34" /></a></td>
    <td class="title">
      <a href="../">{$LANG.word_forms}</a> <span class="joiner">&raquo;</span>
      <a href="./">{$LANG.phrase_add_form}</a> <span class="joiner">&raquo;</span>
      {$LANG.phrase_external_form}
    </td>
  </tr>
  </table>

  <table cellpadding="0" cellspacing="0" width="100%" class="add_form_nav margin_bottom_large">
  <tr>
    <td class="selected"><a href="step1.php">{$LANG.word_start}</a></td>
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
      <input type="hidden" id="form_type" value="external" />
      <input type="hidden" id="submission_type" value="{$submission_type}" />

      <table width="100%" class="list_table">
      <tr>
        <td class="pad_left_small" width="200">{$LANG.phrase_form_name}</td>
        <td><input type="text" name="form_name" id="form_name" value="{$page_values.form_name|escape}" style="width: 99%" /></td>
      </tr>
      {if $submission_type == "code"}
      <tbody>
        <tr>
          <td class="pad_left_small">{$LANG.phrase_is_multi_page_form_q}</td>
          <td>
            <input type="radio" name="is_multi_page_form" class="is_multi_page_form" id="impf1" value="yes"
              {if $page_values.is_multi_page_form == "yes"}checked{/if} />
              <label for="impf1">{$LANG.word_yes}</label>
            <input type="radio" name="is_multi_page_form" class="is_multi_page_form" id="impf2" value="no"
              {if $page_values.is_multi_page_form == "no"}checked{/if} />
              <label for="impf2">{$LANG.word_no}</label>
          </td>
        </tr>
      </tbody>
      {/if}
      <tr>
        <td valign="top" class="pad_left_small">
          {if $submission_type == "direct"}
            <input type="hidden" name="is_multi_page_form" value="no" />
            {$LANG.phrase_form_url}
          {else}
            <span id="form_label_single" {if $page_values.is_multi_page_form == "yes"}style="display:none"{/if}>{$LANG.phrase_form_url}</span>
            <span id="form_label_multiple" {if $page_values.is_multi_page_form == "no"}style="display:none"{/if}>{$LANG.phrase_form_urls}</span>
          {/if}
        </td>
        <td>
          {if $submission_type == "direct"}
            <table width="100%" cellpadding="0" cellspacing="0">
            <tr>
              <td><input type="text" name="form_url" id="form_url" value="{$page_values.form_url}" style="width: 98%" /></td>
              <td width="60"><input type="button" class="check_url" id="check_url__form_url" value="{$LANG.phrase_check_url|escape}" /></td>
            </tr>
            </table>
          {else}
            <div id="form_url_single" {if $page_values.is_multi_page_form == "yes"}style="display:none"{/if}>
              <table width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td><input type="text" name="form_url" id="form_url" value="{$page_values.form_url}" style="width: 98%" /></td>
                <td width="60"><input type="button" class="check_url" id="check_url__form_url" value="{$LANG.phrase_check_url|escape}" /></td>
              </tr>
              </table>
            </div>
            <div id="form_url_multiple" {if $page_values.is_multi_page_form == "no"}style="display:none"{/if}>
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
                  {foreach from=$page_values.multi_page_form_urls item=i name=row}
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
                  {if $page_values.multi_page_form_urls|@count == 0}
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
          {/if}
        </td>
      </tr>
      {if $submission_type == "direct"}
      <tr>
        <td valign="top" class="pad_left_small">{$LANG.phrase_redirect_url}</td>
        <td>
          <table width="100%" cellpadding="0" cellspacing="0">
          <tr>
            <td><input type="text" name="redirect_url" id="redirect_url" value="{$page_values.redirect_url}" style="width: 98%" /></td>
            <td width="60"><input type="button" class="check_url" id="check_url__redirect_url" value="{$LANG.phrase_check_url|escape}" /></td>
          </tr>
          </table>
          <div class="medium_grey">
            {$LANG.text_add_form_step_2_text_2}
          </div>
        </td>
      </tr>
      {/if}

      <tr>
        <td class="pad_left_small" valign="top">{$LANG.phrase_who_can_access}</td>
        <td>

          <table cellspacing="1" cellpadding="0" >
          <tr>
            <td>
              <input type="radio" name="access_type" id="at1" value="admin" {if $page_values.access_type == 'admin'}checked{/if} />
                <label for="at1">{$LANG.phrase_admin_only}</label>
            </td>
          </tr>
          <tr>
            <td>
              <input type="radio" name="access_type" id="at2" value="public" {if $page_values.access_type == 'public'}checked{/if} />
                <label for="at2">{$LANG.word_public} <span class="light_grey">{$LANG.phrase_all_clients_have_access}</span></label>
            </td>
          </tr>
          <tr>
            <td>
              <input type="radio" name="access_type" id="at3" value="private" {if $page_values.access_type == 'private'}checked{/if} />
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
                  clients=$selected_client_ids size="4" style="width: 220px"}
              </td>
              <td align="center" valign="middle" width="100">
                <input type="button" value="{$LANG.word_add_uc_rightarrow}"
                  onclick="ft.move_options(this.form['available_client_ids[]'], this.form['selected_client_ids[]']);" /><br />
                <input type="button" value="{$LANG.word_remove_uc_leftarrow}"
                  onclick="ft.move_options(this.form['selected_client_ids[]'], this.form['available_client_ids[]']);" />
              </td>
              <td>
                {clients_dropdown name_id="selected_client_ids[]" multiple="true" multiple_action="show"
                  clients=$selected_client_ids size="4" style="width: 220px"}
              </td>
            </tr>
            </table>
          </div>

        </td>
      </tr>
      </table>

      {if $submission_type == "direct"}
        <p>
          {$LANG.text_form_contains_file_fields}
          <input type="radio" name="uploading_files" id="uploading_files1" value="yes" {if $SESSION.uploading_files == "yes"}checked{/if} />
            <label for="uploading_files1">{$LANG.word_yes}</label>
          <input type="radio" name="uploading_files" id="uploading_files2" value="no" {if $SESSION.uploading_files == "no"}checked{/if} />
            <label for="uploading_files2">{$LANG.word_no}</label>
        </p>
      {/if}

      <p>
        <input type="submit" name="submit" class="next_step" value="{$LANG.word_next_step_rightarrow}" />
      </p>

    </form>

  </div>

{ft_include file='footer.tpl'}
