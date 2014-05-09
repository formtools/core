  <div class="previous_page_icon">
    <a href="edit.php?page=fields&form_id={$form_id}"><img src="{$images_url}/up.jpg" title="{$LANG.phrase_previous_page}" alt="{$LANG.phrase_previous_page}" border="0" /></a>
  </div>

  <div class="underline margin_top_large">
    <div style="float:right; padding-right: 20px; margin-top: -4px;">{$previous_field_link} &nbsp; {$next_field_link}</div>
    <span class="subtitle">{$LANG.word_field_c|upper} {$field.field_title|upper}</span>
  </div>

  {ft_include file='messages.tpl'}

  <form action="{$same_page}?page=field_options&field_id={$field.field_id}" method="post">
	  <div class="box margin_bottom_large">
	    <table cellspacing="0" cellpadding="0">
	    <tr>
	      <td width="140">{$LANG.phrase_field_type}</td>
	      <td>

	        {if $field.field_type == "system"}
	          <select name="field_type" id="field_type" disabled>
	            <option value="system">{$LANG.phrase_system_field}</option>
	          </select>
	        {else}
		        <select name="field_type" id="field_type" onchange="fo_ns.select_field_type(this.value)">
		          <optgroup label="{$LANG.phrase_standard_fields}">
		            <option value="textbox"       {if $field.field_type == "textbox"}selected{/if}>{$LANG.word_textbox}</option>
		            <option value="textarea"      {if $field.field_type == "textarea"}selected{/if}>{$LANG.word_textarea}</option>
		            <option value="password"      {if $field.field_type == "password"}selected{/if}>{$LANG.word_password}</option>
		            <option value="select"        {if $field.field_type == "select"}selected{/if}>{$LANG.word_dropdown}</option>
		            <option value="multi-select"  {if $field.field_type == "multi-select"}selected{/if}>{$LANG.phrase_multi_select_dropdown}</option>
		            <option value="radio-buttons" {if $field.field_type == "radio-buttons"}selected{/if}>{$LANG.phrase_radio_buttons}</option>
		            <option value="checkboxes"    {if $field.field_type == "checkboxes"}selected{/if}>{$LANG.word_checkboxes}</option>
		          </optgroup>
		          <optgroup label="{$LANG.phrase_special_fields}">
		            <option value="file" {if $field.field_type == "file"}selected{/if}>{$LANG.word_file}</option>
		            {if $image_manager_module_enabled}
		              <option value="image" {if $field.field_type == "image"}selected{/if}>{$LANG.word_image}</option>
		            {/if}
		            <option value="wysiwyg" {if $field.field_type == "wysiwyg"}selected{/if}>{$LANG.phrase_wysiwyg_field}</option>
		          </optgroup>
		        </select>
		      {/if}
		      <input type="submit" name="update_field_type" id="update_field_type" value="{$LANG.word_update|upper}" disabled />
	      </td>
	    </tr>
	    </table>
	  </div>
  </form>

  <div id="field_settings">
	  {* display the appropriate options page. This is reloaded on page load only (it was originally JS, but I downgraded it
	     to server-side due to the complexity of namespacing the various field values, options etc & the need for future
	     extensibility *}
	  {if $field.field_type == "system"}
	    {ft_include file="admin/forms/field_types/system.tpl"}
	  {elseif $field.field_type == "textbox"}
	    {ft_include file="admin/forms/field_types/textbox.tpl"}
	  {elseif $field.field_type == "password"}
	    {ft_include file="admin/forms/field_types/password.tpl"}
	  {elseif $field.field_type == "textarea"}
	    {ft_include file="admin/forms/field_types/textarea.tpl"}
	  {elseif $field.field_type == "radio-buttons"}
	    {ft_include file="admin/forms/field_types/radios.tpl"}
	  {elseif $field.field_type == "checkboxes"}
	    {ft_include file="admin/forms/field_types/checkboxes.tpl"}
	  {elseif $field.field_type == "select"}
	    {ft_include file="admin/forms/field_types/select.tpl"}
	  {elseif $field.field_type == "multi-select"}
	    {ft_include file="admin/forms/field_types/multi_select.tpl"}
	  {elseif $field.field_type == "file"}
	    {ft_include file="admin/forms/field_types/file.tpl"}
	  {elseif $field.field_type == "date"}
	    {ft_include file="admin/forms/field_types/date.tpl"}
	  {elseif $field.field_type == "wysiwyg"}
	    {ft_include file="admin/forms/field_types/wysiwyg.tpl"}
	  {/if}
  </div>

  <div id="changed_field_settings" style="display:none">
    Please update the field type to edit the field settings.
  </div>