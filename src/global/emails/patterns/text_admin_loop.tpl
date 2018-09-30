{$LANG.text_email_template_text_1_c}

{literal}{foreach from=$fields item=field}
{if $field.col_name != "submission_date"}
  {if $field.is_file_field == "yes"}{$field.field_title}: {if $field.answer}{$field.folder_url}/{$field.answer}{/if}

{else}{$field.field_title}: {$field.answer}
{/if}
{/if}
{/foreach}{/literal}

{$LANG.phrase_submission_made}