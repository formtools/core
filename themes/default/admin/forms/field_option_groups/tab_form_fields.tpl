  {ft_include file="messages.tpl"}

  {if $form_fields|@count == 0}

    <div class="notify margin_bottom_large">
      <div style="padding:6px">
        {$LANG.text_unused_field_option_group}
      </div>
    </div>

  {else}

	  <div class="margin_bottom_large">
	    {$LANG.text_used_field_option_group}
	  </div>

	  <table cellspacing="1" cellpadding="0" class="list_table margin_bottom_large">
	  <tr>
	    <th width="40"> </th>
	    <th>{$LANG.word_field}</th>
	    <th>{$LANG.word_form}</th>
	    <th width="100">{$LANG.phrase_edit_field|upper}</th>
	  </tr>
	  {foreach from=$form_fields item=field_info name=row}
	    {assign var=count value=$smarty.foreach.row.iteration}
		  <tr>
		    <td align="center" class="medium_grey">{$count}</td>
		    <td class="pad_left_small">{$field_info.field_title}</td>
		    <td class="pad_left_small">{$field_info.form_name}</td>
		    <td align="center"><a href="../edit.php?page=field_options&field_id={$field_info.field_id}&form_id={$field_info.form_id}">{$LANG.phrase_edit_field|upper}</a></td>
		  </tr>
	  {/foreach}
	  </table>

	{/if}