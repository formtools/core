    <div class="previous_page_icon">
      <a href="edit.php?page=emails&form_id={$form_id}"><img src="{$images_url}/up.jpg" title="{$LANG.phrase_previous_page}" alt="{$LANG.phrase_previous_page}" border="0" /></a>
    </div>

    <div class="underline margin_top_large">
      <span class="subtitle">{$LANG.phrase_edit_email_template|upper}</span>
    </div>

    {ft_include file='messages.tpl'}

    <form method="post" id="edit_email_template_form" action="{$same_page}?page=edit_email"
      onsubmit="return page_ns.onsubmit_check_email_settings(this)">

      {* used for the JS, don't delete! *}
      <input type="hidden" name="form_id" id="form_id" value="{$form_id}" />
      <input type="hidden" name="email_id" id="email_id" value="{$email_id}" />
      <input type="hidden" name="num_recipients" id="num_recipients" value="{$template_info.recipients|@count}" />

      <div class="inner_tab_set">
        <div style="position:relative; height:20">
          <div style="left:0%;  width:20%;" id="inner_tab1" {if $edit_email_tab == 1}class="inner_tab_selected"{else}class="inner_tab_unselected"{/if}>
            <a href="#" onclick="return ft.change_inner_tab(1, 5, 'edit_email_tab')">{$LANG.word_configuration}</a>
          </div>
          <div style="left:20%; width:20%;" id="inner_tab2" {if $edit_email_tab == 2}class="inner_tab_selected"{else}class="inner_tab_unselected"{/if}>
            <a href="#" onclick="return ft.change_inner_tab(2, 5, 'edit_email_tab')">{$LANG.word_recipient_sp}</a>
          </div>
          <div style="left:40%; width:20%;" id="inner_tab3" {if $edit_email_tab == 3}class="inner_tab_selected"{else}class="inner_tab_unselected"{/if}>
            <a href="#" onclick="return ft.change_inner_tab(3, 5, 'edit_email_tab')">{$LANG.word_content}</a>
          </div>
          <div style="left:60%; width:20%;" id="inner_tab4" {if $edit_email_tab == 4}class="inner_tab_selected"{else}class="inner_tab_unselected"{/if}>
            <a href="#" onclick="return ft.change_inner_tab(4, 5, 'edit_email_tab')">{$LANG.word_test}</a>
          </div>
          <div style="left:80%; width:20%;" id="inner_tab5" {if $edit_email_tab == 5}class="inner_tab_selected"{else}class="inner_tab_unselected"{/if}>
            <a href="#" onclick="return ft.change_inner_tab(5, 5, 'edit_email_tab')">{$LANG.word_reference}</a>
          </div>
        </div>
        <div class="inner_tab_content">

          <div id="inner_tab_content1" {if $edit_email_tab != 1}style="display:none"{/if}>
            <br />
            {ft_include file="admin/forms/tab_edit_email_tab1.tpl"}
          </div>

          <div id="inner_tab_content2" {if $edit_email_tab != 2}style="display:none"{/if}>
            <br />
            {ft_include file="admin/forms/tab_edit_email_tab2.tpl"}
          </div>

          <div id="inner_tab_content3" {if $edit_email_tab != 3}style="display:none"{/if}>
            <br />
            {ft_include file="admin/forms/tab_edit_email_tab3.tpl"}
          </div>

          <div id="inner_tab_content4" {if $edit_email_tab != 4}style="display:none"{/if}>
            <br />
            {ft_include file="admin/forms/tab_edit_email_tab4.tpl"}
          </div>

          <div id="inner_tab_content5" {if $edit_email_tab != 5}style="display:none"{/if}>
            <br />
            {ft_include file="admin/forms/tab_edit_email_tab5.tpl"}
          </div>
        </div>
      </div>

      <p>
        <input type="submit" name="update_email_template" value="{$LANG.phrase_update_email_template}" />
      </p>

    </form>