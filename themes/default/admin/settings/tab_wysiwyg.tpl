  <div class="subtitle underline margin_top_large">{$LANG.phrase_wysiwyg_editor|upper}</div>

  {ft_include file='messages.tpl'}

  <form action="{$same_page}" id="wysiwyg_config_form">
    <input type="hidden" name="page" value="wysiwyg" />

    <table cellspacing="0" cellpadding="1">
    <tr>
      <td width="170">{$LANG.word_toolbar}</td>
      <td>
        <select name="tinymce_toolbar" class="update_example">
          <option value="basic" {if $settings.tinymce_toolbar == "basic"}selected{/if}>{$LANG.word_basic}</option>
          <option value="simple" {if $settings.tinymce_toolbar == "simple"}selected{/if}>{$LANG.word_simple}</option>
          <option value="advanced" {if $settings.tinymce_toolbar == "advanced"}selected{/if}>{$LANG.word_advanced}</option>
          <option value="expert" {if $settings.tinymce_toolbar == "expert"}selected{/if}>{$LANG.word_expert}</option>
        </select>
      </td>
    </tr>
    <tr>
      <td>{$LANG.phrase_toolbar_location}</td>
      <td>
        <input type="radio" name="tinymce_toolbar_location" class="update_example" id="ttl1" value="top"
          {if $settings.tinymce_toolbar_location == "top"}checked{/if} /> <label for="ttl1">{$LANG.word_top}</label>
        <input type="radio" name="tinymce_toolbar_location" class="update_example" id="ttl2" value="bottom"
          {if $settings.tinymce_toolbar_location == "bottom"}checked{/if} /> <label for="ttl2">{$LANG.word_bottom}</label>
      </td>
    </tr>
    <tr>
      <td>{$LANG.phrase_toolbar_alignment}</td>
      <td>
        <input type="radio" name="tinymce_toolbar_align" class="update_example" id="tinymce_toolbar_align1" value="left"
          {if $settings.tinymce_toolbar_align == "left"}checked{/if} /> <label for="tinymce_toolbar_align1">{$LANG.word_left}</label>
        <input type="radio" name="tinymce_toolbar_align" class="update_example" id="tinymce_toolbar_align2" value="center"
          {if $settings.tinymce_toolbar_align == "center"}checked{/if} /> <label for="tinymce_toolbar_align2">{$LANG.word_center}</label>
        <input type="radio" name="tinymce_toolbar_align" class="update_example" id="tinymce_toolbar_align3" value="right"
          {if $settings.tinymce_toolbar_align == "right"}checked{/if} /> <label for="tinymce_toolbar_align3">{$LANG.word_right}</label>
      </td>
    </tr>
    <tr>
      <td>{$LANG.phrase_show_path_information}</td>
      <td>
        <input type="radio" name="tinymce_show_path" class="update_example" id="tinymce_show_path1" value="yes"
          {if $settings.tinymce_show_path == "yes"}checked{/if} /> <label for="tinymce_show_path1">{$LANG.word_yes}</label>
        <input type="radio" name="tinymce_show_path" class="update_example" id="tinymce_show_path2" value="no"
          {if $settings.tinymce_show_path == "no"}checked{/if} /> <label for="tinymce_show_path2">{$LANG.word_no}</label>
      </td>
    </tr>
    <tr>
      <td>&mdash; {$LANG.phrase_path_info_location}</td>
      <td>
        <input type="radio" name="tinymce_path_info_location" class="update_example" id="tinymce_path_info_location1" value="top"
          {if $settings.tinymce_path_info_location == "top"}checked{/if} {if $settings.tinymce_show_path == "no"}disabled{/if}
          /> <label for="tinymce_path_info_location1">{$LANG.word_top}</label>
        <input type="radio" name="tinymce_path_info_location" class="update_example" id="tinymce_path_info_location2" value="bottom"
          {if $settings.tinymce_path_info_location == "bottom"}checked{/if} {if $settings.tinymce_show_path == "no"}disabled{/if}
          /> <label for="tinymce_path_info_location2">{$LANG.word_bottom}</label>
      </td>
    </tr>
    <tr>
      <td>&mdash; {$LANG.phrase_allow_toolbar_resizing}</td>
      <td>
        <input type="radio" name="tinymce_resize" class="update_example" id="tinymce_resize1" value="yes"
          {if $settings.tinymce_resize == "yes"}checked{/if} {if $settings.tinymce_show_path == "no"}disabled{/if}
          /> <label for="tinymce_resize1">{$LANG.word_yes}</label>
        <input type="radio" name="tinymce_resize" class="update_example" id="tinymce_resize2" value="no"
          {if $settings.tinymce_resize == "no"}checked{/if} {if $settings.tinymce_show_path == "no"}disabled{/if}
          /> <label for="tinymce_resize2">{$LANG.word_no}</label>
      </td>
    </tr>
    {template_hook location="admin_settings_wysiwyg_bottom"}		
    </table>


    <p class="bold">{$LANG.phrase_example_editor}</p>

    <div>
      <textarea id="example" name="example" rows="8" cols="90" style="width: 100%">{$LANG.text_example_wysiwyg}</textarea>
    </div>

    <p>
      <input type="submit" name="update_wysiwyg" value="{$LANG.word_update|upper}" />
    </p>

  </form>