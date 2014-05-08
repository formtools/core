  <div class="margin_bottom_large">
    {$LANG.text_file_settings_page}
  </div>

  <form action="{$same_page}?{$query_string}" method="post" enctype="multipart/form-data"
    onsubmit="return file_settings_ns.submit(this)">
    <input type="hidden" name="field_id" value="{$field_id}" />
    <input type="hidden" name="num_settings" id="num_settings" value="{$field.settings|@count}" />

    <table cellpadding="0" cellspacing="1" width="100%" class="margin_bottom_large">
    <tr>
      <td width="200" class="pad_left_small"><label for="field_title">{$LANG.phrase_display_text}</label></td>
      <td><input type="text" name="field_title" id="field_title" value="{$field.field_title|escape}" style="width:99%" /></td>
    </tr>
    <tr>
      <td class="pad_left_small"><label for="field_size">{$LANG.phrase_db_field_size}</label></td>
      <td>
        <select name="field_size" id="field_size" tabindex="{$count}" disabled>
          <option {if $field.field_size == "tiny"}selected{/if} value="tiny">{$LANG.phrase_size_tiny}</option>
          <option {if $field.field_size == "small"}selected{/if} value="small">{$LANG.phrase_size_small}</option>
          <option {if $field.field_size == "medium"}selected{/if} value="medium">{$LANG.phrase_size_medium}</option>
          <option {if $field.field_size == "large"}selected{/if} value="large">{$LANG.phrase_size_large}</option>
          <option {if $field.field_size == "very_large"}selected{/if} value="very_large">{$LANG.phrase_size_very_large}	</option>
         </select>
      </td>
    </tr>
    <tr>
      <td class="pad_left_small"><label for="include_on_redirect">{$LANG.phrase_pass_on_to_redirect_page}</label></td>
      <td><input type="checkbox" name="include_on_redirect" id="include_on_redirect" value="yes" {if $field.include_on_redirect == "yes"}checked{/if} /></td>
    </tr>
    </table>

    <div id="customize_settings" {if $field.settings|@count == 0}style="display:none"{/if} >
      <table class="list_table" id="file_settings_table" cellspacing="1" cellpadding="1" width="100%">
      <tbody>
      <tr>
        <th width="150">{$LANG.word_setting}</th>
        <th>{$LANG.word_values}</th>
        <th width="60" class="del">{$LANG.word_delete|upper}</th>
      </tr>
      {foreach from=$field.settings key=k item=v name=row}
        {assign var='index' value=$smarty.foreach.row.index}
        {assign var='count' value=$smarty.foreach.row.iteration}

        <tr id="setting_row_{$count}">
          <td valign="top">
            <select name="row_{$count}" id="row_{$count}" onchange="file_settings_ns.select_setting({$count})">
               <option value="">{$LANG.phrase_please_select}</option>
              <option value="file_upload_folder"      {if $k == "file_upload_folder"}selected{/if}>{$LANG.phrase_file_upload_folder}</option>
              <option value="file_upload_max_size"    {if $k == "file_upload_max_size"}selected{/if}>{$LANG.phrase_max_file_size}</option>
              <option value="file_upload_filetypes"   {if $k == "file_upload_filetypes"}selected{/if}>{$LANG.phrase_permitted_file_types}</option>
            </select>
          </td>
          <td>
            <div id="values_{$count}">

              {if $k == "file_upload_max_size"}

                <select name="file_upload_max_size_{$count}">
                  {if $max_filesize >= 20}<option value="20"   {if $v == 20}selected{/if}>20 KB</option>{/if}
                  {if $max_filesize >= 50}<option value="50"   {if $v == 50}selected{/if}>50 KB</option>{/if}
                  {if $max_filesize >= 100}<option value="100"  {if $v == 100}selected{/if}>100 KB</option>{/if}
                  {if $max_filesize >= 200}<option value="200"  {if $v == 200}selected{/if}>200 KB</option>{/if}
                  {if $max_filesize >= 300}<option value="300"  {if $v == 300}selected{/if}>300 KB</option>{/if}
                  {if $max_filesize >= 500}<option value="500"  {if $v == 500}selected{/if}>1/2 MB</option>{/if}
                  {if $max_filesize >= 1000}<option value="1000" {if $v == 1000}selected{/if}>1 MB</option>{/if}
                  {if $max_filesize >= 2000}<option value="2000" {if $v == 2000}selected{/if}>2 MB</option>{/if}
                  {if $max_filesize >= 3000}<option value="3000" {if $v == 3000}selected{/if}>3 MB</option>{/if}
                  {if $max_filesize >= 5000}<option value="5000" {if $v == 5000}selected{/if}>5 MB</option>{/if}
                  {if $max_filesize > 5000}<option value="{$max_filesize}" {if $v == $max_filesize}selected{/if}>{$max_filesize/1000} MB</option>{/if}
                </select>

              {elseif $k == "file_upload_folder"}

                 <table cellpadding="0" cellspacing="1" width="100%">
                <tr>
                  <td class="pad_right nowrap" valign="top">{$LANG.phrase_upload_folder_path}</td>
                  <td>
                    <table cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                      <td><input type="text" name="file_upload_dir_{$count}" id="file_upload_dir_{$count}" value="{$v.file_upload_dir}" style="width: 98%" /></td>
                      <td width="150">
                        <input type="button" value="{$LANG.phrase_test_folder_permissions}"
                          onclick="ft.test_folder_permissions($('file_upload_dir_{$count}').value, 'permissions_result_{$count}')" style="width: 150px;" />
                      </td>
                    </tr>
                    </table>
                    <div id="permissions_result_{$count}"></div>
                  </td>
                </tr>
                <tr>
                  <td class="pad_right nowrap" valign="top">{$LANG.phrase_upload_folder_url}</td>
                  <td>
                    <table cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                      <td><input type="text" name="file_upload_url_{$count}" id="file_upload_url_{$count}" value="{$v.file_upload_url}" style="width: 98%" /></td>
                      {if $allow_url_fopen}
                        <td width='150'><input type="button" value="{$LANG.phrase_confirm_folder_url_match}" onclick="ft.test_folder_url_match($('file_upload_dir_{$count}').value, $('file_upload_url_{$count}').value, 'folder_match_message_id_{$count}')" style="width: 150px;" /></td>
                      {/if}
                    </tr>
                    </table>
                    <div id="folder_match_message_id_{$count}"></div>
                  </td>
                </tr>
                </table>

              {elseif $k == "file_upload_filetypes"}

                <input type="text" style="width: 98%" name="file_upload_filetypes_{$count}" value="{$v}" />

              {/if}
            </div>
          </td>
          <td valign="top" class="del" align="center"><a href="#" onclick="return file_settings_ns.remove_row({$count})">{$LANG.word_delete|upper}</a></td>
        </tr>

      {/foreach}

      </tbody>
      </table>

      <p>
        <a href="#" onclick="return file_settings_ns.add_row()">{$LANG.phrase_add_row}</a>
      </p>
    </div>


    <div id="default_settings" {if $field.settings|@count > 0}style="display:none"{/if}>
      <div class="notify yellow_bg" style="padding: 5px;">
        {$LANG.phrase_field_uses_default_settings}
        <input type="button" value="{$LANG.word_customize}" onclick="file_settings_ns.add_row()" />
      </div>
    </div>

    <p>
      <input type="submit" name="update_file_settings" value="{$LANG.word_update}" />
    </p>

  </form>


  {* ----------------------------------------------------------------------------------------------
    The rest of the page contains all HTML for each of the file settings. These chunk of HTML are
    inserted dynamically into the appropriate spot in the table when the user selects an option.
  ----------------------------------------------------------------------------------------------- *}
  <div style="display:none">

    <div id="section_file_settings">
      <select name="row_%%X%%" id="row_%%X%%">
         <option value="">{$LANG.phrase_please_select}</option>
        <option value="file_upload_folder">{$LANG.phrase_file_upload_folder}</option>
         <option value="file_upload_max_size">{$LANG.phrase_max_file_size}</option>
        <option value="file_upload_filetypes">{$LANG.phrase_permitted_file_types}</option>
      </select>
    </div>

    <div id="section_file_upload_max_size">
      <select name="file_upload_max_size_%%ROW%%">
        {if $max_filesize >= 20}<option value="20"   {if $settings.file_upload_max_size == 20}selected{/if}>20 KB</option>{/if}
        {if $max_filesize >= 50}<option value="50"   {if $settings.file_upload_max_size == 50}selected{/if}>50 KB</option>{/if}
        {if $max_filesize >= 100}<option value="100"  {if $settings.file_upload_max_size == 100}selected{/if}>100 KB</option>{/if}
        {if $max_filesize >= 200}<option value="200"  {if $settings.file_upload_max_size == 200}selected{/if}>200 KB</option>{/if}
        {if $max_filesize >= 300}<option value="300"  {if $settings.file_upload_max_size == 300}selected{/if}>300 KB</option>{/if}
        {if $max_filesize >= 500}<option value="500"  {if $settings.file_upload_max_size == 500}selected{/if}>1/2 MB</option>{/if}
        {if $max_filesize >= 1000}<option value="1000" {if $settings.file_upload_max_size == 1000}selected{/if}>1 MB</option>{/if}
        {if $max_filesize >= 2000}<option value="2000" {if $settings.file_upload_max_size == 2000}selected{/if}>2 MB</option>{/if}
        {if $max_filesize >= 3000}<option value="3000" {if $settings.file_upload_max_size == 3000}selected{/if}>3 MB</option>{/if}
        {if $max_filesize >= 5000}<option value="5000" {if $settings.file_upload_max_size == 5000}selected{/if}>5 MB</option>{/if}
        {if $max_filesize > 5000}<option value="{$max_filesize}" {if $settings.file_upload_max_size == $max_filesize}selected{/if}>{$max_filesize/1000} MB</option>{/if}
      </select>
    </div>

    <div id="section_file_upload_folder">
       <table cellpadding="0" cellspacing="1" width="100%">
      <tr>
        <td class="pad_right nowrap" valign="top">{$LANG.phrase_upload_folder_path}</td>
        <td>
          <table cellpadding="0" cellspacing="0" width="100%">
          <tr>
            <td><input type="text" name="file_upload_dir_%%ROW%%" id="file_upload_dir_%%ROW%%" value="{$settings.file_upload_dir}" style="width: 98%" /></td>
            <td width="150">
              <input type="button" value="{$LANG.phrase_test_folder_permissions}"
                onclick="ft.test_folder_permissions($('file_upload_dir_%%ROW%%').value, 'permissions_result_%%ROW%%')" style="width: 150px;" />
            </td>
          </tr>
          </table>
          <div id="permissions_result_%%ROW%%"></div>
        </td>
      </tr>
      <tr>
        <td class="pad_right nowrap" valign="top">{$LANG.phrase_upload_folder_url}</td>
        <td>
          <table cellpadding="0" cellspacing="0" width="100%">
          <tr>
            <td><input type="text" name="file_upload_url_%%ROW%%" id="file_upload_url_%%ROW%%" value="{$settings.file_upload_url}" style="width: 98%" /></td>
            {if $allow_url_fopen}
              <td width="150"><input type="button" value="{$LANG.phrase_confirm_folder_url_match}" onclick="ft.test_folder_url_match($('file_upload_dir_%%ROW%%').value, $('file_upload_url_%%ROW%%').value, 'folder_match_message_id_%%ROW%%')" style="width: 150px;" /></td>
            {/if}
          </tr>
          </table>
          <div id="folder_match_message_id_%%ROW%%"></div>
        </td>
      </tr>
      </table>
    </div>

    <div id="section_file_upload_filetypes">
      <input type="text" style="width: 98%" name="file_upload_filetypes_%%ROW%%" value="{$file_upload_filetypes}" />
    </div>

  </div>