
          {if $num_submissions == 0}
            <div class="margin_top margin_bottom_large">
              {$LANG.text_test_email_templates_no_submissions}
            </div>
          {else}

            <div class="margin_top margin_bottom_large">
              {$LANG.text_test_email}
            </div>

            <table cellpadding="0" cellspacing="1" class="list_table margin_bottom_large" width="100%">
            <tr>
              <td width="150" class="pad_left_small">{$LANG.phrase_email_format}</td>
              <td>
                <select name="test_email_format" id="test_email_format">
                  <option value="both" {if $test_email_format == "both"}selected{/if}>{$LANG.phrase_text_and_html}</option>
                  <option value="text" {if $test_email_format == "text"}selected{/if}>{$LANG.word_text}</option>
                  <option value="html" {if $test_email_format == "html"}selected{/if}>{$LANG.word_html}</option>
                </select>
              </td>
              <td rowspan="3" width="120" class="subpanel" align="center">
                <input type="button" name="send_test_email" value="{$LANG.phrase_send_test_email}"
                  onclick="return emails_ns.send_test_email(this.form, 'send')" /><br />
                <input type="button" name="display_email" value="{$LANG.phrase_display_email}"
                  onclick="return emails_ns.send_test_email(this.form, 'display')" /><br />

                <div id="ajax_activity" style="padding:3px; display:none"><img src="{$images_url}/ajax_activity_grey.gif" /></div>
                <div id="ajax_no_activity" style="padding:3px;"><img src="{$images_url}/ajax_no_activity_grey.gif" /></div>
              </td>
            </tr>
            <tr>
              <td class="pad_left_small">{$LANG.phrase_your_email_address}</td>
              <td>
                <input type="text" name="test_email_recipient" id="test_email_recipient" style="width:390px" value="{$test_email_recipient}" />
              </td>
            </tr>
            <tr>
              <td class="pad_left_small" valign="top">{$LANG.phrase_form_submission}</td>
              <td>
                <input type="radio" name="test_email_data_source" id="test_email_data_random_submission"
                  value="random_submission" {if $test_email_data_source == "random_submission"}checked{/if} />
                  <label for="test_email_data_random_submission">{$LANG.phrase_random_form_submission}</label><br />
                <input type="radio" name="test_email_data_source" id="test_email_data_submission_id"
                  value="submission_id" {if $test_email_data_source == "submission_id"}checked{/if} />
                  <label for="test_email_data_submission_id">{$LANG.phrase_submission_id}</label>
                  <input type="input" id="test_email_submission_id" name="test_email_submission_id" size="5" value="{$test_email_submission_id}" />
              </td>
            </tr>
            </table>

            <div id="display_text" class="hidden" style="padding: 8px; margin-bottom: 6px; background-color: #f9f9f9; border: 1px solid #666666;">
              <div class="bold">{$LANG.phrase_text_email}</div>
              <div id="display_text_content"></div>
            </div>

            <div id="display_html" class="hidden" style="padding: 8px; margin-bottom: 6px; background-color: #f9f9f9; border: 1px solid #666666;">
              <div class="bold">{$LANG.phrase_html_email}</div>
              <div id="display_html_content"></div>
            </div>

          {/if}
