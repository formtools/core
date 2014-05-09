{ft_include file='header.tpl'}

  <table cellpadding="0" cellspacing="0">
  <tr>
    <td width="45"><img src="{$images_url}/icon_forms.gif" width="34" height="34" /></td>
    <td class="title">{$LANG.word_forms|upper}</td>
  </tr>
  </table>

  <p>
    {$LANG.text_client_welcome}
  </p>

  {* here, there are no forms assigned to this client *}
  {if count($forms) == 0}
    <b>{$LANG.text_client_no_forms}</b>
  {else}

    <table class="list_table" cellpadding="1" cellspacing="1" style="width:600px">
    <tr style="height: 20px;">
      <th>{$LANG.word_form}</th>
      <th width="80">{$LANG.word_status}</th>
      <th width="80">{$LANG.word_submissions|upper}</th>
    </tr>

    {* loop through all forms assigned to this client *}
    {foreach from=$forms key=k item=form_info}
      {assign var=form_id value=$form_info.form_id}

      <tr style="height: 20px;">
        <td><a href="{$form_info.form_url}" target="_blank">{$form_info.form_name}</a></td>
        <td align="center">
          {if $form_info.is_active == 'no'}
            <span class="red">{$LANG.word_offline}</span>
          {else}
            <span class="light_green">{$LANG.word_online}</span>
          {/if}
        </td>
        <td align="center">
          {assign var=form_num_submissions_key value="form_`$form_id`_num_submissions"}
          {assign var=num_submissions value=$SESSION.$form_num_submissions_key}
          ({$num_submissions})&nbsp;<a href="forms/index.php?form_id={$form_id}">{$LANG.word_view|upper}</a>
        </td>
      </tr>
    {/foreach}

    </table>

  {/if}

{ft_include file='footer.tpl'}