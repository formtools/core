          <div class="margin_top margin_bottom_large">
            <img src="{$images_url}/placeholders.png" style="float: left; margin-right: 10px" />
            {$text_reference_tab_info}
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
            <td>{$LANG.text_form_submission_date_placeholder}</td>
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
            <th>{$LANG.phrase_label_response_placeholders}</th>
          </tr>
          {foreach from=$form_fields item=field name=row}
            <tr>
              <td>{$field.field_title}</td>
              <td>{$field.field_name}</td>
              <td>
                <table cellspacing="0" cellpadding="0">
                <tr>
                  <td nowrap class="margin_right_large">Field Label</td>
                  <td class="blue">{literal}{$QUESTION{/literal}_{$field.field_name}{literal}}{/literal}</td>
                </tr>
                <tr>
                  <td nowrap class="margin_right_large">Field Response</td>
                  <td class="blue">
                    {if $field.is_file_field == "yes"}
                      {literal}{$FILENAME{/literal}_{$field.field_name}{literal}}{/literal}, {literal}{$FILEURL{/literal}_{$field.field_name}{literal}}{/literal}
                    {else}
                      {literal}{$ANSWER{/literal}_{$field.field_name}{literal}}{/literal}
                      {if $field.field_name == "core__submission_id"}
                        {literal}/ {$SUBMISSIONID}{/literal}
                      {elseif $field.field_name == "core__submission_date"}
                        {literal}/ {$SUBMISSIONDATE}{/literal}
                      {elseif $field.field_name == "core__last_modified"}
                        {literal}/ {$LASTMODIFIEDDATE}{/literal}
                      {elseif $field.field_name == "core__ip_address"}
                        {literal}/ {$IPADDRESS}{/literal}
                      {/if}
                    {/if}
                  </td>
                </tr>
                </table>
              </td>
            </tr>
          {/foreach}
          </table>

        </div>
