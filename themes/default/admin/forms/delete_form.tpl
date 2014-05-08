{ft_include file='header.tpl'}

  <p>
    <span class="title">{$LANG.phrase_delete_form_c|upper}</span>
    <span class="subtitle">{$form_info.form_name}</span> (<b>{$form_id}</b>)
  </p>

  <div class="error yellow_bg">
    <div style="padding: 8px;">
      <b>{$LANG.word_warning_c}</b> {$LANG.text_delete_form_warning}
    </div>
  </div>

  {ft_include file="messages.tpl"}

  <form method="post" action="{$same_page}" onsubmit="return rsv.validate(this, rules)">
    <input type="hidden" name="form_id" value="{$form_id}" />
    <input type="checkbox" name="delete_form" id="delete_form" value="yes" />
    <label for="delete_form">{$LANG.text_confirm_delete_form}</label><br />

    {if $files_uploaded}
      <input type="checkbox" name="delete_files" id="delete_files" value="yes" />
      <label for="delete_files">{$LANG.text_delete_all_forms}</label>
      (<a href="#" onclick="page_ns.show_uploaded_files(); return false">{$LANG.phrase_view_uploaded_files}</a>)<br />
    {/if}

    <br />
    <input type="button" name="sss" value="{$LANG.phrase_return_form_list}" onclick="window.location='index.php'" />
    <input type="submit" name="sss2" value="{$LANG.phrase_delete_form|upper}" class="bold" />
  </form>

  {if $files_uploaded}
    <div id="uploaded_files" style="display: none;">
      <br />
      <hr size="1" />

      {assign var=has_at_least_one_file value=false}
      {foreach from=$file_field_hash key=field_id item=v}

         {foreach from=$files_uploaded.$field_id item=file}
           <a href="{$v[1]}/{$file}" target="_blank">{$file}</a><br />
           {assign var=has_at_least_one_file value=true}
         {/foreach}

      {/foreach}

      {if !$has_at_least_one_file}
        <span class="medium_grey">{$LANG.phrase_no_files_uploaded}</span>
      {/if}
    </div>
  {/if}

{ft_include file='footer.tpl'}