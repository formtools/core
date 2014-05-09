
            <table cellpadding="1" cellspacing="1" width="100%">
            <tr>
              <td class="pad_left">{$LANG.phrase_view_name}</td>
              <td>
                <input type="text" maxlength="100" style="width: 300px;" name="view_name" value="{$view_info.view_name}" />
              </td>
            </tr>
            <tr>
              <td class="pad_left" width="125">{$LANG.phrase_submissions_per_page}</td>
              <td><input type="text" size="3" name="num_submissions_per_page" value="{$view_info.num_submissions_per_page}" /></td>
            </tr>
            <tr>
              <td class="pad_left">{$LANG.phrase_default_sort_order}</td>
              <td>

                <table cellpadding="0" cellspacing="0">
                <tr>
                  <td>
                    {form_fields_dropdown name_id="default_sort_field" form_id=$form_id view_id=$view_id
                        default=$view_info.default_sort_field}
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
              <td class="pad_left" width="160" valign="top">{$LANG.word_access}</td>
              <td>

                <table cellspacing="1" cellpadding="0" >
                <tr>
                  <td>
                    <input type="radio" name="access_type" id="at1" value="admin" {if $view_info.access_type == 'admin'}checked{/if}
                      onclick="page_ns.toggle_view_type(this.value)" />
                      <label for="at1">{$LANG.phrase_admin_only}</label>
                  </td>
                </tr>
                <tr>
                  <td>
                    <div style="float:right;margin-left: 20px">
                      <input type="button" id="client_omit_list_button"
                        value="Manage Client Omit List{if $view_info.access_type == 'public'} ({$num_clients_on_omit_list}){/if}"
                        onclick="window.location='edit.php?page=public_view_omit_list&form_id={$form_id}&view_id={$view_id}'"
                        {if $view_info.access_type != 'public'}disabled{/if} /><br />
                    </div>

                    <input type="radio" name="access_type" id="at2" value="public" {if $view_info.access_type == 'public'}checked{/if}
                      onclick="page_ns.toggle_view_type(this.value)" />
                      <label for="at2">{$LANG.word_public} <span class="light_grey">{$LANG.phrase_all_clients_have_access}</span></label>
                  </td>
                </tr>
                <tr>
                  <td>
                    <input type="radio" name="access_type" id="at3" value="private" {if $view_info.access_type == 'private'}checked{/if}
                      onclick="page_ns.toggle_view_type(this.value)" />
                      <label for="at3">{$LANG.word_private} <span class="light_grey">{$LANG.phrase_only_specific_clients_have_access}</span></label>
                  </td>
                </tr>
                </table>

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
                      <select name="selected_user_ids[]" multiple size="4" style="width: 180px">
                        {$selected_users}
                      </select>
                    </td>
                  </tr>
                  </table>

                </div>

              </td>
            </tr>
            <tr>
              <td class="pad_left">{$LANG.phrase_may_add_submissions}</td>
              <td valign="top">
                <input type="radio" name="may_add_submissions" value="yes" id="cmas1"
                  {if $view_info.may_add_submissions == "yes"}checked{/if} />
                  <label for="cmas1">{$LANG.word_yes}</label>
                <input type="radio" name="may_add_submissions" value="no" id="cmas2"
                  {if $view_info.may_add_submissions == "no"}checked{/if} />
                  <label for="cmas2">{$LANG.word_no}</label>
              </td>
            </tr>
            <tr>
              <td class="pad_left">{$LANG.phrase_may_delete_view_submissions}</td>
              <td valign="top">
                <input type="radio" name="may_delete_submissions" value="yes" id="cmds1"
                  {if $view_info.may_delete_submissions == "yes"}checked{/if} />
                  <label for="cmds1">{$LANG.word_yes}</label>
                <input type="radio" name="may_delete_submissions" value="no" id="cmds2"
                  {if $view_info.may_delete_submissions == "no"}checked{/if} />
                  <label for="cmds2">{$LANG.word_no}</label>
              </td>
            </tr>
            </table>
