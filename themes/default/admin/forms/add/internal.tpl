{ft_include file='header.tpl'}

  <table cellpadding="0" cellspacing="0">
  <tr>
    <td width="45"><a href="../"><img src="{$images_url}/icon_forms.gif" border="0" width="34" height="34" /></a></td>
    <td class="title">
      <a href="../">{$LANG.word_forms}</a> <span class="joiner">&raquo;</span>
      <a href="./">{$LANG.phrase_add_form}</a> <span class="joiner">&raquo;</span>
      {$LANG.phrase_internal_form}
    </td>
  </tr>
  </table>

  {ft_include file="messages.tpl"}

  <div class="margin_bottom_large">
    {$LANG.text_internal_form_intro}
  </div>

  <form action="internal.php" id="create_internal_form" method="post">

    <table cellspacing="1" cellpadding="0" class="list_table">
    <tr>
      <td width="15" align="center" class="red">*</td>
      <td class="pad_left_small" width="180">{$LANG.phrase_form_name}</td>
      <td>
        <input type="text" name="form_name" id="form_name" style="width: 99%" />
      </td>
    </tr>
    <tr>
      <td width="15" align="center" class="red">*</td>
      <td class="pad_left_small">{$LANG.phrase_num_fields}</td>
      <td>
        <input type="text" name="num_fields" style="width: 50px" value="5" />
      </td>
    </tr>
    <tr>
      <td class="red" valign="top" align="center">*</td>
      <td class="pad_left_small" valign="top">{$LANG.word_access}</td>
      <td>

        <table cellspacing="1" cellpadding="0" >
        <tr>
          <td>
            <input type="radio" name="access_type" id="at1" value="admin" checked />
              <label for="at1">{$LANG.phrase_admin_only}</label>
          </td>
        </tr>
        <tr>
          <td>
            <input type="radio" name="access_type" id="at2" value="public" />
              <label for="at2">{$LANG.word_public} <span class="light_grey">{$LANG.phrase_all_clients_have_access}</span></label>
          </td>
        </tr>
        <tr>
          <td>
            <input type="radio" name="access_type" id="at3" value="private" />
              <label for="at3">{$LANG.word_private} <span class="light_grey">{$LANG.phrase_only_specific_clients_have_access}</span></label>
          </td>
        </tr>
        </table>

        <div id="custom_clients" {if $form_info.access_type != 'private'}style="display:none"{/if} class="margin_top">
          <table cellpadding="0" cellspacing="0" class="subpanel">
          <tr>
            <td class="medium_grey">{$LANG.phrase_available_clients}</td>
            <td></td>
            <td class="medium_grey">{$LANG.phrase_selected_clients}</td>
          </tr>
          <tr>
            <td>
              {clients_dropdown name_id="available_client_ids[]" multiple="true" multiple_action="hide"
                clients=$selected_client_ids size="4" style="width: 205px"}
            </td>
            <td align="center" valign="middle" width="100">
              <input type="button" value="{$LANG.word_add_uc_rightarrow}"
                onclick="ft.move_options(this.form['available_client_ids[]'], this.form['selected_client_ids[]']);" /><br />
              <input type="button" value="{$LANG.word_remove_uc_leftarrow}"
                onclick="ft.move_options(this.form['selected_client_ids[]'], this.form['available_client_ids[]']);" />
            </td>
            <td>
              {clients_dropdown name_id="selected_client_ids[]" multiple="true" multiple_action="show"
                clients=$selected_client_ids size="4" style="width: 205px"}
            </td>
          </tr>
          </table>
        </div>

      </td>
    </tr>
    </table>

    <p>
      <input type="submit" name="add_form" class="add_form" value="{$LANG.phrase_add_form}" />
    </p>
  </form>

{ft_include file='footer.tpl'}