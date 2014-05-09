    {ft_include file="messages.tpl"}

    <div class="margin_bottom_large">
      {$LANG.text_client_form_page}
    </div>

    <form method="post" name="client_forms" id="client_forms" action="{$same_page}" onsubmit="return cf_ns.check_fields(this)">
      <input type="hidden" name="client_id" value="{$client_id}" />
      <input type="hidden" name="num_forms" id="num_forms" value="0" />

      {template_hook location="admin_edit_client_forms_top"}

      <table class="list_table" id="client_forms_table" cellpadding="0" cellspacing="1">
      <tbody><tr>
        <th>{$LANG.word_form}</th>
        <th width="160">{$LANG.phrase_available_views}</th>
        <th width="90">{$LANG.word_action}</th>
        <th width="160">{$LANG.phrase_selected_views}</th>
        <th class="del"></th>
      </tr>

      {* loop through all forms to which this client has been assigned *}
      {foreach from=$client_forms item=form_row name=i}
        {assign var=form_info value=$form_row}
        {assign var=views value=$form_row.views}
        {assign var=row value=$smarty.foreach.i.iteration}

         <tr id="row_{$row}">
           <td valign="top">{forms_dropdown name_id="form_row_`$row`" include_blank_option=true default=$form_info.form_id onchange="cf_ns.select_form(`$row`, this.value)" class="selected_form"}</td>
           <td>
             <span id="row_{$row}_available_views_span">
               <select name="row_{$row}_available_views[]" id="row_{$row}_available_views" multiple size="4">
                 {* only display those Views to which this client is not already assigned *}
                 {foreach from=$all_form_views[$form_info.form_id] item=view_row name=vr}

                   {assign var=is_found value=false}
                   {foreach from=$views item=client_view_row name=vr2}
                     {if $client_view_row.view_id == $view_row.view_id}
                       {assign var=is_found value=true}
                     {/if}
                   {/foreach}

                   {if !$is_found}
                     <option value="{$view_row.view_id}">{$view_row.view_name}</option>
                   {/if}
                 {/foreach}
               </select>
             </span>
           </td>
           <td valign="center" align="center">
             <span id="row_{$row}_actions">
               <input type="button" onclick="return ft.move_options('row_{$row}_available_views', 'row_{$row}_selected_views')" value="{$LANG.word_add_uc_rightarrow}" />
               <input type="button" onclick="return ft.move_options('row_{$row}_selected_views', 'row_{$row}_available_views')" value="{$LANG.word_remove_uc_leftarrow}" />
             </span>
           </td>
           <td>
             <span id="row_{$row}_selected_views_span">
               <select name="row_{$row}_selected_views[]" id="row_{$row}_selected_views" multiple size="4">
               {foreach from=$views item=view_row name=vr}
                 <option value="{$view_row.view_id}">{$view_row.view_name}</option>
               {/foreach}
               </select>
             </span>
           </td>
           <td class="del" onclick="return cf_ns.delete_row({$row})"> </td>
         </tr>
      {/foreach}</tbody>
      </table>

      <script>
      cf_ns.num_rows = {$client_forms|@count};
      {* if there aren't any forms assigned to this client already, add a blank one ready for the admin to edit *}
      {if $client_forms|@count == 0}
        cf_ns.add_form_row();
      {/if}
      </script>

      <p>
        <a href="#" onclick="return cf_ns.add_form_row()">{$LANG.phrase_add_row}</a>
      </p>

      {template_hook location="admin_edit_client_forms_bottom"}

      <p>
        <input type="submit" name="update_client" value="{$LANG.word_update}" />
      </p>

    </form>