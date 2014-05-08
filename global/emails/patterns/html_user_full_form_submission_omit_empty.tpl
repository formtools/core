<p>
    {$LANG.text_email_template_thanks}
</p>

<table cellpadding="0" cellspacing="1">
{literal}{foreach from=$fields item=field}
    {if $field.field_title}
        <tr>
            <td style="font-weight: bold">{$field.field_title}:<td>
            <td>{$field.answer}<td>
        </tr>
    {/if}
{/foreach}{/literal}
</table>

<p>
    {$LANG.phrase_submission_made}
</p>