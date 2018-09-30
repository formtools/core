{ft_include file='header.tpl'}

  <table cellpadding="0" cellspacing="0" class="margin_bottom_large">
  <tr>
    <td width="45"><a href="../"><img src="{$images_url}/icon_forms.gif" border="0" width="34" height="34" /></a></td>
    <td class="title"><a href="../">{$LANG.word_forms}</a> <span class="joiner">&raquo;</span> {$LANG.phrase_add_form}</td>
  </tr>
  </table>

  {if $max_forms_reached}
    <div class="notify margin_bottom_large">
      <div style="padding:6px">
        {$notify_max_forms_reached}
      </div>
    </div>
  {else}

	  <div class="margin_bottom_large">
	    {$LANG.text_choose_form_type}
	  </div>

	  <form action="{$same_page}" method="post">
	    <table width="100%">
	      <tr>
	        <td width="49%" valign="top">
	          <div class="grey_box add_form_select">
	            <span style="float:right"><input type="submit" name="internal" class="blue bold" value="{$LANG.word_select|upper}" /></span>
	            <div class="bold">{$LANG.word_internal}</div>
	            <div class="medium_grey">
	              {$LANG.text_internal_form_desc}
	            </div>
	          </div>
	        </td>
	        <td width="2%"> </td>
	        <td width="49%" valign="top">
	          <div class="grey_box add_form_select margin_bottom_large">
	            <span style="float:right"><input type="button" id="select_external" name="external" class="blue bold" value="{$LANG.word_select|upper}" /></span>
	            <div class="bold">{$LANG.word_external}</div>
	            <div class="medium_grey">
	              {$LANG.text_external_form_desc}
	            </div>
	          </div>
	        </td>
	      </tr>
	    </table>
	    {template_hook location="add_form_page"}
	  </form>

	  <div id="add_external_form_dialog" class="hidden">
	    <table width="100%">
	    <tr>
	      <td valign="top" width="65"><span class="margin_top_large popup_icon popup_type_info"></span></td>
	      <td>
	        <p>
	          {$LANG.text_add_form_step_1_text_1}
	        </p>
	        <ul>
	          <li>{$LANG.text_add_form_step_1_text_2}</li>
	          <li>{$LANG.text_add_form_step_1_text_3}</li>
	        </ul>
	        <p>
	          {$LANG.text_add_form_help_link}
	        </p>
	      </td>
	    </tr>
	    </table>
	  </div>

  {/if}

{ft_include file='footer.tpl'}
