            </div>
          </div>
        </div>

        <div id="left">
          <div id="left_nav_top">
	          {if $SESSION.account.is_logged_in}

	        	  {$LANG.word_version} <b>{$settings.program_version}</b>

	        	  {if $SESSION.settings.is_beta}
	        	    &#8212; <span style="" class="red bold">{$SESSION.settings.beta_version}</span>
	        	  {/if}

	        	{else}
	        	  <div style="height: 20px"> </div>
	          {/if}
          </div>

					{ft_include file="menu.tpl"}
        </div>

      </div>

      <div class="clear"></div>

    </div>
  </div>
</div>

<div id="footer">
  <span style="float:right"><img src="{$theme_url}/images/footer_right.jpg" width="16" height="37" /></span>
  <span style="float:left"><img src="{$theme_url}/images/footer_left.jpg" width="13" height="37" /></span>
  <div style="padding-top:3px;">{$account.settings.footer_text}</div>
</div>

</body>
</html>