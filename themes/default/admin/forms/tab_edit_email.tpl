    <div class="previous_page_icon">
      <a href="edit.php?page=emails&form_id={$form_id}"><img src="{$images_url}/up.jpg" title="{$LANG.phrase_previous_page}" alt="{$LANG.phrase_previous_page}" border="0" /></a>
    </div>

    <div class="underline margin_top_large">
      <span class="subtitle">{$LANG.phrase_edit_email_template|upper}</span>
    </div>

    {ft_include file='messages.tpl'}

    <form method="post" id="edit_email_template_form" action="{$same_page}?page=edit_email">
      {* used by the JS, don't delete! *}
      <input type="hidden" name="form_id" id="form_id" value="{$form_id}" />
      <input type="hidden" name="email_id" id="email_id" value="{$email_id}" />
      <input type="hidden" name="num_recipients" id="num_recipients" value="{$template_info.recipients|@count}" />

      <div class="inner_tabset" id="edit_email_template">
        <div class="tab_row fourCols">
          <div class="inner_tab1{if $edit_email_tab == 1} selected{/if}">{$LANG.word_configuration}</div>
          <div class="inner_tab2{if $edit_email_tab == 2} selected{/if}">{$LANG.word_recipient_sp}</div>
          <div class="inner_tab3{if $edit_email_tab == 3} selected{/if}">{$LANG.word_content}</div>
          <div class="inner_tab4{if $edit_email_tab == 4} selected{/if}">{$LANG.word_test}</div>
        </div>
        <div class="inner_tab_content">
          <div class="inner_tab_content1" {if $edit_email_tab != 1}style="display:none"{/if}>
            {ft_include file="admin/forms/tab_edit_email_tab1.tpl"}
          </div>
          <div class="inner_tab_content2" {if $edit_email_tab != 2}style="display:none"{/if}>
            {ft_include file="admin/forms/tab_edit_email_tab2.tpl"}
          </div>
          <div class="inner_tab_content3" {if $edit_email_tab != 3}style="display:none"{/if}>
            {ft_include file="admin/forms/tab_edit_email_tab3.tpl"}
          </div>
          <div class="inner_tab_content4" {if $edit_email_tab != 4}style="display:none"{/if}>
            {ft_include file="admin/forms/tab_edit_email_tab4.tpl"}
          </div>
        </div>
      </div>

      <p>
        <input type="submit" name="update_email_template" value="{$LANG.phrase_update_email_template}" />
      </p>

    </form>