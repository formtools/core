{include file='modules_header.tpl'}

  <table cellpadding="0" cellspacing="0">
  <tr>
    <td width="45"><a href="index.php"><img src="images/icon_core_field_types.png" border="0" width="34" height="34" /></a></td>
    <td class="title">
      <a href="../../admin/modules">{$LANG.word_modules}</a>
      <span class="joiner">&raquo;</span>
      <a href="./">{$L.module_name}</a>
      <span class="joiner">&raquo;</span>
      {$L.word_reset}
    </td>
  </tr>
  </table>

  {include file="messages.tpl"}

  <div class="margin_bottom_large">
    {$L.text_reset_page}
  </div>

  <div class="notify">
    <div style="padding: 6px">
      {$L.text_custom_fields_warning}
    </div>
  </div>


  <form action="" method="post">
	  <p>
	    <input type="submit" name="reset" value="Reset Core Fields" />
	  </p>
  </form>

{include file='modules_footer.tpl'}