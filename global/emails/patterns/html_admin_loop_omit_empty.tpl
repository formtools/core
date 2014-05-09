<p>
  {$LANG.text_email_template_text_1_c}
</p>

<table cellpadding="0" cellspacing="1">
{literal}{foreach from=$fields item=field}
{if $field.col_name != "submission_date" && $field.answer}
  {if $field.is_file_type == "yes"}
    <tr>
      <td style="font-weight: bold">{$field.field_title}:</td>
      <td><a href="{$field.folder_url}/{$field.answer}">{$field.answer}</a></td>
    </tr>
	{else}
    <tr>
      <td style="font-weight: bold">{$field.field_title}:</td>
      <td>{$field.answer}</td>
    </tr>
  {/if}
{/if}
{/foreach}{/literal}
</table>

<p>
  {$LANG.phrase_submission_made}
</p>