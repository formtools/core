    <div class="previous_page_icon">
      <a href="edit.php?page=views"><img src="{$images_url}/up.jpg" title="{$LANG.phrase_previous_page}" alt="{$LANG.phrase_previous_page}" border="0" /></a>
    </div>

    <div class="underline margin_top_large">
      <div style="float:right; padding-right: 20px; margin-top: -4px;">{$previous_view_link} &nbsp; {$next_view_link}</div>
      <span class="subtitle">{$LANG.phrase_edit_view|upper}:</span> <span class="green">{$view_info.view_name}</span>
    </div>

    {ft_include file='messages.tpl'}

    <form method="post" id="edit_view_form" action="{$same_page}" onsubmit="return view_ns.process_form(this)">
      <input type="hidden" name="view_id" value="{$view_id}" />

      <div class="inner_tab_set">
        <div style="position:relative; height:20px">
          <div style="left:0%; width:25%;" id="inner_tab1" {if $edit_view_tab == 1}class="inner_tab_selected"{else}class="inner_tab_unselected"{/if}>
            <a href="#" onclick="return ft.change_inner_tab(1, 4, 'edit_view_tab')">{$LANG.word_main}</a>
          </div>
          <div style="left:25%; width:25%" id="inner_tab2" {if $edit_view_tab == 2}class="inner_tab_selected"{else}class="inner_tab_unselected"{/if}>
            <a href="#" onclick="return ft.change_inner_tab(2, 4, 'edit_view_tab')">{$LANG.word_fields}</a>
          </div>
          <div style="left:50%; width:25%" id="inner_tab3" {if $edit_view_tab == 3}class="inner_tab_selected"{else}class="inner_tab_unselected"{/if}>
            <a href="#" onclick="return ft.change_inner_tab(3, 4, 'edit_view_tab')">{$LANG.word_tabs}</a>
          </div>
          <div style="left:75%; width:25%;" id="inner_tab4" {if $edit_view_tab == 4}class="inner_tab_selected"{else}class="inner_tab_unselected"{/if}>
            <a href="#" onclick="return ft.change_inner_tab(4, 4, 'edit_view_tab')">{$LANG.word_filters}</a>
          </div>
        </div>

        <div class="inner_tab_content">
          <div id="inner_tab_content1" {if $edit_view_tab != 1}style="display:none"{/if}>
            {ft_include file="admin/forms/tab_edit_view_tab1.tpl"}
          </div>

          <div id="inner_tab_content2" {if $edit_view_tab != 2}style="display:none"{/if}>
            {ft_include file="admin/forms/tab_edit_view_tab2.tpl"}
          </div>

          <div id="inner_tab_content3" {if $edit_view_tab != 3}style="display:none"{/if}>
            {ft_include file="admin/forms/tab_edit_view_tab3.tpl"}
          </div>

          <div id="inner_tab_content4" {if $edit_view_tab != 4}style="display:none"{/if}>
            {ft_include file="admin/forms/tab_edit_view_tab4.tpl"}
          </div>
        </div>

      </div>

      <p>
        <input type="submit" name="update_view" value="{$LANG.phrase_update_view}" />
      </p>

    </form>
