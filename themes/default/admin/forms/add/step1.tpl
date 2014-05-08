{ft_include file='header.tpl'}

  <table cellpadding="0" cellspacing="0" class="margin_bottom_large">
  <tr>
    <td width="45"><a href="../"><img src="{$images_url}/icon_forms.gif" border="0" width="34" height="34" /></a></td>
    <td class="title"><a href="../">{$LANG.word_forms|upper}</a>: {$LANG.phrase_add_form|upper}</td>
  </tr>
  </table>

  <table cellpadding="0" cellspacing="0" width="100%" class="add_form_nav margin_bottom_large">
  <tr>
    <td class="selected">{$LANG.word_checklist}</td>
    <td class="unselected">{$LANG.phrase_form_info}</td>
    <td class="unselected">{$LANG.phrase_test_submission}</td>
    <td class="unselected">{$LANG.phrase_database_setup}</td>
    <td class="unselected">{$LANG.phrase_field_types}</td>
    <td class="unselected">{$LANG.phrase_finalize_form}</td>
  </tr>
  </table>

  <div class="subtitle underline" style="position:relative">{$LANG.phrase_checklist_1|upper}</div>

  <p>
    {$LANG.text_add_form_step_1_text_1}
  </p>

  <ul>
    <li>{$LANG.text_add_form_step_1_text_2}</li>
    <li>{$LANG.text_add_form_step_1_text_3}</li>
  </ul>

  <p>
    All the settings you enter in the following pages may later be edited.
  </p>

  <p>
    If you run into any trouble during these steps, you may want to consult the <a href="http://docs.formtools.org/userdoc?page=add_form">User Documentation</a>
    for further information.
  </p>

  <form action="step2.php" method="post">
    <p>
      <input type="hidden" name="form_id" value="{$form_id}" />
      <input type="submit" name="submit" class="next_step" value="{$LANG.word_next_step_rightarrow}" />
    </p>
  </form>

{ft_include file='footer.tpl'}