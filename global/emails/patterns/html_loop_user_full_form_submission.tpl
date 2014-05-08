<p>
  {$LANG.text_email_template_thanks}
</p>

<table cellpadding="0" cellspacing="1">
{foreach from=$fields item=field}
{if $field.field_type != "system"}
  <tr>
    <td style="font-weight: bold">{$field.field_title}</td>
    <td>{literal}{$ANSWER_{/literal}{$field.field_name}{literal}}{/literal}</td>
  </tr>
{else}
{if $field.col_name == "submission_id"}
  <tr>
    <td style="font-weight: bold">{$field.field_title}</td>
    <td>{literal}{$SUBMISSIONID}{/literal}</td>
  </tr>
{elseif $field.col_name == "last_modified"}
  <tr>
    <td style="font-weight: bold">{$LANG.phrase_last_modified}</td>
    <td>{literal}{$LASTMODIFIEDDATE}{/literal}</td>
  </tr>
{elseif $field.col_name == "ip_address"}
  <tr>
    <td style="font-weight: bold">{$LANG.phrase_ip_address}</td>
    <td>{literal}{$IPADDRESS}{/literal}</td>
  </tr>
{/if}
{/if}
{/foreach}
</table>

<p>
  {$LANG.phrase_submission_made}
</p>