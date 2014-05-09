{ft_include file='header.tpl'}

  <table cellpadding="0" cellspacing="0" class="margin_bottom_large">
  <tr>
    <td width="45"><a href="../"><img src="{$images_url}/icon_forms.gif" border="0" width="34" height="34" /></a></td>
    <td class="title"><a href="../">{$LANG.word_forms}</a> <span class="joiner">&raquo;</span> {$LANG.phrase_add_form}</td>
  </tr>
  </table>

  <div class="margin_bottom_large">
    First, please choose your form type.
  </div>

  <form action="{$same_page}" method="post">

    <table width="100%">
      <tr>
        <td width="49%" valign="top">

          <div class="grey_box">
            <span style="float:right"><input type="submit" name="internal" class="blue bold" value="{$LANG.word_select|upper}" /></span>
            <div class="bold">{$LANG.word_internal}</div>
            <div class="medium_grey">
              Internal forms exist only within Form Tools - not elsewhere on your site. Only Form Tools user accounts will have
              access to the form. Select this option if you don't have an existing form.
            </div>
          </div>

        </td>
        <td width="2%"> </td>
        <td width="49%" valign="top">

          <div class="grey_box margin_bottom_large">
            <span style="float:right"><input type="button" id="select_external" name="external" class="blue bold" value="{$LANG.word_select|upper}" /></span>
            <div class="bold">{$LANG.word_external}</div>
            <div class="medium_grey">
              External forms are forms that already exist on your website, or somewhere on the web. Select this option if you have
              your own form which you'd like to integrate with Form Tools.
            </div>
          </div>

        </td>
      </tr>
    </table>

  </form>

  <div id="add_external_form_dialog" class="hidden">
    <table width="100%">
    <tr>
      <td valign="top" width="65"><span class="margin_top_large popup_icon popup_type_info"></span></td>
      <td>
        <p>
          {$LANG.text_add_form_step_1_text_1}
        </p>

        <ul>
          <li>{$LANG.text_add_form_step_1_text_2}</li>
          <li>{$LANG.text_add_form_step_1_text_3}</li>
        </ul>

        <p>
          If you run into any trouble during these steps, try reading out
          <a href="http://docs.formtools.org/userdoc?page=add_form">user documentation</a>.
        </p>
      </td>
    </tr>
    </table>
  </div>

{ft_include file='footer.tpl'}
