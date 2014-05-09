  <div class="subtitle underline margin_top_large">{$LANG.word_emails|upper}</div>

  {ft_include file='messages.tpl'}

  <div class="margin_bottom_large">
    {$LANG.text_email_tab_summary}
  </div>

  <form action="{$same_page}" method="post">
    <input type="hidden" name="page" value="emails" />

    {if $form_emails|@count == 0}

      <div class="notify yellow_bg" style="width:100%">
        <div style="padding: 8px">
          {$LANG.notify_no_emails_defined}
        </div>
      </div>

    {else}
      {$pagination}

      <table class="list_table" cellspacing="1" cellpadding="1">
      <tr>
        <th>{$LANG.phrase_email_template}</th>
        <th>{$LANG.word_recipient_sp}</th>
        <th width="90">{$LANG.word_status}</th>
        <th class="edit"></th>
        <th class="del colN"></th>
      </tr>

      {foreach from=$form_emails item=email name=row}
        {assign var='index' value=$smarty.foreach.row.index}
        {assign var='count' value=$smarty.foreach.row.iteration}
        {assign var='email_id' value=$email.email_id}

         <tr>
           <td>{$email.email_template_name}</td>
           <td>
            {if $email.recipients|@count == 0}
              <span class="light_grey">{$LANG.word_none}</span>
            {elseif $email.recipients|@count == 1}
              {$email.recipients[0].final_recipient}
            {else}
              <select>
                {* this is a little convoluted - and tightly coupled to the order in which the email recipients are returned...
                   Basically, this groups the email recipients in optgroups "cc" and "bcc". *}
                {assign var=last_recipient_type value=""}
                {foreach from=$email.recipients item=recipient name=user_row}

                  {if $last_recipient_type != $recipient.recipient_type}
                    {if $last_recipient_type != ""}
                      </optgroup>
                    {/if}
                    <optgroup label="{$recipient.recipient_type}">

                    {assign var=last_recipient_type value=$recipient.recipient_type}
                  {/if}

                  <option>{if $recipient.recipient_user_type == "form_email_field"}{$LANG.phrase_form_email_field_b_c}{/if} {$recipient.final_recipient}</option>
                {/foreach}

                {if $last_recipient_type != ""}
                  </optgroup>
                {/if}
              </select>
            {/if}
           </td>
          <td align="center">
            {if $email.email_status == "enabled"}
              <span class="light_green">{$LANG.word_enabled}</span>
            {else}
              <span class="red">{$LANG.word_disabled}</span>
            {/if}
          </td>
          <td class="edit"><a href="{$same_page}?page=edit_email&email_id={$email_id}"></a></td>
          <td class="del colN"><a href="#" onclick="page_ns.delete_email({$email_id})"></a></td>
        </tr>
      {/foreach}
      </table>

    {/if}

    <div class="margin_top_large">
      {if $all_form_emails|@count > 0}
        <select name="create_email_from_email_id">
          <option value="">{$LANG.phrase_new_blank_email}</option>
          <optgroup label="{$LANG.phrase_copy_email_settings_from}">
          {foreach from=$all_form_emails key=k item=i}
              <option value="{$i.email_id}">{$i.email_template_name}</option>
          {/foreach}
          </optgroup>
        </select>
      {/if}
      <input type="submit" name="add_email" value="{$LANG.phrase_create_new_email}" />
      <input type="submit" name="edit_email_user_settings" value="{$LANG.phrase_configure_form_email_fields} ({$num_registered_form_emails})" />
    </div>

  </form>
