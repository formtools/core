{ft_include file='header.tpl'}

  <div style="width: 100%">

    <table cellpadding="0" cellspacing="0" width="100%">
    <tr>
      <td><span class="title">{$edit_submission_page_label}</span></td>
      <td align="right" valign="top">
        {display_edit_submission_view_dropdown form_id=$form_id view_id=$view_id submission_id=$submission_id
          account_id=$SESSION.account.account_id}
      </td>
    </tr>
    </table>

    <table cellpadding="0" cellspacing="0" class="pad_top_large pad_bottom_large">
    <tr>
      <td width="80">{$previous_link_html}</td>
      <td width="150">{$search_results_link_html}</td>
      <td>{$next_link_html}</td>
    </tr>
    </table>

    {template_hook location="client_edit_submission_top"}

    {if $tabs|@count > 0}
      {ft_include file='tabset_open.tpl'}
    {/if}

    {ft_include file="messages.tpl"}

    <form action="edit_submission.php" method="post" name="edit_submission_form" enctype="multipart/form-data">
      <input type="hidden" name="form_id" id="form_id" value="{$form_id}" /> {* needed for JS - don't delete! *}
      <input type="hidden" name="submission_id" value="{$submission_id}" />
      <input type="hidden" name="tab" value="{$tab_number}" />
      <input type="hidden" name="field_id" value="" />
      <input type="hidden" name="delete_file_type" value="" />

      <table class="list_table" cellpadding="1" cellspacing="1" border="0" width="100%">
      {* loop through the submission and display all the contents *}
      {foreach from=$submission_tab_fields key=k item=submission_field}
        {assign var=field_id value=$submission_field.field_id}

        <tr>
          <td width="150" class="pad_left_small">{$submission_field.field_title}</td>
          <td>

          {if $submission_field.field_type == "select"}

            {submission_dropdown name=$submission_field.col_name field_id=$field_id
              selected=$submission_field.content is_editable=$submission_field.is_editable}

          {elseif $submission_field.field_type == "radio-buttons"}

            {submission_radios name=$submission_field.col_name field_id=$field_id
              selected=$submission_field.content is_editable=$submission_field.is_editable}

          {elseif $submission_field.field_type == "checkboxes"}

            {submission_checkboxes name=$submission_field.col_name field_id=$field_id
              selected=$submission_field.content is_editable=$submission_field.is_editable}

          {elseif $submission_field.field_type == "multi-select"}

            {submission_dropdown_multiple name=$submission_field.col_name field_id=$field_id
              selected=$submission_field.content is_editable=$submission_field.is_editable}

          {elseif $submission_field.field_type == "file"}

            <span id="field_{$field_id}_link" {if $submission_field.content == ""}style="display:none"{/if}>
              {display_file_field field_id=$field_id filename=$submission_field.content}

              {if $submission_field.is_editable == "yes"}
                <input type="button" class="pad_left_large" value="{$LANG.phrase_delete_file|upper}" onclick="ms.delete_submission_file({$field_id}, 'file', false)" />
              {/if}
            </span>

            <span id="field_{$field_id}_upload_field" {if $submission_field.content != ""}style="display:none"{/if}>
              {if $submission_field.is_editable == "yes"}
                <input type="file" name="{$submission_field.col_name}" />
              {/if}
            </span>

            <span id="file_field_{$field_id}_message_id"></span>

          {elseif $submission_field.field_type == "image"}

            {module_function name="display_image" type="main_thumb" extended_field_info=$image_field_info[$field_id] field_id=$field_id
              image_info_string=$submission_field.content}

          {elseif $submission_field.field_type == "system"}

            {if $submission_field.col_name == "submission_id"}

              <b>{$submission_field.content}</b>

            {elseif $submission_field.col_name == "ip_address"}

              {if $submission_field.is_editable == "yes"}
                <input type="text" style="width: 100px;" name="{$submission_field.col_name}" value="{$submission_field.content}" />
              {else}
                {$submission_field.content}
              {/if}

            {elseif $submission_field.col_name == "submission_date"}

              {if $submission_field.is_editable == "yes"}
                <table cellspacing="0" cellpadding="0">
                <tr>
                  <td><input type="text" style="width: 110px;" name="{$submission_field.col_name}" id="{$submission_field.col_name}" value="{$submission_field.content}" /></td>
                  <td><img src="{$theme_url}/images/calendar_icon.gif" id="date_image_{$field_id}" style="cursor:pointer" /></td>
                </tr>
                </table>
                <script type="text/javascript">
                {literal}Calendar.setup({{/literal}
                   inputField     :    "{$submission_field.col_name}",
                   showsTime      :    true,
                   timeFormat     :    "24",
                   ifFormat       :    "%Y-%m-%d %H:%M:00",
                   button         :    "date_image_{$field_id}",
                   align          :    "tr",
                   singleClick    :    true
                {literal}});{/literal}
                </script>
              {else}
                {$submission_field.content|custom_format_date:$SESSION.account.timezone_offset:$SESSION.account.date_format}
              {/if}

            {elseif $submission_field.col_name == "last_modified_date"}

              {if $submission_field.is_editable == "yes"}
                <table cellspacing="0" cellpadding="0">
                <tr>
                  <td><input type="text" style="width: 110px;" name="{$submission_field.col_name}" id="{$submission_field.col_name}" value="{$submission_field.content}" /></td>
                  <td><img src="{$theme_url}/images/calendar_icon.gif" id="date_image_{$field_id}" style="cursor:pointer" /></td>
                </tr>
                </table>
                <script type="text/javascript">
                {literal}Calendar.setup({{/literal}
                   inputField     :    "{$submission_field.col_name}",
                   showsTime      :    true,
                   timeFormat     :    "24",
                   ifFormat       :    "%Y-%m-%d %H:%M:00",
                   button         :    "date_image_{$field_id}",
                   align          :    "tr",
                   singleClick    :    true
                {literal}});{/literal}
                </script>
              {else}
                {$submission_field.content|custom_format_date:$SESSION.account.timezone_offset:$SESSION.account.date_format}
              {/if}

            {/if}

          {elseif $submission_field.field_type == "wysiwyg"}
            {if $submission_field.is_editable == "yes"}
              {* TODO: don't hardcode the height here.... *}
              <textarea name="{$submission_field.col_name}" id="field_{$field_id}_wysiwyg" style="width: 100%; height: 160px">{$submission_field.content}</textarea>
            {else}
              {$submission_field.content}
            {/if}

          {elseif $submission_field.field_type == "password"}
            {if $submission_field.is_editable == "yes"}
              <input type="password"  name="{$submission_field.col_name}" value="{$submission_field.content|escape}" style="width: 150px;" />
            {/if}
          {else}

            {if $submission_field.is_editable == "yes"}
              {if     $submission_field.field_size == "tiny"}
                <input type="text" name="{$submission_field.col_name}" value="{$submission_field.content|escape}" style="width: 50px;" />
              {elseif $submission_field.field_size == "small"}
                <input type="text" name="{$submission_field.col_name}" value="{$submission_field.content|escape}" style="width: 150px;" />
              {elseif $submission_field.field_size == "medium"}
                <input type="text" name="{$submission_field.col_name}" value="{$submission_field.content|escape}" style="width: 100%;" />
              {elseif $submission_field.field_size == "large" || $submission_field.field_size == "very_large"}
                <textarea name="{$submission_field.col_name}" style="width: 100%; height: 80px">{$submission_field.content}</textarea>
              {/if}
            {else}
              {$submission_field.content}
            {/if}

          {/if}

        </td>
      </tr>
      {/foreach}
      </table>

      <input type="hidden" name="field_ids" value="{$submission_tab_field_id_str}" />

      {* if there are no fields in this tab, display a message to let the user know *}
      {if $submission_tab_fields|@count == 0}
        <div>{$LANG.notify_no_fields_in_tab}</div>
      {/if}

      <br />

      <div style="position:relative">

        <span style="float:right">
          {* show the list of whatever email templates can be send from this page *}
          {display_email_template_dropdown form_id=$form_id view_id=$view_id submission_id=$submission_id}
         </span>

        {* only show the update button if there are editable fields in the tab *}
        {if $submission_tab_fields|@count > 0 && $tab_has_editable_fields}
          <input type="submit" name="update" value="{$LANG.word_update|upper}" />
        {/if}

        {if $view_info.may_delete_submissions == "yes"}
           <input type="button" name="delete" value="{$LANG.word_delete|upper}" class="red" onclick="return ms.delete_submission({$submission_id}, 'index.php')"/>
         {/if}

      </div>

    </form>

    {if $tabs|@count > 0}
      {ft_include file='tabset_close.tpl'}
    {/if}

    {template_hook location="client_edit_submission_bottom"}

  </div>

{ft_include file='footer.tpl'}