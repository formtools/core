  <form action="{$same_page}" method="post" onsubmit="return sf_ns.submit_update_field_option_group_page()">
    <input type="hidden" name="num_rows" id="num_rows" value="{$group_info.options|@count}" />

    {ft_include file='messages.tpl'}

    {if $num_fields_using_group > 1}
	    <div class="notify margin_bottom">
	      <div style="padding:6px">
	        {$text_field_option_group_used_by_fields}
	      </div>
	    </div>
	  {/if}

    <table cellspacing="1" cellpadding="1">
    <tr>
      <td valign="top" width="130" class="pad_left_small">{$LANG.phrase_group_name}</td>
      <td>
        <input type="text" name="group_name" id="group_name" style="width:300px" value="{$group_info.group_name|escape}" />
        <div class="light_grey">{$LANG.text_group_name_explanation}</div>
      </td>
    </tr>
    <tr>
      <td valign="top" class="pad_left_small">{$LANG.phrase_field_orientation}</td>
      <td>
        <input type="radio" name="field_orientation" id="o1" value="horizontal" {if $group_info.field_orientation == "horizontal"}checked{/if} />
          <label for="o1">{$LANG.word_horizontal}</label>
        <input type="radio" name="field_orientation" id="o2" value="vertical" {if $group_info.field_orientation == "vertical"}checked{/if} />
          <label for="o2">{$LANG.word_vertical}</label>
        <input type="radio" name="field_orientation" id="o3" value="na" {if $group_info.field_orientation == "na"}checked{/if} />
          <label for="o3">{$LANG.word_na}</label>

        <div class="light_grey">{$LANG.text_field_orientation_explanation}</div>
      </td>
    </tr>
    </table>

    <table cellspacing="1" cellpadding="0" id="field_options_table" class="list_table margin_bottom_large">
    <tbody>
      <tr>
        <th width="40"> </th>
        <th>{$LANG.phrase_field_value}</th>
        <th>{$LANG.phrase_display_text}</th>
        <th class="del" width="70">{$LANG.word_delete|upper}</th>
      </tr>
      {foreach from=$group_info.options item=option name=row}
        {assign var=count value=$smarty.foreach.row.iteration}
	      <tr id="row_{$count}">
	        <td class="medium_grey" align="center" id="field_option_{$count}_order">{$count}</td>
	        <td><input type="text" style="width:98%" name="field_option_value_{$count}" value="{$option.option_value|escape}" /></td>
	        <td><input type="text" style="width:98%" name="field_option_text_{$count}" value="{$option.option_name|escape}" /></td>
	        <td align="center" class="del"><a href="#" onclick="sf_ns.delete_field_option({$count})">{$LANG.word_delete|upper}</a></td>
	      </tr>
      {/foreach}
    </tbody>
    </table>

    <div class="margin_bottom_large">
      <input type="button" value="{$LANG.phrase_add_row}" onclick="sf_ns.add_field_option(null, null)" />
      <input type="submit" name="update" value="{$LANG.word_update}" />
    </div>

  </form>

    <div class="box">
      <div id="smart_fill_messages"></div>

	    <table cellspacing="1" cellpadding="0" width="100%" class="margin_bottom_large" height="40">
	    <tr>
	      <td> </td>
	      <td class="light_grey">{$LANG.phrase_form_field_name}</td>
	      <td class="light_grey">{$LANG.phrase_form_url}</td>
	      <td colspan="2"> </td>
	    </tr>
	    <tr>
	      <td nowrap>{$LANG.phrase_smart_fill_fields_from_c}</td>
	      <td><input type="text" id="smart_fill_source_form_field" style="width:150px" /></td>
	      <td><input type="text" id="smart_fill_source_url" style="width:250px" /></td>
	      <td><input type="button" value="{$LANG.phrase_smart_fill|upper}" onclick="sf_ns.smart_fill_field()" /></td>
	      <td width="50" align="center">
	        <div id="ajax_activity" style="display:none"><img src="{$images_url}/ajax_activity_yellow.gif" /></div>
	        <div id="ajax_no_activity"><img src="{$images_url}/ajax_no_activity_yellow.gif" /></div>
	      </td>
	    </tr>
	    </table>
    </div>

    <div class="margin_top_large">
      <div style="float:right"><a href="http://docs.formtools.org/userdoc/index.php?page=fog_editing" target="_blank">{$LANG.phrase_smart_fill_user_documentation}</a></div>
    </div>



  <div id="upload_files_text" style="display:none">
    We were unable to Smart Fill your field options. However, as an alternative, you can try uploading
    a copy of your form page in the field below. Note: do <b>not</b> upload raw PHP pages (or other
    server-side code) - just upload the HTML version. To get this, view and save the page from your web browser.

    <form action="{$g_root_url}/global/code/actions.php?action=upload_scraped_page_for_smart_fill"
      target="hidden_iframe" method="post" enctype="multipart/form-data"
      onsubmit="return sf_ns.validate_upload_file(this)">
      <input type="hidden" name="num_pages" value="1" />

	    <table cellspacing="0" cellpadding="0" class="margin_top margin_bottom">
	    <tr>
	      <td width="90">{$LANG.word_page}</td>
	      <td><input type="file" name="form_page_1" /></td>
	    </tr>
	    <tr>
	      <td> </td>
	      <td><input type="submit" value="{$LANG.phrase_upload_file}" class="margin_top_small" /></td>
	    </tr>
	    </table>
	  </form>
  </div>


  <iframe name="hidden_iframe" id="hidden_iframe" src="" style="width: 0px; height: 0px" frameborder="0"
    onload="sf_ns.log_form_page_as_loaded()"></iframe>

