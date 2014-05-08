{ft_include file='header.tpl'}

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
    <td class="selected">{$LANG.phrase_test_submission}</td>
    <td class="unselected">{$LANG.phrase_database_setup}</td>
    <td class="unselected">{$LANG.phrase_field_types}</td>
    <td class="unselected">{$LANG.phrase_finalize_form}</td>
  </tr>
  </table>

  <br />

  <div class="subtitle underline">{$LANG.phrase_test_submission_3|upper}</div>

    {ft_include file='messages.tpl'}

    <div class="pad_bottom">
      {$LANG.text_add_form_step_3_text_1}
    </div>

    <table cellspacing="2" cellpadding="6" width="100%">
      <tr>
        <td width="50%">
          <div id="direct_box" {if $form_info.submission_type == "direct"}class="blue_box"{else}class="grey_box"{/if}>
            <span style="float:right"><input type="button" class="blue bold" value="{$LANG.word_select|upper}" onclick="return page_ns.show_section('direct')" /></span>
            <div class="bold">{$LANG.phrase_1_direct}</div>
            <div class="medium_grey">&#8212; {$LANG.text_add_form_step_3_text_2}</div>
            <br />
            <div>
              <a href="#" onclick="return page_ns.show_section('method1_benefits')">{$LANG.word_benefits}</a> |
              <a href="#" onclick="return page_ns.show_section('method1_drawbacks')">{$LANG.word_drawbacks}</a>
            </div>
          </div>
        </td>
        <td width="50%">
          <div id="code_box" {if $form_info.submission_type == "code"}class="blue_box"{else}class="grey_box"{/if}>
            <span style="float:right"><input type="button" class="blue bold" value="{$LANG.word_select|upper}" onclick="return page_ns.show_section('code')" /></span>
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
        <td colspan="2">

          <div style="position:relative">
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

            <div id="direct" {if $form_info.submission_type != "direct"}style="display:none"{/if}>
              <p>
                <b>1</b>. {$LANG.text_add_form_step_2_para_2}
                <br />
                <textarea style="color: #336699; width: 100%; height: 60px">{$form_tag}
{$hidden_fields}</textarea>
              </p>

              <p>
                <b>2</b>. {$direct_form_para_2}
              </p>

              {if $form_info.is_initialized == "no"}
                <div class="incomplete">
                  <div style="padding-bottom: 5px;">{$LANG.phrase_awaiting_form_submission}</div>
                  <form action="{$same_page}" method="post">
                    <input type="hidden" name="submission_type" value="direct" />
                    <input type="hidden" name="form_id" value="{$form_id}"/>
                    <input type="submit" name="refresh" value="{$LANG.phrase_refresh_page}" />
                  </form>
                </div>
              {elseif $form_info.is_initialized == "yes"}
                <p>
                  <input type="button" value="{$LANG.phrase_resend_test_submission|upper}"
                    onclick="window.location='{$same_page}?uninitialize=1'" />
                </p>
                <p>
                  <input type="button" name="submit" class="next_step" value="{$LANG.word_next_step_rightarrow}"
                    onclick="window.location='step4.php?form_id={$form_id}'"/>
                </p>
              {/if}
            </div>

            <div id="code" {if $form_info.submission_type != "code"}style="display:none"{/if}>
              <p>
                {$LANG.text_add_form_step_3_text_4}
              </p>

              <ul>
                <li><a href="http://docs.formtools.org/tutorials/api_single_page_form/" target="_blank">{$LANG.phrase_adding_single_page_form}</a></li>
                <li><a href="http://docs.formtools.org/tutorials/api_multi_page_form/" target="_blank">{$LANG.phrase_adding_multi_page_form}</a></li>
              </ul>

              <p>
                {$LANG.text_add_form_step_3_text_5}
              </p>

              <code><pre class="green">
    $fields = ft_api_init_form_page({$form_id}, "initialize");</pre></code>

              <p>
                {$LANG.text_add_form_step_3_text_7}
              </p>

              <code><pre class="green">
    "finalize" => true</pre></code>

              <p>
                {$LANG.text_add_form_step_3_text_6}
              </p>

              {if $form_info.is_initialized == "no"}
                <div class="incomplete">
                  <div style="padding-bottom: 5px;">{$LANG.phrase_awaiting_form_submission}</div>
                  <form action="{$same_page}" method="post">
                    <input type="hidden" name="submission_type" value="code" />
                    <input type="hidden" name="form_id" value="{$form_id}" />
                    <input type="submit" name="refresh" value="{$LANG.phrase_refresh_page}" />
                  </form>
                </div>
              {elseif $form_info.is_initialized == "yes"}
                <p>
                  <input type="button" value="{$LANG.phrase_resend_test_submission|upper}"
                    onclick="window.location='{$same_page}?uninitialize=1'" />
                </p>
                <p>
                  <input type="button" name="submit" class="next_step" value="{$LANG.word_next_step_rightarrow}"
                    onclick="window.location='step4.php?form_id={$form_id}'"/>
                </p>
              {/if}

            </div>
          </div>

        </td>
      </tr>
    </table>


{ft_include file='footer.tpl'}