{ft_include file='header.tpl'}

  <div class="edit_submission">
    <table cellpadding="0" cellspacing="0" width="100%">
    <tr>
      <td><span class="title">{$edit_submission_page_label}</span></td>
      <td align="right">
        <div style="float:right; padding-left: 4px;">
          <a href="edit.php?form_id={$form_id}"><img src="{$images_url}/admin_view.png" border="0" alt="{$LANG.phrase_edit_form}"
            title="{$LANG.phrase_edit_form}" width="48" height="23" /></a>
        </div>
        <div class="views_dropdown">
        {views_dropdown form_id=$form_id submission_id=$submission_id selected=$view_id omit_hidden_views=true
          onchange="window.location='`$same_page`?form_id=`$form_id`&submission_id=`$submission_id`&view_id=' + this.value"
          open_html='<div class="views_dropdown">' close_html='</div>' hide_single_view=true}
      </div>
      </td>
    </tr>
    </table>

    <table cellpadding="0" cellspacing="0" class="pad_top_large pad_bottom_large">
    <tr>
      <td width="80" class="nowrap">{$previous_link_html}</td>
      <td width="150" class="nowrap">{$search_results_link_html}</td>
      <td>{$next_link_html}</td>
    </tr>
    </table>

    {template_hook location="admin_edit_submission_top"}

    {if $tabs|@count > 0}
      {ft_include file='tabset_open.tpl'}
    {/if}

    {ft_include file="messages.tpl"}

    <form action="edit_submission.php?form_id={$form_id}&submission_id={$submission_id}" method="post" id="edit_submission_form"
      name="edit_submission_form" enctype="multipart/form-data">
      {* hidden fields needed for JS - don't delete! *}
      <input type="hidden" name="form_id" id="form_id" value="{$form_id}" />
      <input type="hidden" name="submission_id" id="submission_id" value="{$submission_id}" />
      <input type="hidden" name="tab" id="tab" value="{$tab_number}" />

      {foreach from=$grouped_fields key=k item=curr_group}
        {assign var=group value=$curr_group.group}
        {assign var=fields value=$curr_group.fields}

        {if $group.group_name}
          <h3>{$group.group_name|upper}</h3>
        {/if}

        {if $fields|@count > 0}
          <table class="list_table" cellpadding="1" cellspacing="1" border="0" width="100%">
        {/if}

        {foreach from=$fields item=curr_field}
          {assign var=field_id value=$field.field_id}
          <tr>
            <td width="160" class="pad_left_small" valign="top">{$curr_field.field_title}
              {if $curr_field.is_required && $curr_field.is_editable == "yes"}<span class="req">*</span>{/if}
            </td>
            <td valign="top">
              {edit_custom_field form_id=$form_id submission_id=$submission_id field_info=$curr_field
                field_types=$field_types settings=$settings}
            </td>
          </tr>
        {/foreach}

        {if $fields|@count > 0}
          </table>
        {/if}

      {/foreach}

      <input type="hidden" name="field_ids" value="{$page_field_ids_str}" />

      {* if there are no fields in this tab/page, display a message to let the user know *}
      {if $page_field_ids|@count == 0}
        <div class="margin_bottom_large">{$LANG.notify_no_fields_in_tab}</div>
      {/if}

      <div style="position:relative">
        <span style="float:right">
          {* show the list of whatever email templates can be send from this page *}
          {display_email_template_dropdown form_id=$form_id view_id=$view_id submission_id=$submission_id}
        </span>
        {* only show the update button if there are editable fields in the tab *}
        {if $page_field_ids|@count > 0 && $tab_has_editable_fields}
          <input type="submit" name="update" value="{$LANG.word_update}" />
        {/if}
        {if $view_info.may_delete_submissions == "yes"}
          <input type="button" name="delete" value="{$LANG.word_delete}" class="red" onclick="return ms.delete_submission({$submission_id}, 'submissions.php')"/>
        {/if}
        {if $view_info.may_add_submissions == "yes"}
          <span class="button_separator">|</span>
          <input type="button" value="{eval var=$form_info.add_submission_button_label}" onclick="window.location='submissions.php?form_id={$form_id}&add_submission'" />
        {/if}
      </div>
    </form>

    {if $tabs|@count > 0}
      {ft_include file='tabset_close.tpl'}
    {/if}

    {template_hook location="admin_edit_submission_bottom"}
  </div>

{ft_include file='footer.tpl'}