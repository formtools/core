{ft_include file='header.tpl'}

  <table cellpadding="0" cellspacing="0" class="margin_bottom_large">
  <tr>
    <td width="45"><a href="../"><img src="{$images_url}/icon_forms.gif" border="0" width="34" height="34" /></a></td>
    <td class="title"><a href="../">{$LANG.word_forms|upper}</a>: {$LANG.phrase_add_form|upper}</td>
  </tr>
  </table>

  <table cellpadding="0" cellspacing="0" width="100%" class="add_form_nav">
  <tr>
    <td class="selected"><a href="step1.php">{$LANG.word_checklist}</a></td>
    <td class="selected"><a href="step2.php">{$LANG.phrase_form_info}</a></td>
    <td class="selected"><a href="step3.php">{$LANG.phrase_test_submission}</a></td>
    <td class="selected"><a href="step4.php">{$LANG.phrase_database_setup}</a></td>
    <td class="selected">{$LANG.phrase_field_types}</td>
    <td class="unselected">{$LANG.phrase_finalize_form}</td>
  </tr>
  </table>

  <br />

  <div class="subtitle underline margin_bottom_large">
    5. {$LANG.phrase_field_types|upper}
  </div>

  <div>
	  <span style="float: right">
	    <table cellspacing="0" cellpadding="0" style="height:40px">
	    <tr>
	      <td class="pad_left" align="center">
	        <input type="button" id="smart_fill_button" value="{$LANG.phrase_smart_fill}" class="light_grey bold"
	          onclick="sf_ns.smart_fill()" disabled /><br />
	      </td>
	      <td width="40" align="right">
	        <div id="ajax_activity"><img src="{$images_url}/ajax_activity.gif" /></div>
	        <div id="ajax_no_activity" style="display:none"><img src="{$images_url}/ajax_no_activity.gif" /></div>
	      </td>
	    </tr>
	    </table>
	  </span>
	  <p>
	  This (optional) step is for standard HTML-based web forms, letting you quickly find and catalog your form field types.
	  Any issues that occur will be displayed in the Action Needed columns. You will be allowed to proceed when all actions
	  have been resolved.
	  </p>
	  <p>
	  If your form is Flash-based, dynamically built with javascript or anything else other than plain HTML, you can just
	  click the Skip Step button to proceed. These settings can all be added later.
	  </p>
  </div>

  {ft_include file="messages.tpl"}

  <form action="{$same_page}" method="post">
    <input type="hidden" name="form_id" value="{$form_id}" />

    <div id="main_field_table">

	    <table class="list_table" width="100%" cellpadding="0" cellspacing="1">
      <tr>
        <th>{$LANG.phrase_display_name}</th>
        <th>{$LANG.phrase_field_type}</th>
        <th class="nowrap">{$LANG.phrase_action_needed}</th>
        <th width="100" class="nowrap">{$LANG.word_options|upper}</th>
      </tr>

      {foreach from=$form_fields item=field name=row}
        {assign var=row_count value=$smarty.foreach.row.iteration}
        {assign var=field_id value=$field.field_id}

        {if $field.field_type != "system"}
	        <tr style="{$style}">
	          <td class="blue pad_left_small" width="250" id="field_{$field_id}_title">{$field.field_title}</td>
	          <td class="pad_left_small" width="80">
							<input type="hidden" id="field_{$field_id}_name" value="{$field.field_name}" />

							{* TODO need to prefill file fields to the appropriate type *}
							{if $field.field_test_value == $LANG.word_file_b_uc} {/if}

	            <select name="field_{$field_id}_type" id="field_{$field_id}_type" tabindex="{$count}" disabled>
	              <option value="">Unknown</option>
	              <optgroup label="Standard Fields">
			            <option value="textbox"       >{$LANG.word_textbox}</option>
			            <option value="textarea"      >{$LANG.word_textarea}</option>
	                <option value="password"      >{$LANG.word_password}</option>
	                <option value="select"        >{$LANG.word_dropdown}</option>
	                <option value="multi-select"  >{$LANG.phrase_multi_select_dropdown}</option>
	                <option value="radio-buttons" >{$LANG.phrase_radio_buttons}</option>
	                <option value="checkboxes"    >{$LANG.word_checkboxes}</option>
	              </optgroup>
	              <optgroup label="Special Fields">
	                <option value="file">{$LANG.word_file}</option>
	                {if $image_manager_module_enabled}
	                  <option value="image">{$LANG.word_image}</option>
	                {/if}
	                <option value="wysiwyg">{$LANG.phrase_wysiwyg_field}</option>
	              </optgroup>
	            </select>

	          </td>
	          <td align="center" class="pad_left_small"><span id="field_{$field_id}_action" class="light_grey">&#8212;</span></td>
	          <td align="center"><span id="field_{$field_id}_options" class="light_grey">&#8212;</span></td>
	        </tr>
	      {/if}
      {/foreach}
      </table>

	    <table cellspacing="0" cellpadding="0" width="100%" class="margin_top_large">
	    <tr>
	      <td width="150" valign="top"><input type="button" id="refresh_page_button" value="{$LANG.phrase_refresh_page}" onclick="sf_ns.refresh_page()" /></td>
	      <td>
	        This reloads the contents of your form(s). Note: this will overwrite any changes you have made on this page.
	      </td>
	    </tr>
	    <tr>
	      <td width="150" valign="top"><input type="button" id="refresh_page_button" value="{$LANG.phrase_skip_step}" onclick="sf_ns.skip_step()" /></td>
	      <td>
	        In case you run into problems, click here to skip this step. All unresolved fields are set to use the default values. You can customize these fields later.
	      </td>
	    </tr>
	    </table>

      <div class="margin_top">
        <table cellspacing="0" cellpadding="0" width="100%">
        <tr>
          <td valign="top" width="100">
		        <input type="button" name="next_step" class="light_grey bold margin_top_small margin_right_large" id="next_step"
		          value="{$LANG.word_next_step_rightarrow}" onclick="sf_ns.submit_form()" disabled />
		      </td>
		      <td width="10"> </td>
		      <td width="40" valign="top">
		        <div id="ajax_activity_bottom" style="display:none"><img src="{$images_url}/ajax_activity.gif" /></div>
		        <div id="ajax_no_activity_bottom"><img src="{$images_url}/ajax_no_activity.gif" /></div>
		      </td>
		      <td valign="top">
            <div id="next_step_message"></div>
		      </td>
		    </tr>
		    </table>
      </div>

    </div>


    <div id="review_field_options" style="display:none">

        <div class="margin_bottom_large pad_left_small pad_right_small" style="background-color: #efefef; border-top: 2px solid #cccccc; border-bottom: 2px solid #cccccc;">
          <span style="float:right">
            <span class="previous_field_link" id="review_field_options_previous_field_link"></span> |
            <span class="next_field_link" id="review_field_options_next_field_link"></span>
          </span>
          <a href="#" onclick="return sf_ns.show_page('main_field_table', null)">{$LANG.phrase_back_to_field_list}</a>
        </div>

        <p>
          <span class="blue large_text" id="review_field_options_field_title"></span>
        </p>

        <table cellpadding="1" cellspacing="1" width="100%">
	      <tr>
	        <td valign="top" class="bold" width="140">{$LANG.phrase_action_needed}</td>
	        <td><div id="review_field_options_action_needed"></div></td>
	      </tr>
	      <tr>
	        <td class="bold">{$LANG.phrase_field_orientation}</td>
	        <td id="review_field_options_field_orientation"></td>
	      </tr>
	      <tr>
	        <td class="bold" valign="top">{$LANG.phrase_field_options}</td>
	        <td>

	          <div id="field_option_buttons"></div>

            <table cellpadding="0" cellspacing="1" class="list_table" style="width:100%" id="review_field_options_table">
            <tbody>
              <tr><td></td></tr>
            </tbody>
            </table>

			      <p>
			        <span style="float:right"><input type="button" value="{$LANG.phrase_update_field}"
			          onclick="sf_ns.update_field('review_field_options_action_needed')" /></span>
			        <input type="button" value="{$LANG.phrase_add_row}" onclick="sf_ns.add_field_option()" />
			        <span id="review_options_values_to_text"><input type="button" value="{$LANG.phrase_field_values_to_display_values}"
			          onclick="sf_ns.set_display_values_from_field_values()" /></span>
			      </p>

	        </td>
	      </tr>
	      </table>

        <div class="margin_bottom_large pad_left_small pad_right_small" style="background-color: #efefef; border-top: 2px solid #cccccc; border-bottom: 2px solid #cccccc;">
          <span style="float:right">
            <span class="previous_field_link" id="review_field_options_previous_field_link2"></span> |
            <span class="next_field_link" id="review_field_options_next_field_link2"></span>
          </span>
          <a href="#" onclick="return sf_ns.show_page('main_field_table', null)">{$LANG.phrase_back_to_field_list}</a>
        </div>

      </div>


      <div id="multiple_fields_found" style="display:none">

        <div class="margin_bottom_large pad_left_small pad_right_small" style="background-color: #efefef; border-top: 2px solid #cccccc; border-bottom: 2px solid #cccccc;">
          <span style="float:right">
            <span class="previous_field_link" id="multiple_fields_found_previous_field_link"></span> |
            <span class="next_field_link" id="multiple_fields_found_next_field_link"></span>
          </span>
          <a href="#" onclick="return sf_ns.show_page('main_field_table', null)">{$LANG.phrase_back_to_field_list}</a>
        </div>

        <p>
          <span class="blue large_text" id="multiple_fields_found_field_title"></span>
        </p>

        <table cellpadding="1" cellspacing="1" width="100%">
	      <tr>
	        <td valign="top" class="bold" width="140">{$LANG.phrase_action_needed}</td>
	        <td><div id="multiple_fields_found_action_needed"></div></td>
	      </tr>
	      </table>

        <table cellpadding="0" cellspacing="1" class="list_table margin_top margin_bottom" style="width:100%" id="multiple_fields_found_table">
        <tbody>
          <tr><td></td></tr>
        </tbody>
        </table>

        <br />

        <div class="margin_bottom_large pad_left_small pad_right_small" style="background-color: #efefef; border-top: 2px solid #cccccc; border-bottom: 2px solid #cccccc;">
          <span style="float:right">
            <span class="previous_field_link" id="multiple_fields_found_previous_field_link2"></span> |
            <span class="next_field_link" id="multiple_fields_found_next_field_link2"></span>
          </span>
          <a href="#" onclick="return sf_ns.show_page('main_field_table', null)">{$LANG.phrase_back_to_field_list}</a>
        </div>

      </div>

      <div id="not_found" style="display:none">

        <div class="margin_bottom_large pad_left_small pad_right_small" style="background-color: #efefef; border-top: 2px solid #cccccc; border-bottom: 2px solid #cccccc;">
          <span style="float:right">
            <span class="previous_field_link" id="not_found_previous_field_link"></span> |
            <span class="next_field_link" id="not_found_next_field_link"></span>
          </span>
          <a href="#" onclick="return sf_ns.show_page('main_field_table', null)">{$LANG.phrase_back_to_field_list}</a>
        </div>

        <p>
          <span class="blue large_text" id="not_found_field_title"></span>
        </p>

        <table cellpadding="1" cellspacing="1" width="100%">
	      <tr>
	        <td valign="top" class="bold" width="140">{$LANG.phrase_action_needed}</td>
	        <td><div id="not_found_action_needed"></div></td>
	      </tr>
	      <tr>
	        <td>Field Type</td>
	        <td>
            <select id="not_found_field_type">
	            <option value="">{$LANG.phrase_please_select}</option>
			        <option value="textbox"       >{$LANG.word_textbox}</option>
			        <option value="textarea"      >{$LANG.word_textarea}</option>
	            <option value="password"      >{$LANG.word_password}</option>
	            <option value="file"          >{$LANG.word_file}</option>
	            <option value="select"        >{$LANG.word_dropdown}</option>
	            <option value="multi-select"  >{$LANG.phrase_multi_select_dropdown}</option>
	            <option value="radio-buttons" >{$LANG.phrase_radio_buttons}</option>
	            <option value="checkboxes"    >{$LANG.word_checkboxes}</option>
	          </select>
	        </td>
	      </tr>
	      <tr>
	        <td> </td>
	        <td>
		        <p>
		          <input type="button" value="{$LANG.word_update}" onclick="sf_ns.choose_field_type()" />
		          <input type="button" value="{$LANG.phrase_skip_field}" onclick="sf_ns.skip_field()" />
		        </p>
	        </td>
	      </tr>
	      </table>

        <div class="margin_bottom_large pad_left_small pad_right_small" style="background-color: #efefef; border-top: 2px solid #cccccc; border-bottom: 2px solid #cccccc;">
          <span style="float:right">
            <span class="previous_field_link" id="not_found_previous_field_link2"></span> |
            <span class="next_field_link" id="not_found_next_field_link2"></span>
          </span>
          <a href="#" onclick="return sf_ns.show_page('main_field_table', null)">{$LANG.phrase_back_to_field_list}</a>
        </div>

      </div>

    </form>

		{foreach from=$form_urls item=url name="row"}
		  {assign var=row_count value=$smarty.foreach.row.iteration}
		  <iframe name="form_{$row_count}_iframe" id="form_{$row_count}_iframe" src="{$g_root_url}/global/code/actions.php?action=smart_fill&scrape_method={$scrape_method}&url={$url}" style="width: 0px; height: 0px" frameborder="0" onload="sf_ns.log_form_page_as_loaded({$row_count})"></iframe>
		{/foreach}


  <div id="multiple_fields_not_found_single_page_form" style="display:none">
	  There were multiple fields that couldn't be found in the form page you specified. This is mostly
	  likely caused by one of the following:
	  <ol>
		  <li>You incorrectly entered your form URL.<br />
		    <b>Solution</b>: <a href="step2.php">Click here</a> to return to the Form Information page
		    to check your settings.</li>
		  <li>You changed your form(s) after making the test submission.<br />
		    <b>Solution</b>: <a href="step3.php?uninitialize=1">Click here</a> to put through another test submission.</li>
      <li>Your form is password protected and the script couldn't access the page.<br />
		    <b>Solution</b>: In another tab / window of this browser, log into your form then click the
		    Refresh Page button below to try to re-find the fields.</li>
		</ol>

		If none of the above solutions work, you may also want to try
		<a href="#" onclick="ft.display_message('ft_message', true, $('upload_files_text').innerHTML)">manually uploading your forms for processing</a>.
  </div>

  <div id="multiple_fields_not_found_multi_page_form" style="display:none">
	  There were multiple fields that couldn't be found in the form pages you specified. This is mostly
	  likely caused by one of these:
	  <ol>
		  <li>You entered one or more of the form URLs of your multi-page form incorrectly.<br />
		    <b>Solution</b>: <a href="step2.php">Click here</a> to return to the Form Information page
		    to check your settings.</li>
		  <li>You changed your form(s) after making the test submission.<br />
		    <b>Solution</b>: <a href="step3.php?uninitialize=1">Click here</a> to put through another test submission.</li>
      <li>One or more pages of your form are password protected and the script couldn't access the page.<br />
		    <b>Solution</b>: In another tab / window of this browser, log into your form then click the
		    Refresh Page button below to try to re-find the fields.</li>
		</ol>

		If none of the above solutions work, you may also want to try
		<a href="#" onclick="ft.display_message('ft_message', true, $('upload_files_text').innerHTML)">manually uploading your forms for processing</a>.
  </div>

  <div id="upload_files_text" style="display:none">
    If you have been unable to Smart Fill your fields, you may want to try an alternative solution: upload
    copies of your forms in the fields below.

    <form action="{$g_root_url}/global/code/actions.php?action=upload_scraped_pages_for_smart_fill"
      target="upload_files_iframe" method="post" enctype="multipart/form-data"
      onsubmit="return sf_ns.validate_upload_files(this)">
      <input type="hidden" name="num_pages" value="{$form_urls|@count}" />

	    <table cellspacing="0" cellpadding="0" class="margin_top margin_bottom">
	    {foreach from=$form_urls item=url name="row"}
	      {assign var=row_count value=$smarty.foreach.row.iteration}
		    <tr>
		      <td width="90">{$LANG.phrase_form_page} {$row_count}</td>
		      <td><input type="file" name="form_page_{$row_count}" /></td>
		    </tr>
	    {/foreach}
	    <tr>
	      <td> </td>
	      <td><input type="submit" value="{$LANG.phrase_upload_files}" class="margin_top_small" /></td>
	    </tr>
	    </table>

	  </form>
    Note: do <b>not</b> upload raw PHP pages (or other server-side code) - just upload the HTML versions. To get this,
    view and save the page from your web browser.
  </div>

  <iframe name="upload_files_iframe" id="upload_files_iframe" src="" style="width: 0px; height: 0px" frameborder="0"
    onload="sf_ns.log_files_as_uploaded()"></iframe>

{ft_include file='footer.tpl'}