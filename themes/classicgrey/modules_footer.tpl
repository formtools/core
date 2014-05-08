              </div>
            </div>
          </div>
        </div>

        <div id="left">

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
  <div style="padding-top:3px;">{$account.settings.footer_text}</div>
</div>

</body>
</html>