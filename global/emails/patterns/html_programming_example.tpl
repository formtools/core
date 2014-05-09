<!--
  This is an example for programmers: it contains all the information about the
  field that is available to you in the Smarty loop. Try using the "Display
  Email" option on the Test tab to see what kind of values each attribute
  contains.
-->

{literal}{foreach from=$fields item=field}
<div>Field: {$field.field_title}</div>

<table cellspacing="0" cellpadding="1" border="1" width="100%">
<tr>
  <td width="180"><b>[field_id]</b></td>
  <td>{$field.field_id}</td>
</tr>
<tr>
  <td><b>[form_id]</b></td>
  <td>{$field.form_id}</td>
</tr>
<tr>
  <td><b>[field_name]</b></td>
  <td>{$field.field_name}</td>
</tr>
<tr>
  <td><b>[field_size]</b></td>
  <td>{$field.field_size}</td>
</tr>
<tr>
  <td><b>[field_type_id]</b></td>
  <td>{$field.field_type_id}</td>
</tr>
<tr>
  <td><b>[data_type]</b></td>
  <td>{$field.data_type}</td>
</tr>
<tr>
  <td><b>[field_title]</b></td>
  <td>{$field.field_title}</td>
</tr>
<tr>
  <td><b>[col_name]</b></td>
  <td>{$field.col_name}</td>
</tr>
<tr>
  <td><b>[list_order]</b></td>
  <td>{$field.list_order}</td>
</tr>
<tr>
  <td><b>[include_on_redirect]</b></td>
  <td>{$field.include_on_redirect}</td>
</tr>
<tr>
  <td><b>[answer]</b></td>
  <td>{$field.answer}</td>
</tr>
</table>

<br />
{/foreach}{/literal}