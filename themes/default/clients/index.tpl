{ft_include file='header.tpl'}

  <table cellpadding="0" cellspacing="0">
  <tr>
    <td width="45"><img src="{$images_url}/icon_forms.gif" width="34" height="34" /></td>
    <td class="title">{$LANG.word_forms}</td>
  </tr>
  </table>

  {ft_include file="messages.tpl"}

  {if $forms_page_default_message}
    <div class="margin_bottom_large">
      {$forms_page_default_message}
    </div>
  {/if}

  {* here, there are no forms assigned to this client *}
  {if count($num_client_forms) == 0}
    <b>{$LANG.text_client_no_forms}</b>
  {else}

    <div id="search_form" class=" margin_bottom_large">
      <form action="{$same_page}" method="post">
        <table cellspacing="2" cellpadding="0" id="search_form_table">
        <tr>
          <td class="blue" width="70">{$LANG.word_search}</td>
          <td>
            <input type="text" size="20" name="keyword" value="{$search_criteria.keyword|escape}" />
            <input type="submit" name="search_forms" value="{$LANG.word_search}" />
            <input type="button" name="reset" onclick="window.location='{$same_page}?reset=1'"
              {if $forms|@count < $num_client_forms}
                value="{$LANG.phrase_show_all} ({$num_client_forms})" class="bold"
              {else}
                value="{$LANG.phrase_show_all}" class="light_grey" disabled
              {/if} />
          </td>
        </tr>
        </table>
      </form>
    </div>

    {if $forms|@count == 0}
      <div class="notify yellow_bg">
        <div style="padding: 8px">
          {if $num_client_forms == 0}
            {$LANG.text_no_forms_found}
          {else}
            {$LANG.text_no_forms_found_search}
          {/if}
        </div>
      </div>
    {else}
	    <table class="list_table" cellpadding="1" cellspacing="1" style="width:600px">
	    <tr>
	      {assign var="up_down" value=""}
          {if     $search_criteria.order == "form_name-DESC"}
	        {assign var=order_col value="order=form_name-ASC"}
	        {assign var=up_down value="<img src=\"`$theme_url`/images/sort_down.gif\" />"}
	      {elseif $search_criteria.order == "form_name-ASC"}
	        {assign var=order_col value="order=form_name-DESC"}
	        {assign var=up_down value="<img src=\"`$theme_url`/images/sort_up.gif\" />"}
	      {else}
	        {assign var=order_col value="order=form_name-DESC"}
	      {/if}
          <th class="sortable_col{if $up_down} over{/if}">
            <a href="{$same_page}?{$order_col}">{$LANG.word_form} {$up_down}</a>
          </th>
	      </th>
	      <th width="80">{$LANG.word_status}</th>
	      <th width="100">{$LANG.word_submissions}</th>
	    </tr>

	    {* loop through all forms assigned to this client *}
	    {foreach from=$forms key=k item=form_info}
	      {assign var=form_id value=$form_info.form_id}

	      <tr style="height: 20px;">
	        <td class="pad_left_small">
              {if $form_info.form_type == "external"}
                {$form_info.form_name}
                <a href="{$form_info.form_url}" class="show_form" target="_blank" title="Open form in dialog window"></a>
              {else}
                {$form_info.form_name}
              {/if}
	        </td>
	        <td align="center">
	          {if $form_info.is_active == 'no'}
	            <span class="red">{$LANG.word_offline}</span>
	          {else}
	            <span class="light_green">{$LANG.word_online}</span>
	          {/if}
	        </td>
	        <td align="center">
              <div class="form_info_link">
  	            {assign var=form_num_submissions_key value="form_`$form_id`_num_submissions"}
	            {assign var=num_submissions value=$SESSION.$form_num_submissions_key}
	            <a href="forms/index.php?form_id={$form_id}">{$LANG.word_view|upper}<span class="num_submissions_box">{$num_submissions}</span></a>
              </div>
	        </td>
	      </tr>
	    {/foreach}

	    </table>

	  {/if}

  {/if}

{ft_include file='footer.tpl'}