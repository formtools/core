{$LANG.text_email_template_text_1_c}

{literal}{foreach from=$fields item=field}
{$field.field_title}: {$field.answer}
{/foreach}{/literal}

{$LANG.phrase_submission_made}