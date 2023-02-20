
      </div>
    </td>
  </tr>
  </table>

</div>

{* only display the footer area if there is some text entered for it *}
{if $footer_text != "" || $g_enable_benchmarking}
  <div class="footer">
    {$footer_text}
    {show_page_load_time}
  </div>
{/if}

</body>
</html>
