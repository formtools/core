
      </div>
    </td>
  </tr>
  </table>

</div>

{* only display the footer area if there is some text entered for it *}
{if $account.settings.footer_text != ""}
  <div id="footer">
    <div style="padding-top:3px;">{$account.settings.footer_text}</div>
  </div>
{/if}

</body>
</html>