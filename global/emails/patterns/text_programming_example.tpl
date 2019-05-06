<!--
  This is an example for programmers: it contains all the information about the
  field that is available to you in the Smarty loop. Try using the "Display
  Email" option on the Test tab to see what kind of values each attribute
  contains.
-->

{literal}{foreach from=$fields item=field}
Field: {$field.field_title}

[field_id]: {$field.field_id}
[form_id]: {$field.form_id}
[field_name]: {$field.field_name}
[field_size]: {$field.field_size}
[field_type_id]: {$field.field_type_id}
[data_type]: {$field.data_type}
[field_title]: {$field.field_title}
[col_name]: {$field.col_name}
[list_order]: {$field.list_order}
[include_on_redirect]: {$field.include_on_redirect}
[answer]: {$field.answer}

--------------------------------
{/foreach}{/literal}