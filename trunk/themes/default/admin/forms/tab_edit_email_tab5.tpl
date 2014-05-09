          <div class="margin_top margin_bottom_large">
            {$LANG.text_reference_tab_info}
          </div>

          <p class="subtitle">{$LANG.phrase_global_placeholders|upper}</p>
          <p>
            {$LANG.text_global_placeholder_info}
          </p>

          <table cellpadding="1" cellspacing="1" class="list_table" width="100%">
          <tr>
            <td valign="top" class="blue" width="160">{literal}{$FORMNAME}{/literal}</td>
            <td>{$LANG.text_name_of_form}</td>
          </tr>
          <tr>
            <td valign="top" class="blue">{literal}{$LOGINURL}{/literal}</td>
            <td>{$LANG.text_form_tools_login_url}</td>
          </tr>
          <tr>
            <td valign="top" class="blue">{literal}{$FORMURL}{/literal}</td>
            <td>{$LANG.text_form_tools_form_url}</td>
          </tr>
          <tr>
            <td valign="top" class="blue">{literal}{$ADMINEMAIL}{/literal}</td>
            <td>{$LANG.text_admin_email_placeholder_info}</td>
          </tr>
          <tr>
            <td valign="top" class="blue">{literal}{$SUBMISSIONDATE}{/literal}</td>
            <td>{$submission_date_str}</td>
          </tr>
          <tr>
            <td valign="top" class="blue">{literal}{$LASTMODIFIEDDATE}{/literal}</td>
            <td>
              {$LANG.text_last_modified_date_explanation_c}
              {literal}{$SUBMISSIONDATE}{/literal}
            </td>
          </tr>
          <tr>
            <td valign="top" class="blue">{literal}{$SUBMISSIONID}{/literal}</td>
            <td>{$LANG.text_unique_submission_id}</td>
          </tr>
          <tr>
            <td valign="top" class="blue">{literal}{$IPADDRESS}{/literal}</td>
            <td>{$LANG.text_submission_ip_address}</td>
          </tr>
          </table>
          <br />

          <p class="subtitle">{$LANG.phrase_form_placeholders|upper}</p>
          <p>
            {$LANG.text_form_placeholder_info}
            {$file_field_text}
          </p>

          <table cellpadding="1" cellspacing="1" class="list_table" width="100%">
          <tr>
            <th>{$LANG.phrase_field_label}</th>
            <th>{$LANG.phrase_form_field}</th>
            <th>{$LANG.phrase_field_label_placeholder}</th>
            <th>{$LANG.phrase_field_response_placeholder}</th>
          </tr>
          {foreach from=$form_fields item=field name=row}

            {if $field.field_type != "system"}
              <tr>
                <td>{$field.field_title}</td>
                <td>{$field.field_name}</td>
                <td class="blue">{literal}{$QUESTION{/literal}_{$field.field_name}{literal}}{/literal}</td>
                <td class="blue">

                  {* if this is a file field, display the image folder URL placeholder *}
                  {if $field.field_type == "file"}
                    {literal}{$FILENAME{/literal}_{$field.field_name}{literal}}{/literal}, {literal}{$FILEURL{/literal}_{$field.field_name}{literal}}{/literal}
                  {else}
                    {literal}{$ANSWER{/literal}_{$field.field_name}{literal}}{/literal}
                  {/if}

                </td>
              </tr>
            {/if}
          {/foreach}
          </table>

          <br />

          <p class="subtitle">{$LANG.phrase_user_account_placeholders|upper}</p>
          <p>
            {$LANG.text_user_account_placeholders_explanation}
          </p>

          <table cellpadding="1" cellspacing="1" class="list_table" width="100%">
          <tr>
            <td valign="top" class="blue" width="160">{literal}{$FIRSTNAME}{/literal}</td>
            <td>{$LANG.text_first_name}</td>
          </tr>
          <tr>
            <td valign="top" class="blue" width="160">{literal}{$LASTNAME}{/literal}</td>
            <td>{$LANG.text_last_name}</td>
          </tr>
          <tr>
            <td valign="top" class="blue" width="160">{literal}{$COMPANYNAME}{/literal}</td>
            <td>{$LANG.text_company_name}</td>
          </tr>
          <tr>
            <td valign="top" class="blue" width="160">{literal}{$EMAIL}{/literal}</td>
            <td>{$LANG.text_email_address}</td>
          </tr>
          </table>

        </div>
