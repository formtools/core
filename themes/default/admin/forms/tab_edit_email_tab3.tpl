  <div class="margin_top margin_bottom_large">
    <div class="placeholders_section">
      <img src="{$images_url}/placeholders.png" />
      <span class="placeholders_link">{$LANG.phrase_view_placeholders}</span>
    </div>
    {$LANG.text_email_template_tab}
  </div>

  <table cellpadding="0" cellspacing="1" style="padding-bottom: 5px; width:100%">
  <tr>
    <td class="bold">{$LANG.phrase_html_template}</td>
    <td class="no_wrap" align="right">
      {$LANG.word_examples_c}
      {email_patterns_dropdown type="html" form_id=$form_id onchange="emails_ns.select_template('html', this.value)"}
    </td>
  </tr>
  <tr>
    <td colspan="2">
      <div style="border: 1px solid #666666; padding: 3px">
        <textarea id="html_template" name="html_template" style="width: 100%; height: 300px">{$template_info.html_template}</textarea>
      </div>
      <script>
        var html_editor = new CodeMirror.fromTextArea("html_template", {literal}{{/literal}
        parserfile: ["parsexml.js"],
        path: "{$g_root_url}/global/codemirror/js/",
        stylesheet: "{$g_root_url}/global/codemirror/css/xmlcolors.css"
        {literal}});{/literal}
      </script>
    </td>
  </tr>
  <tr>
    <td class="bold">{$LANG.phrase_text_template}</td>
    <td class="no_wrap" align="right">
      {$LANG.word_examples_c}
      {email_patterns_dropdown type="text" form_id=$form_id onchange="emails_ns.select_template('text', this.value)"}
    </td>
  </tr>
  <tr>
    <td colspan="2">
      <div style="border: 1px solid #666666; padding: 3px">
        <textarea id="text_template" name="text_template" style="width: 100%; height: 300px">{$template_info.text_template}</textarea>
      </div>
      <script>
        var text_editor = new CodeMirror.fromTextArea("text_template", {literal}{{/literal}
        parserfile: ["parsexml.js"],
        path: "{$g_root_url}/global/codemirror/js/",
        stylesheet: "{$g_root_url}/global/codemirror/css/xmlcolors.css"
        {literal}});{/literal}
      </script>
    </td>
  </tr>
  </table>
