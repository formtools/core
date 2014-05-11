            <table cellpadding="2" cellspacing="1" width="100%" class="margin_bottom">
            <tr>
              <td width="10" class="red">*</td>
              <td width="180">{$LANG.phrase_email_template_name}</td>
              <td><input type="text" name="email_template_name" value="{$template_info.email_template_name|escape}" style="width:484px;" maxlength="100" /></td>
            </tr>
            <tr>
              <td class="red">*</td>
              <td>{$LANG.word_status}</td>
              <td>
                <input type="radio" name="email_status" id="status_enabled" value="enabled" {if $template_info.email_status == "enabled"}checked="checked"{/if} />
                  <label for="status_enabled" class="light_green">{$LANG.word_enabled}</label>
                <input type="radio" name="email_status" id="status_disabled" value="disabled" {if $template_info.email_status == "disabled"}checked="checked"{/if} />
                  <label for="status_disabled" class="red">{$LANG.word_disabled}</label>
              </td>
            </tr>
            <tr>
              <td valign="top" class="red"> </td>
              <td valign="top">{$LANG.phrase_event_trigger}</td>
              <td>
                <input type="checkbox" name="email_event_trigger[]" id="eet1" value="on_submission"
                  {if "on_submission"|in_array:$template_info.email_event_trigger}checked="checked"{/if} />
                  <label for="eet1">{$LANG.phrase_on_form_submission}</label><br />
                <input type="checkbox" name="email_event_trigger[]" id="eet2" value="on_edit"
                  {if "on_edit"|in_array:$template_info.email_event_trigger}checked="checked"{/if} />
                  <label for="eet2">{$LANG.phrase_when_submission_is_edited}</label><br />
                <input type="checkbox" name="email_event_trigger[]" id="eet3" value="on_delete"
                  {if "on_delete"|in_array:$template_info.email_event_trigger}checked="checked"{/if} />
                  <label for="eet3">{$LANG.phrase_when_submission_is_deleted}</label><br />
              </td>
            </tr>
            {template_hook location="edit_template_tab1"}
            </table>

            <div class="grey_box">
              <div>
                <a href="#" onclick="return emails_ns.toggle_advanced_settings()">{$LANG.phrase_advanced_settings_rightarrow}</a>
              </div>

              <div {if !isset($SESSION.edit_email_advanced_settings) || $SESSION.edit_email_advanced_settings == "false"}style="display:none"{/if} id="advanced_settings">
                <table cellpadding="2" cellspacing="1" width="100%">
                <tr>
                  <td valign="top" width="180">{$LANG.phrase_when_sent}</td>
                  <td>
                    <input type="radio" name="view_mapping_type" id="vmt1" value="all" {if $template_info.view_mapping_type == "all"}checked="checked"{/if} />
                      <label for="vmt1">{$LANG.phrase_for_any_form_submission}</label><br />
                    <input type="radio" name="view_mapping_type" id="vmt2" value="specific" {if $template_info.view_mapping_type == "specific"}checked="checked"{/if}
                      {if $filtered_views|@count == 0}disabled="disabled"{/if} />
                      {if $filtered_views|@count == 0}
                        <span class="medium_grey">{$LANG.phrase_no_views_with_filters_p}</span>
                      {else}
                        <label for="vmt2">{$LANG.phrase_for_view_submissions}</label>
                        <div id="when_sent_views" {if $template_info.view_mapping_type != "specific"}style="display:none"{/if}>
                          <table width="100%">
                          <tr>
                            <td>
                              <select name="available_when_sent_views[]" id="available_when_sent_views" multiple size="4" style="width:190px">
                                {foreach from=$filtered_views item=view_info}
                                  {assign var=is_found value=false}
                                  {foreach from=$template_info.when_sent_view_ids item=curr_view_id}
                                    {if $curr_view_id == $view_info.view_id}
                                      {assign var=is_found value=true}
                                    {/if}
                                  {/foreach}
                                  {if !$is_found}
                                    <option value="{$view_info.view_id}">{$view_info.view_name}</option>
                                  {/if}
                                {/foreach}
                              </select>
                            </td>
                            <td valign="middle" align="center">
                              <span id="row_{$row}_actions">
                                <input type="button" onclick="return ft.move_options('available_when_sent_views', 'selected_when_sent_views')" value="{$LANG.word_add_uc_rightarrow}" /><br />
                                <input type="button" onclick="return ft.move_options('selected_when_sent_views', 'available_when_sent_views')" value="{$LANG.word_remove_uc_leftarrow}" />
                              </span>
                            </td>
                            <td>
                              <select name="selected_when_sent_views[]" id="selected_when_sent_views" multiple size="4" style="width:190px">
                                {foreach from=$selected_when_sent_views item=view_info}
                                  <option value="{$view_info.view_id}">{$view_info.view_name}</option>
                                {/foreach}
                              </select>
                            </td>
                          </tr>
                          </table>
                          <div class="medium_grey pad_top_small">{$LANG.text_list_views_with_filters}</div>
                        </div>
                      {/if}
                  </td>
                </tr>
                <tr>
                  <td valign="top">{$LANG.text_send_email_from_edit_submission_page}</td>
                  <td>
                    <input type="radio" name="include_on_edit_submission_page" id="iesp1" value="no"
                      {if $template_info.include_on_edit_submission_page == "no"}checked="checked"{/if} />
                      <label for="iesp1">{$LANG.word_no}</label><br />
                    <input type="radio" name="include_on_edit_submission_page" id="iesp2" value="all_views"
                      {if $template_info.include_on_edit_submission_page == "all_views"}checked="checked"{/if} />
                      <label for="iesp2">{$LANG.phrase_yes_for_all_views}</label><br />
                    <input type="radio" name="include_on_edit_submission_page" id="iesp3" value="specific_views"
                      {if $template_info.include_on_edit_submission_page == "specific_views"}checked="checked"{/if} />
                      <label for="iesp3">{$LANG.phrase_yes_for_specific_views}</label><br />

                    <div id="include_on_edit_submission_page_views"
                      {if $template_info.include_on_edit_submission_page != "specific_views"}style="display:none"{/if}>
                      <table width="100%">
                      <tr>
                        <td>
                          <select name="available_edit_submission_views[]" id="available_edit_submission_views" multiple size="4" style="width:190px">
                            {foreach from=$views item=view_info}
                              {assign var=is_found value=false}
                              {foreach from=$template_info.edit_submission_page_view_ids item=curr_view_id}
                                {if $curr_view_id == $view_info.view_id}
                                  {assign var=is_found value=true}
                                {/if}
                              {/foreach}
                              {if !$is_found}
                                <option value="{$view_info.view_id}">{$view_info.view_name}</option>
                              {/if}
                            {/foreach}
                          </select>
                        </td>
                        <td valign="middle" align="center">
                          <span id="row_{$row}_actions">
                            <input type="button" onclick="return ft.move_options('available_edit_submission_views', 'selected_edit_submission_views')" value="{$LANG.word_add_uc_rightarrow}" /><br />
                            <input type="button" onclick="return ft.move_options('selected_edit_submission_views', 'available_edit_submission_views')" value="{$LANG.word_remove_uc_leftarrow}" />
                          </span>
                        </td>
                        <td>
                          <select name="selected_edit_submission_views[]" id="selected_edit_submission_views" multiple size="4" style="width:190px">
                            {foreach from=$selected_edit_submission_views item=view_info}
                              <option value="{$view_info.view_id}">{$view_info.view_name}</option>
                            {/foreach}
                          </select>
                        </td>
                      </tr>
                      </table>
                    </div>

                  </td>
                </tr>
                <tr>
                  <td valign="top">{$LANG.phrase_limit_email_content}</td>
                  <td>
                    {views_dropdown name_id="limit_email_content_to_fields_in_view" form_id=$form_id show_empty_label=true
                      selected=$template_info.limit_email_content_to_fields_in_view}
                    <div class="medium_grey">
                      {$LANG.text_limit_email_content_desc}
                    </div>
                  </td>
                </tr>
                </table>

                {template_hook location="edit_template_tab1_advanced"}

              </div>
            </div>
