{$LANG.text_email_template_thanks}

{foreach from=$fields item=field}
{if $field.is_system_field == "yes"}
{if $field.col_name == "submission_id"}
{$field.field_title}: {literal}{$SUBMISSIONID}{/literal}
{elseif $field.col_name == "last_modified_date"}
{$LANG.phrase_last_modified}: {literal}{$LASTMODIFIEDDATE}{/literal}
{elseif $field.col_name == "ip_address"}
{$LANG.phrase_ip_address}: {literal}{$IPADDRESS}{/literal}
{/if}
{elseif $field.is_file_field == "yes"}
{literal}{if $FILENAME_{/literal}{$field.field_name}{literal}}{/literal}{$field.field_title}: {literal}{$FILEURL_{/literal}{$field.field_name}{literal}}{/literal}{literal}
{/if}{/literal}
{else}
{literal}{if $ANSWER_{/literal}{$field.field_name}{literal}}{/literal}{$field.field_title}: {literal}{$ANSWER_{/literal}{$field.field_name}{literal}}{/literal}{literal}
{/if}{/literal}
{/if}
{/foreach}

{$LANG.phrase_submission_made}