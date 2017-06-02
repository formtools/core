  <div class="hint margin_bottom_large">
    {$LANG.text_edit_tab_summary}
  </div>

  <table class="list_table" cellpadding="0" cellspacing="1" id="tab_options_table" style="width: 350px; float: left">
    <tr>
      <th width="40">{$LANG.word_tab}</th>
      <th>{$LANG.phrase_tab_label}</th>
    </tr>
    <tr>
      <td align="center">1</td>
      <td><input type="text" name="tabs[]" id="tab_label1" class="tab_label" value="{$view_tabs[1].tab_label}" maxlength="50" /></td>
    </tr>
    <tr>
      <td align="center">2</td>
      <td><input type="text" name="tabs[]" id="tab_label2" class="tab_label" value="{$view_tabs[2].tab_label}" maxlength="50" /></td>
    </tr>
    <tr>
      <td align="center">3</td>
      <td><input type="text" name="tabs[]" id="tab_label3" class="tab_label" value="{$view_tabs[3].tab_label}" maxlength="50" /></td>
    </tr>
    <tr>
      <td align="center">4</td>
      <td><input type="text" name="tabs[]" id="tab_label4" class="tab_label" value="{$view_tabs[4].tab_label}" maxlength="50" /></td>
    </tr>
    <tr>
      <td align="center">5</td>
      <td><input type="text" name="tabs[]" id="tab_label5" class="tab_label" value="{$view_tabs[5].tab_label}" maxlength="50" /></td>
    </tr>
    <tr>
      <td align="center">6</td>
      <td><input type="text" name="tabs[]" id="tab_label6" class="tab_label" value="{$view_tabs[6].tab_label}" maxlength="50" /></td>
    </tr>
  </table>

  <input type="button" value="{$LANG.phrase_remove_tabs}" onclick="view_ns.remove_tabs()" style="margin-left: 10px; float: left" />

  <div class="clear"></div>
