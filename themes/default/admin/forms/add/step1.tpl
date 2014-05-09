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

  <table cellpadding="0" cellspacing="0" width="100%" class="add_form_nav margin_bottom_large">
  <tr>
    <td class="selected">{$LANG.word_start}</td>
    <td class="unselected">{$LANG.phrase_form_info}</td>
    <td class="unselected">{$LANG.phrase_test_submission}</td>
    <td class="unselected">{$LANG.phrase_database_setup}</td>
    <td class="unselected">{$LANG.phrase_field_types}</td>
    <td class="unselected">{$LANG.phrase_finalize_form}</td>
  </tr>
  </table>

  <div class="subtitle underline">1. {$LANG.phrase_getting_started|upper}</div>

  <p>
    {$LANG.text_add_form_choose_integration_method}
  </p>

  <form method="post" action="{$same_page}">
    <table width="100%">
      <tr>
        <td width="49%" valign="top">
          <div id="direct_box" class="{if $form_info.submission_type == "direct"}blue_box{else}grey_box{/if}">
            <span style="float:right"><input type="submit" class="blue bold" value="{$LANG.word_select|upper}" name="direct" /></span>
            <div class="bold">{$LANG.phrase_1_direct}</div>
            <div class="medium_grey">&#8212; {$LANG.text_add_form_step_3_text_2}</div>
            <br />
            <div>
              <a href="#" onclick="return page_ns.show_section('method1_benefits')">{$LANG.word_benefits}</a> |
              <a href="#" onclick="return page_ns.show_section('method1_drawbacks')">{$LANG.word_drawbacks}</a>
            </div>
          </div>
        </td>
        <td width="2%"> </td>
        <td width="49%" valign="top">
          <div id="select_box" class="{if $form_info.submission_type == "code"}blue_box{else}grey_box{/if}">
            <span style="float:right"><input type="submit" class="blue bold" value="{$LANG.word_select|upper}" name="code" /></span>
            <div class="bold">{$LANG.phrase_2_code}</div>
            <div class="medium_grey">&#8212; {$LANG.text_add_form_step_3_text_3}</div>
            <br />
            <div>
              <a href="#" onclick="return page_ns.show_section('method2_benefits')">{$LANG.word_benefits}</a> |
              <a href="#" onclick="return page_ns.show_section('method2_drawbacks')">{$LANG.word_drawbacks}</a>
            </div>
          </div>
        </td>
      </tr>
      <tr>
        <td colspan="3">

          <div class="margin_top_large">
            <div class="box" id="method1_benefits" style="display:none">
              {$LANG.text_add_form_direct_submission_benefits}
            </div>

            <div class="box" id="method1_drawbacks" style="display:none">
              {$LANG.text_add_form_direct_submission_drawbacks}
            </div>

            <div class="box" id="method2_benefits" style="display:none">
              {$LANG.text_add_form_code_submission_benefits}
            </div>

            <div class="box" id="method2_drawbacks" style="display:none">
              {$LANG.text_add_form_code_submission_drawbacks}
            </div>
          </div>

        </td>
      </tr>
    </table>
  </form>

{ft_include file='footer.tpl'}
