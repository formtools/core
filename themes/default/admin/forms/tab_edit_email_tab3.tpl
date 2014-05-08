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
                <textarea style="width: 100%; height: 160px;" class="template" name="html_template" id="html_template">{$template_info.html_template}</textarea>
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
                <textarea style="width: 100%; height: 160px;" class="template" name="text_template" id="text_template">{$template_info.text_template}</textarea>
              </td>
            </tr>
            </table>
