{$LANG.text_email_template_text_1_c}

{foreach from=$fields item=field}
{if $field.field_type == "system"}
{if $field.col_name == "submission_id"}
{$field.field_title}: {literal}{$SUBMISSIONID}{/literal}
{elseif $field.col_name == "last_modified"}
{$LANG.phrase_last_modified}
{literal}{$LASTMODIFIEDDATE}{/literal}
{elseif $field.col_name == "ip_address"}
{$LANG.phrase_ip_address}: {literal}{$IPADDRESS}{/literal}
{/if}
{elseif $field.field_type == "file"}
{$field.field_title}: {literal}{$FILEURL_{/literal}{$field.field_name}{literal}}{/literal}
{else}
{$field.field_title}: {literal}{$ANSWER_{/literal}{$field.field_name}{literal}}{/literal}
{/if}
{/foreach}

{$LANG.phrase_submission_made}