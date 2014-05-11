    <div class="subtitle underline margin_top_large">{$LANG.word_files|upper}</div>

    {ft_include file='messages.tpl'}

    <div class="margin_bottom_large">
      {$LANG.text_default_file_settings_page}
    </div>

    <form action="{$same_page}" method="post" name="file_upload_settings_form">
      <input type="hidden" name="page" value="files" />

      <table cellpadding="0" cellspacing="1" class="list_table" width="100%">
      <tr>
        <td width="120" class="pad_left">{$LANG.phrase_upload_folder_path}</td>
        <td>
          <input type="hidden" name="original_file_upload_dir" value="{$settings.file_upload_dir}" />
          <table cellpadding="0" cellspacing="0" width="100%">
          <tr>
            <td><input type="text" name="file_upload_dir" id="file_upload_dir" value="{$settings.file_upload_dir}" style="width: 98%" /></td>
            <td width="180">
              <input type="button" value="{$LANG.phrase_test_folder_permissions}" onclick="ft.test_folder_permissions($('#file_upload_dir').val(), 'permissions_result')" style="width: 180px;" />
            </td>
          </tr>
          </table>
          <div id="permissions_result"></div>
        </td>
      </tr>
      <tr>
        <td class="pad_left">{$LANG.phrase_upload_folder_url}</td>
        <td>
          <table cellpadding="0" cellspacing="0" width="100%">
          <tr>
            <td><input type="text" name="file_upload_url" id="file_upload_url" value="{$settings.file_upload_url}" style="width: 98%" /></td>
            {if $allow_url_fopen}
              <td width="150"><input type="button" value="{$LANG.phrase_confirm_folder_url_match}" onclick="ft.test_folder_url_match($('#file_upload_dir').val(), $('#file_upload_url').val(), 'folder_match_message_id')" style="width: 180px;" /></td>
            {/if}
          </tr>
          </table>
          <div id="folder_match_message_id"></div>
        </td>
      </tr>
      <tr>
        <td class="pad_left">{$LANG.phrase_max_file_size}</td>
        <td>
          <select name="file_upload_max_size">
            {if $max_filesize >= 20}<option value="20"   {if $settings.file_upload_max_size == 20}selected{/if}>20 KB</option>{/if}
            {if $max_filesize >= 50}<option value="50"   {if $settings.file_upload_max_size == 50}selected{/if}>50 KB</option>{/if}
            {if $max_filesize >= 100}<option value="100"  {if $settings.file_upload_max_size == 100}selected{/if}>100 KB</option>{/if}
            {if $max_filesize >= 200}<option value="200"  {if $settings.file_upload_max_size == 200}selected{/if}>200 KB</option>{/if}
            {if $max_filesize >= 300}<option value="300"  {if $settings.file_upload_max_size == 300}selected{/if}>300 KB</option>{/if}
            {if $max_filesize >= 500}<option value="500"  {if $settings.file_upload_max_size == 500}selected{/if}>1/2 MB</option>{/if}
            {if $max_filesize >= 1000}<option value="1000" {if $settings.file_upload_max_size == 1000}selected{/if}>1 MB</option>{/if}
            {if $max_filesize >= 2000}<option value="2000" {if $settings.file_upload_max_size == 2000}selected{/if}>2 MB</option>{/if}
            {if $max_filesize >= 3000}<option value="3000" {if $settings.file_upload_max_size == 2000}selected{/if}>3 MB</option>{/if}
            {if $max_filesize >= 5000}<option value="5000" {if $settings.file_upload_max_size == 5000}selected{/if}>5 MB</option>{/if}
            {if $max_filesize >= 10000}<option value="10000" {if $settings.file_upload_max_size == 10000}selected{/if}>10 MB</option>{/if}
            {if $max_filesize > 5000}<option value="{$max_filesize}" {if $settings.file_upload_max_size == $max_filesize}selected{/if}>{$max_filesize/1000} MB</option>{/if}
          </select>
          <span class="pad_left light_grey">{$LANG.phrase_php_ini_max_allowed_upload_size_c} {$max_filesize/1000} MB</span>

        </td>
      </tr>
      <tr>
        <td class="pad_left">{$LANG.phrase_permitted_file_types}</td>
        <td>

          <table cellspacing="0" cellpadding="0">
          <tr>
            <td width="90" class="subpanel">
              <div class="bold nowrap">{$LANG.phrase_images_media}</div>
              <input type="checkbox" name="file_upload_filetypes[]" value="bmp" id="bmp" {if "bmp"|in_array:$file_upload_filetypes}checked="checked"{/if} />
                <label for="bmp">bmp</label><br />
              <input type="checkbox" name="file_upload_filetypes[]" value="gif" id="gif" {if "gif"|in_array:$file_upload_filetypes}checked="checked"{/if} />
                <label for="gif">gif</label><br />
              <input type="checkbox" name="file_upload_filetypes[]" value="jpg,jpeg" id="jpg" {if "jpg"|in_array:$file_upload_filetypes}checked="checked"{/if} />
                <label for="jpg">jpg / jpeg</label><br />
              <input type="checkbox" name="file_upload_filetypes[]" value="png" id="png" {if "png"|in_array:$file_upload_filetypes}checked="checked"{/if} />
                <label for="png">png</label><br />
              <input type="checkbox" name="file_upload_filetypes[]" value="avi" id="avi" {if "avi"|in_array:$file_upload_filetypes}checked="checked"{/if} />
                <label for="avi">avi</label><br />
              <input type="checkbox" name="file_upload_filetypes[]" value="mp3" id="mp3" {if "mp3"|in_array:$file_upload_filetypes}checked="checked"{/if} />
                <label for="mp3">mp3</label><br />
              <input type="checkbox" name="file_upload_filetypes[]" value="mp4" id="mp4" {if "mp4"|in_array:$file_upload_filetypes}checked="checked"{/if} />
                <label for="mp4">mp4</label>
            </td>
            <td valign="top" width="90" class="subpanel">
              <div class="bold nowrap">{$LANG.word_web}</div>
              <input type="checkbox" name="file_upload_filetypes[]" value="css" id="css" {if "css"|in_array:$file_upload_filetypes}checked="checked"{/if} />
                <label for="css">css</label><br />
              <input type="checkbox" name="file_upload_filetypes[]" value="js" id="js" {if "js"|in_array:$file_upload_filetypes}checked="checked"{/if} />
                <label for="js">js</label><br />
              <input type="checkbox" name="file_upload_filetypes[]" value="html,htm" id="html" {if "js"|in_array:$file_upload_filetypes}checked="checked"{/if} />
                <label for="html">htm / html</label>
            </td>
            <td valign="top" width="90" class="subpanel">
              <div class="bold nowrap">{$LANG.word_data}</div>
              <input type="checkbox" name="file_upload_filetypes[]" value="doc" id="doc" {if "doc"|in_array:$file_upload_filetypes}checked="checked"{/if} />
                <label for="doc">doc</label><br />
              <input type="checkbox" name="file_upload_filetypes[]" value="rtf" id="rtf" {if "rtf"|in_array:$file_upload_filetypes}checked="checked"{/if} />
                <label for="rtf">rtf</label><br />
              <input type="checkbox" name="file_upload_filetypes[]" value="txt" id="txt" {if "txt"|in_array:$file_upload_filetypes}checked="checked"{/if} />
                <label for="txt">txt</label><br />
              <input type="checkbox" name="file_upload_filetypes[]" value="pdf" id="pdf" {if "pdf"|in_array:$file_upload_filetypes}checked="checked"{/if} />
                <label for="pdf">pdf</label><br />
              <input type="checkbox" name="file_upload_filetypes[]" value="xml" id="xml" {if "xml"|in_array:$file_upload_filetypes}checked="checked"{/if} />
                <label for="xml">xml</label><br />
              <input type="checkbox" name="file_upload_filetypes[]" value="csv" id="csv" {if "csv"|in_array:$file_upload_filetypes}checked="checked"{/if} />
                <label for="csv">csv</label><br />
            </td>
            <td valign="top" width="90" class="subpanel">
              <div class="bold nowrap">{$LANG.word_misc}</div>
              <input type="checkbox" name="file_upload_filetypes[]" value="zip" id="zip" {if "zip"|in_array:$file_upload_filetypes}checked="checked"{/if} />
                <label for="zip">zip</label><br />
              <input type="checkbox" name="file_upload_filetypes[]" value="tar,tar.gz" id="tar" {if "tar"|in_array:$file_upload_filetypes}checked="checked"{/if} />
                <label for="tar">tar / tar.gz</label><br />
              <input type="checkbox" name="file_upload_filetypes[]" value="swf" id="swf" {if "swf"|in_array:$file_upload_filetypes}checked="checked"{/if} />
                <label for="swf">swf</label><br />
              <input type="checkbox" name="file_upload_filetypes[]" value="fla" id="fla" {if "fla"|in_array:$file_upload_filetypes}checked="checked"{/if} />
                <label for="fla">fla</label>
            </td>
          </tr>
          </table>

          <div class="pad_left_small pad_top">
            <div>{$LANG.word_other_c}<input type="text" name="file_upload_filetypes_other" value="{$other_filetypes|escape}" style="width: 480px" /></div>
            <div class="pad_top_small medium_grey">{$LANG.text_file_extension_info}</div>
          </div>

        </td>
      </tr>
      {template_hook location="admin_settings_files_bottom"}
      </table>

      <p>
        <input type="submit" name="update_files" value="{$LANG.word_update}" />
      </p>
    </form>
