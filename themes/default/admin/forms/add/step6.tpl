{ft_include file='header.tpl'}

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
    <td class="selected">{$LANG.word_start}</td>
    <td class="selected">{$LANG.phrase_form_info}</td>
    <td class="selected">{$LANG.phrase_test_submission}</td>
    <td class="selected">{$LANG.phrase_database_setup}</td>
    <td class="selected">{$LANG.phrase_field_types}</td>
    <td class="selected">{$LANG.phrase_finalize_form}</td>
  </tr>
  </table>

  <br />
  <div>
    <div class="subtitle underline">{$LANG.phrase_final_touches_page_6|upper}</div>
    <p>
      {$LANG.text_add_form_step_5_para_1}
    </p>
    <code><pre class="green">
      &lt;input type="hidden" name="form_tools_initialize_form" value="1" /&gt;</pre></code>

    <p>
      {$LANG.text_add_form_step_5_para_5}
    </p>
    <code><pre class="green">
      $fields = ft_api_init_form_page({$form_id});</pre></code>

    <p>
      {$LANG.text_add_form_step_5_para_2}
    </p>
    <p>
      {$text_add_form_step_5_para}
    </p>
    {if $uploading_files == "yes"}
      <p>
        {$text_add_form_step_5_para_4}
      </p>
    {/if}

  </div>

  <form method="post" action="../">
    <input type="submit" name="action" value="{$LANG.phrase_form_list}" />
  </form>

{ft_include file='footer.tpl'}