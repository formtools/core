            <div class="margin_top margin_bottom_large">
              {$LANG.text_email_template_tab}
            </div>

            <table cellpadding="0" cellspacing="1" style="padding-bottom: 5px; width:100%">
            <tr>
              <td class="bold">{$LANG.phrase_html_template}</td>
              <td class="no_wrap" align="right">
                {$LANG.phrase_example_templates_c}
                <select onchange="emails_ns.select_template('html', this.value)">
                  <option value="">{$LANG.phrase_please_select}</option>
                  {foreach from=$email_patterns.html_patterns item=pattern name=row}
                    {assign var='count' value=$smarty.foreach.row.iteration}
                    <option value="{$count}">{$pattern.pattern_name}</option>
                  {/foreach}
                </select>
              </td>
            </tr>
            <tr>
              <td colspan="2">
                <div style="border: 1px solid #666666; padding: 3px">
                  <textarea id="html_template" name="html_template" style="width: 100%; height: 320px">{$template_info.html_template}</textarea>
                </div>

                <script type="text/javascript">
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
                {$LANG.phrase_example_templates_c}
                <select onchange="emails_ns.select_template('text', this.value)">
                  <option value="">{$LANG.phrase_please_select}</option>
                  {foreach from=$email_patterns.text_patterns item=pattern name=row}
                    {assign var='count' value=$smarty.foreach.row.iteration}
                    <option value="{$count}">{$pattern.pattern_name}</option>
                  {/foreach}
                </select>
              </td>
            </tr>
            <tr>
              <td colspan="2">
                <div style="border: 1px solid #666666; padding: 3px">
                  <textarea id="text_template" name="text_template" style="width: 100%; height: 320px">{$template_info.text_template}</textarea>
                </div>

                <script type="text/javascript">
								  var text_editor = new CodeMirror.fromTextArea("text_template", {literal}{{/literal}
								    parserfile: ["parsexml.js"],
								    path: "{$g_root_url}/global/codemirror/js/",
								    stylesheet: "{$g_root_url}/global/codemirror/css/xmlcolors.css"
								  {literal}});{/literal}
								</script>

              </td>
            </tr>
            </table>
