
      </div>
    </td>
  </tr>
  </table>

</div>

{* only display the footer area if there is some text entered for it *}
{if $is_logged_in && ((isset($account.settings.footer_text) && $account.settings.footer_text != "") || $g_enable_benchmarking)}
  <div class="footer">
    {$account.settings.footer_text}
    {show_page_load_time}
  </div>
{/if}

</body>
</html>
