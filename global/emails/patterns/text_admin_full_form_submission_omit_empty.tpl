{$LANG.text_email_template_text_1_c}

{literal}{foreach from=$fields item=field}
    {if $field.field_title}
{$field.field_title}: {$field.answer}
    {/if}
{/foreach}{/literal}

{$LANG.phrase_submission_made}