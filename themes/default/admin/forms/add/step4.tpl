{ft_include file="header.tpl"}

  <table cellpadding="0" cellspacing="0" class="margin_bottom_large">
  <tr>
    <td width="45"><a href="../"><img src="{$images_url}/icon_forms.gif" border="0" width="34" height="34" /></a></td>
    <td class="title"><a href="../">{$LANG.word_forms|upper}</a>: {$LANG.phrase_add_form|upper}</td>
  </tr>
  </table>

  <table cellpadding="0" cellspacing="0" width="100%" class="add_form_nav">
  <tr>
    <td class="selected"><a href="step1.php">{$LANG.word_checklist}</a></td>
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

    <form action="{$same_page}" method="post">
      <input type="hidden" name="form_id" value="{$form_id}" />

      <table class="list_table" width="100%" cellpadding="0" cellspacing="1">
      <tr>
        <th width="40">{$LANG.word_order}</th>
        <th>{$LANG.phrase_form_field_name}</td>
        <th nowrap>{$LANG.phrase_display_name}<span class="pad_right">&nbsp;</span><input type="button" class="bold" value="{$LANG.phrase_smart_fill}" onclick="page_ns.smart_fill()" /></th>
        <th>{$LANG.phrase_sample_data}</td>
        <th>{$LANG.phrase_field_size}</th>
        <th width="50" class="del">{$LANG.word_delete}</th>
      </tr>

      {foreach from=$form_fields item=field name=row}
        {assign var=row_count value=$smarty.foreach.row.iteration}
        {assign var=field_id value=$field.field_id}

        {assign var=style value=""}
        {if $field.field_type == "system"}
          {assign var=style value="background-color: #C6F1C9"} {* TODO Use a class instead *}
        {/if}

        {assign var=include_on_redirect value=""}
        {if $field.include_on_redirect == "yes"}
          {assign var=include_on_redirect value="checked"}
        {/if}

        <tr style="{$style}">
          <td align="center">
            <input type="hidden" name="field_{$field_id}" value="1" />
            <input type="text" name="field_{$field_id}_order" style="width: 30px" value="{$row_count}" tabindex="{$row_count}" />
          </td>
          <td class="blue pad_left_small">{$field.field_name}</td>
          <td class="blue pad_left_small">
            <input type="text" name="field_{$field_id}_display_name" id="field_{$field_id}_display_name" style="width: 96%;" value="{$field.field_title}"
              tabindex="{$row_count+10000}" />
          </td>
          <td class="pad_left_small">{$field.field_test_value|truncate:30}</td>
          <td class="pad_left_small" width="100">

            {if $field.field_type == "system"}

              {* pass along hidden field to let the reorder function know that this field is a system field.
                 This prevents the program from trying to rename the column *}
              {$LANG.word_na}
              <input type="hidden" name="field_{$field_id}_system" value="1" />
              <input type="hidden" name="field_{$field_id}_size" value="small" />

            {else}

              {if $field.field_test_value == $LANG.word_file_b_uc}
                <select disabled>
              {else}
                <select name="field_{$field_id}_size" tabindex="{$row_count+20000}">
              {/if}

              <option {if $field.field_size == "tiny"}selected{/if} value="tiny">{$LANG.phrase_size_tiny}</option>
              <option {if $field.field_size == "small"}selected{/if} value="small">{$LANG.phrase_size_small}</option>
              <option {if $field.field_size == "medium" || $field.field_test_value == $LANG.word_file_b_uc}selected{/if} value="medium">{$LANG.phrase_size_medium}</option>
              <option {if $field.field_size == "large"}selected{/if} value="large">{$LANG.phrase_size_large}</option>
              <option {if $field.field_size == "very_large"}selected{/if} value='very_large'>{$LANG.phrase_size_very_large}</option>
              </select>

              {* if a file field, pass along a hidden field with the 256 character value *}
              {if $field.field_test_value == $LANG.word_file_b_uc}
                <input type="hidden" name="field_{$field_id}_size" value="medium" />
              {/if}
            {/if}

          </td>
          <td class="del">
          {if $field.field_type != "system"}<input type="checkbox" name="field_{$field_id}_remove" tabindex="{$row_count+30000}" />{/if}
          </td>
        </tr>
      {/foreach}
      </table>

      <p>
        <input type="submit" name="action" value="{$LANG.word_update|upper}" />
      </p>

      <p>
        <input type="submit" name="next_step" class="next_step" value="{$LANG.word_next_step_rightarrow}"
          onclick="return page_ns.validate_fields()"/>
      </p>

    </form>

  </div>

{ft_include file="footer.tpl"}