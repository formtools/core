{$LANG.text_email_template_thanks}

{literal}{foreach from=$fields item=field}
{if $field.col_name != "submission_date" && $field.answer}
  {if $field.field_type == "file"}{$field.field_title}: {display_files files=$FILENAMES_{$field.field_name} folder=$FOLDERURL_{$field.field_name} delim="\n"}
{else}{$field.field_title}: {$field.answer}
{/if}
{/if}
{/foreach}{/literal}

{$LANG.phrase_submission_made}