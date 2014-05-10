{ft_include file="header.tpl"}

  <table cellpadding="0" cellspacing="0" class="margin_bottom_large">
  <tr>
    <td width="45"><a href="../"><img src="{$images_url}/icon_forms.gif" border="0" width="34" height="34" /></a></td>
    <td class="title">
      <a href="../">{$LANG.word_forms}</a> <span class="joiner">&raquo;</span>
      <a href="./">{$LANG.phrase_add_form}</a> <span class="joiner">&raquo;</span>
      {$LANG.phrase_external_form}
    </td>
  </tr>
  </table>

  <table cellpadding="0" cellspacing="0" width="100%" class="add_form_nav">
  <tr>
    <td class="selected"><a href="step1.php">{$LANG.word_start}</a></td>
    <td class="selected"><a href="step2.php">{$LANG.phrase_form_info}</a></td>
    <td class="selected"><a href="step3.php">{$LANG.phrase_test_submission}</a></td>
    <td class="selected">{$LANG.phrase_database_setup}</td>
    <td class="unselected">{$LANG.phrase_field_types}</td>
    <td class="unselected">{$LANG.phrase_finalize_form}</td>
  </tr>
  </table>

  <br />

  <div>
    <div class="subtitle underline">{$LANG.phrase_db_setup_page_4|upper}</div>

    {ft_include file="messages.tpl"}

    <div class="margin_bottom_large">
      {$LANG.text_add_form_step_3_para_1}
    </div>
    <div class="margin_bottom_large">
      {$LANG.text_add_form_step_3_para_2}
    </div>

    <form action="{$same_page}" method="post" id="step4_form">
      <input type="hidden" name="form_id" value="{$form_id}" />

      <div class="sortable groupable add_form_step4" id="{$sortable_id}">
        <input type="hidden" class="tabindex_col_selectors" value=".rows .col3 input|.rows .col5 select" />
        <ul class="header_row">
          <li class="col1">{$LANG.word_order}</li>
          <li class="col2">{$LANG.phrase_form_field_name}</li>
          <li class="col3">{$LANG.phrase_display_name}<span class="pad_right">&nbsp;</span><input type="button" class="bold" value="{$LANG.phrase_smart_fill}" onclick="page_ns.smart_fill()" /></li>
          <li class="col4">{$LANG.phrase_sample_data}</li>
          <li class="col5 colN del"></li>
        </ul>
        <div class="clear"></div>

        <ul class="rows">
        {assign var=previous_item value=""}
        {foreach from=$form_fields item=field name=row}
          {assign var=row_count value=$smarty.foreach.row.iteration}
          {assign var=field_id value=$field.field_id}
          {assign var=style value=""}

          {if $field.is_new_sort_group == "yes"}
            {if $previous_item != ""}
              </div>
              <div class="clear"></div>
            </li>
            {/if}
            <li class="sortable_row{if $smarty.foreach.row.last} rowN{/if}">
              {assign var=next_item_is_new_sort_group value=$form_fields[$smarty.foreach.row.iteration].is_new_sort_group}
              <div class="row_content{if $next_item_is_new_sort_group == 'no'} grouped_row{/if}">
          {/if}

          {assign var=previous_item value=$i}

            <div class="row_group{if $field.is_system_field == "yes"} system_field{/if}{if $smarty.foreach.row.last} rowN{/if}">
              <input type="hidden" class="sr_order" value="{$field_id}" />
              <ul>
                <li class="col1 sort_col">{$row_count}</li>
                <li class="col2 blue">{$field.field_name}</li>
                <li class="col3"><input type="text" name="field_{$field_id}_display_name" id="field_{$field_id}_display_name" value="{$field.field_title}" /></li>
                <li class="col4 ellipsis">
                  {if $field.is_system_field == "yes"}
                    <span class="light_grey">&#8212;</span>
                  {else}
                    {$field.field_test_value|default:"&nbsp;"}
                  {/if}
                </li>
                <li class="col5 colN{if $field.is_system_field == "no"} del{/if}"></li>
              </ul>
              <div class="clear"></div>
            </div>

          {if $smarty.foreach.row.last}
            </div>
            <div class="clear"></div>
          </li>
          {/if}

        {/foreach}
        </ul>
        <div class="clear"></div>
      </div>

      <p>
        <input type="submit" name="next_step" class="next_step" value="{$LANG.word_next_step_rightarrow}"
          onclick="return page_ns.validate_fields()"/>
      </p>

    </form>

  </div>

{ft_include file="footer.tpl"}
