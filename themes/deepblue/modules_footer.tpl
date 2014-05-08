
            </div>
          </div>
        </div>

        <div id="left">
          <div id="left_nav_top">
	          {if $SESSION.account.is_logged_in}
	        	  {$LANG.word_version} <b>{$SESSION.settings.program_version}</b>
	        	{else}
	        	  <div style="height: 20px"> </div>
	          {/if}
          </div>

          <div class="nav_heading">
            {$LANG.phrase_module_nav}
          </div>
          <div id="module_nav">
	          {ft_include file="module_menu.tpl"}
          </div>

          <br />

          <div class="nav_heading">
            {$LANG.phrase_main_nav}
          </div>
          <div id="main_nav">
					  {ft_include file="menu.tpl"}
					</div>
        </div>

      </div>

      <div class="clear"></div>

    </div>
  </div>
</div>

<div id="footer">
  <span style="float:right"><img src="{$theme_url}/images/footer_right.jpg" width="16" height="37" /></span>
  <span style="float:left"><img src="{$theme_url}/images/footer_left.jpg" width="13" height="37" /></span>
  <div style="padding-top:3px;">{$settings.footer_text}</div>
</div>

</body>
</html>