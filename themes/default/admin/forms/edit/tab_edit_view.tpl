    <div class="previous_page_icon">
      <a href="edit.php?page=views"><img src="{$images_url}/up.jpg" title="{$LANG.phrase_previous_page}" alt="{$LANG.phrase_previous_page}" border="0" /></a>
    </div>

    <div class="underline margin_top_large">
      <div style="float:right; padding-right: 20px; margin-top: -4px;">{$previous_view_link} &nbsp; {$next_view_link}</div>
      <span class="subtitle"><a href="edit.php?page=views">{$LANG.word_views|upper}</a></span> &raquo; <span>{$view_info.view_name}</span>
    </div>

    {ft_include file='messages.tpl'}

    <form method="post" id="edit_view_form" action="{$same_page}" onsubmit="return view_ns.process_form(this)">
      <input type="hidden" name="view_id" value="{$view_id}" />

      <div class="inner_tabset" id="edit_view">
        <div class="tab_row fiveCols">
          <div class="inner_tab1{if $edit_view_tab == 1} selected{/if}">{$LANG.word_general}</div>
          <div class="inner_tab2{if $edit_view_tab == 2} selected{/if}">{$LANG.word_columns}</div>
          <div class="inner_tab3{if $edit_view_tab == 3} selected{/if}">{$LANG.word_fields}</div>
          <div class="inner_tab4{if $edit_view_tab == 4} selected{/if}">{$LANG.word_tabs}</div>
          <div class="inner_tab5{if $edit_view_tab == 5} selected{/if}">{$LANG.word_filters}</div>
        </div>
        <div class="inner_tab_content">
          <div class="inner_tab_content1" {if $edit_view_tab != 1}style="display:none"{/if}>
            {ft_include file="admin/forms/tab_edit_view__main.tpl"}
          </div>
          <div class="inner_tab_content2" {if $edit_view_tab != 2}style="display:none"{/if}>
            {ft_include file="admin/forms/tab_edit_view__list_page.tpl"}
          </div>
          <div class="inner_tab_content3" {if $edit_view_tab != 3}style="display:none"{/if}>
            {ft_include file="admin/forms/tab_edit_view__fields.tpl"}
          </div>
          <div class="inner_tab_content4" {if $edit_view_tab != 4}style="display:none"{/if}>
            {ft_include file="admin/forms/tab_edit_view__tabs.tpl"}
          </div>
          <div class="inner_tab_content5" {if $edit_view_tab != 5}style="display:none"{/if}>
            {ft_include file="admin/forms/tab_edit_view__filters.tpl"}
          </div>
        </div>
      </div>

      <p>
        <input type="submit" name="update_view" value="{$LANG.phrase_update_view}" />
      </p>
    </form>