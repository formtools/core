  {if $registered_form_emails|@count == 0}
    <div class="hint margin_bottom">
      {eval_smarty_string placeholder_str=$LANG.notify_no_user_email_fields_configured}
    </div>
  {/if}

  <table cellpadding="2" cellspacing="1" width="100%">
  <tr>
    <td width="10" valign="top" class="red">*</td>
    <td width="160" valign="top">{$LANG.word_recipient_sp}</td>
    <td>
      <table cellspacing="0" cellpadding="0">
      <tr>
        <td>
          <div class="hint margin_bottom">
            {if $form_info.access_type == "admin"}
              {$LANG.notify_form_access_type_email_info}
            {/if}
            {$LANG.notify_edit_email_fields_link}
          </div>

          <table cellspacing="0">
          <tr>
            <td>
              <select id="recipient_options" onchange="emails_ns.show_custom_email_field('recipients', this.value)"
                onkeyup="emails_ns.show_custom_email_field('recipients', this.value)">
                <option value="" selected>{$LANG.phrase_please_select}</option>
                <optgroup label="{$LANG.word_administrator}">
                  <option value="admin">{$admin_info.first_name} {$admin_info.last_name} &lt;{$admin_info.email}&gt;</option>
                </optgroup>
                {if $clients}
                  <optgroup label="{$LANG.word_clients}">
                  {foreach from=$clients item=client name=row}
                    <option value="client_account_id_{$client.account_id}">{$client.first_name} {$client.last_name} &lt;{$client.email}&gt;</option>
                  {/foreach}
                  </optgroup>
                {/if}
                {if $registered_form_emails|@count > 0}
                  <optgroup label="{$LANG.phrase_form_email_fields}">
                  {foreach from=$registered_form_emails item=email_info}
                    <option value="form_email_id_{$email_info.form_email_id}">{$email_info.email_field_label}</option>
                  {/foreach}
                  </optgroup>
                {/if}
                <optgroup label="{$LANG.word_other}">
                 <option value="custom">{$LANG.phrase_custom_recipient}</option>
                </optgroup>
              </select>
            </td>
            <td>
              <select id="recipient_type">
                <option value="">{$LANG.word_main|lower}</option>
                <option value="cc">cc</option>
                <option value="bcc">bcc</option>
              </select>
            </td>
            <td><input type="button" value="{$LANG.word_add|upper}" onclick="emails_ns.add_recipient(this.form)" /></td>
          </tr>
          </table>

          <div id="custom_recipients" class="box" style="display:none; margin-top: 2px;">
            <table cellspacing="0">
            <tr>
              <td>
                <table cellspacing="0">
                <tr>
                  <td></td>
                  <td class="pad_right">{$LANG.word_name}</td>
                  <td><input type="text" id="custom_recipient_name" style="width:200px" /></td>
                </tr>
                <tr>
                  <td class="red">*</td>
                  <td class="pad_right">{$LANG.word_email}</td>
                  <td><input type="text" id="custom_recipient_email" name="custom_recipient_email" style="width:200px" /></td>
                </tr>
                <tr>
                  <td class="red">*</td>
                  <td class="pad_right">{$LANG.phrase_recipient_type}</td>
                  <td>
                    <select id="custom_recipient_type">
                      <option value="">{$LANG.word_main|lower}</option>
                      <option value="cc">cc</option>
                      <option value="bcc">bcc</option>
                    </select>
                  </td>
                </tr>
                </table>
              </td>
              <td>
                <input type="button" value="{$LANG.word_add|upper}" onclick="emails_ns.add_custom_recipient(this.form)" />
              </td>
            </tr>
            </table>
          </div>

        </td>
      </tr>
      </table>

      <div id="email_recipients" style="padding: 6px; border:1px solid #336699">
        <div id="no_recipients" {if $template_info.recipients|@count > 0}style="display:none"{/if}>{$LANG.text_no_recipients_added}</div>

        {foreach from=$template_info.recipients item=recipient name=row}
          {assign var='count' value=$smarty.foreach.row.iteration}
          {assign var='recipient_type' value=$recipient.recipient_type}

          {if $recipient_type == "cc"}
            {assign var='recipient_type_str' value='&nbsp;<span class="bold">[cc]</span>'}
          {elseif $recipient_type == "bcc"}
            {assign var='recipient_type_str' value='&nbsp;<span class="bold">[bcc]</span>'}
          {elseif $recipient_type == "main"}
            {assign var='recipient_type_str' value=""}
          {/if}

          {if $recipient.recipient_user_type == "admin"}
            <div id="recipient_{$count}">
              {$admin_info.first_name} {$admin_info.last_name} &lt;{$admin_info.email}&gt;{$recipient_type_str}&nbsp;
              <a href="#" onclick="return emails_ns.remove_recipient({$count})">[x]</a>
              <input type="hidden" name="recipients[]" value="{$count}" />
              <input type="hidden" name="recipient_{$count}_user_type" value="admin" />
              <input type="hidden" id="recipient_{$count}_type" name="recipient_{$count}_type" value="{$recipient_type}" />
            </div>
          {elseif $recipient.recipient_user_type == "form_email_field"}
            <div id="recipient_{$count}">
              {$LANG.phrase_form_email_field_b_c} {$recipient.final_recipient}{$recipient_type_str}&nbsp;
              <a href="#" onclick="return emails_ns.remove_recipient({$count})">[x]</a>
              <input type="hidden" name="recipients[]" value="{$count}" />
              <input type="hidden" name="recipient_{$count}_user_type" value="form_email_field" />
              <input type="hidden" name="recipient_{$count}_form_email_id" value="{$recipient.form_email_id}	" />
              <input type="hidden" id="recipient_{$count}_type" name="recipient_{$count}_type" value="{$recipient.recipient_type}" />
            </div>
          {elseif $recipient.recipient_user_type == "client"}
            <div id="recipient_{$count}">
              {$recipient.first_name} {$recipient.last_name} &lt;{$recipient.email}&gt;{$recipient_type_str}&nbsp;
              <a href="#" onclick="return emails_ns.remove_recipient({$count})">[x]</a>
              <input type="hidden" name="recipients[]" value="{$count}" />
              <input type="hidden" name="recipient_{$count}_user_type" value="client" />
              <input type="hidden" id="recipient_{$count}_type" name="recipient_{$count}_type" value="{$recipient.recipient_type}" />
              <input type="hidden" name="recipient_{$count}_account_id" value="{$recipient.account_id}" />
            </div>
          {elseif $recipient.recipient_user_type == "custom"}
            <div id="recipient_{$count}">
              {$recipient.custom_recipient_name} &lt;{$recipient.custom_recipient_email}&gt;{$recipient_type_str}&nbsp;
              <a href="#" onclick="return emails_ns.remove_recipient({$count})">[x]</a>
              <input type="hidden" name="recipients[]" value="{$count}" />
              <input type="hidden" name="recipient_{$count}_user_type" value="custom" />
              <input type="hidden" name="recipient_{$count}_type" id="recipient_{$count}_type" value="{$recipient.recipient_type}" />
              <input type="hidden" name="recipient_{$count}_name" value="{$recipient.custom_recipient_name|escape}" />
              <input type="hidden" name="recipient_{$count}_email" value="{$recipient.custom_recipient_email|escape}" />
            </div>
          {/if}
        {/foreach}
      </div>

    </td>
  </tr>
  <tr>
    <td class="red">*</td>
    <td>{$LANG.phrase_subject_line}</td>
    <td><input type="text" name="subject" class="lang_placeholder_field" style="width: 490px" value="{$template_info.subject|escape}" /></td>
  </tr>
  <tr>
    <td valign="top" class="red">*</td>
    <td valign="top">{$LANG.word_from}</td>
    <td>
      <select name="email_from" id="email_from" onchange="emails_ns.show_custom_email_field('from', this.value)"
        onchange="emails_ns.show_custom_email_field('from', this.value)">
        <option value="">{$LANG.phrase_please_select}</option>
        <option value="none" {if $template_info.email_from == "none"}selected{/if}>{$LANG.phrase_none_not_recommended}</option>
        <optgroup label="{$LANG.word_administrator}">
          <option value="admin" {if $template_info.email_from == "admin"}selected{/if}>{$admin_info.first_name} {$admin_info.last_name} &lt;{$admin_info.email}&gt;</option>
        </optgroup>
        {if $clients}
          <optgroup label="{$LANG.word_clients}">
          {foreach from=$clients item=client name=row}
            <option value="client_account_id_{$client.account_id}" {if $template_info.email_from_account_id == $client.account_id}selected{/if}>{$client.first_name} {$client.last_name} &lt;{$client.email}&gt;</option>
          {/foreach}
          </optgroup>
        {/if}
        {if $registered_form_emails|@count > 0}
          <optgroup label="{$LANG.phrase_form_email_fields}">
          {foreach from=$registered_form_emails item=email_info}
            <option value="form_email_id_{$email_info.form_email_id}"
            {if $template_info.email_from_form_email_id == $email_info.form_email_id}selected{/if}>{$LANG.phrase_form_email_field_b_c} {$email_info.email_field_label}</option>
          {/foreach}
          </optgroup>
        {/if}
        <optgroup label="{$LANG.word_other}">
          <option value="custom" {if $template_info.email_from == "custom"}selected{/if}>{$LANG.word_custom}</option>
        </optgroup>
      </select>

      <div id="custom_from" class="box" style="margin-top: 4px;{if $template_info.email_from != "custom"}display:none{/if}" >
        <table>
        <tr>
          <td></td>
          <td class="pad_right">{$LANG.word_name_c}</td>
          <td><input type="text" name="custom_from_name" value="{$template_info.custom_from_name|escape}" style="width:200px" /></td>
        </tr>
        <tr>
          <td class="red">*</td>
          <td class="pad_right">{$LANG.word_email_c}</td>
          <td><input type="text" name="custom_from_email" value="{$template_info.custom_from_email|escape}" style="width:200px" /></td>
        </tr>
        </table>
      </div>

    </td>
  </tr>
  <tr>
    <td valign="top" class="red"> </td>
    <td valign="top">{$LANG.word_reply_to}</td>
    <td>

      <select name="email_reply_to" id="email_reply_to" onchange="emails_ns.show_custom_email_field('reply_to', this.value)"
        onchange="emails_ns.show_custom_email_field('reply_to', this.value)">
        <option value="">{$LANG.phrase_please_select}</option>
        <option value="none" {if $template_info.email_reply_to == "none"}selected{/if}>{$LANG.word_none}</option>
        <optgroup label="{$LANG.word_administrator}">
          <option value="admin" {if $template_info.email_reply_to == "admin"}selected{/if}>{$admin_info.first_name} {$admin_info.last_name} &lt;{$admin_info.email}&gt;</option>
        </optgroup>
        {if $clients}
          <optgroup label="{$LANG.word_clients}">
          {foreach from=$clients item=client name=row}
            <option value="client_account_id_{$client.account_id}" {if $template_info.email_reply_to_account_id == $client.account_id}selected{/if}>{$client.first_name} {$client.last_name} &lt;{$client.email}&gt;</option>
          {/foreach}
          </optgroup>
        {/if}
        {if $registered_form_emails|@count > 0}
          <optgroup label="{$LANG.phrase_form_email_fields}">
          {foreach from=$registered_form_emails item=email_info}
            <option value="form_email_id_{$email_info.form_email_id}"
            {if $template_info.email_reply_to_form_email_id == $email_info.form_email_id}selected{/if}>{$LANG.phrase_form_email_field_b_c} {$email_info.email_field_label}</option>
          {/foreach}
          </optgroup>
        {/if}
        <optgroup label="{$LANG.word_other}">
          <option value="custom" {if $template_info.email_reply_to == "custom"}selected{/if}>{$LANG.word_custom}</option>
        </optgroup>
      </select>

      <div id="custom_reply_to" class="box" style="margin-top: 4px;{if $template_info.email_reply_to != "custom"}display:none{/if}">
        <table>
        <tr>
          <td></td>
          <td class="pad_right" width="60">{$LANG.word_name_c}</td>
          <td><input type="text" name="custom_reply_to_name" value="{$template_info.custom_reply_to_name}" style="width:200px" /></td>
        </tr>
        <tr>
          <td class="red">*</td>
          <td class="pad_right">{$LANG.word_email_c}</td>
          <td><input type="text" name="custom_reply_to_email" style="width:200px" value="{$template_info.custom_reply_to_email}" /></td>
        </tr>
        </table>
      </div>

    </td>
  </tr>
  {template_hook location="edit_template_tab2"}
  </table>

