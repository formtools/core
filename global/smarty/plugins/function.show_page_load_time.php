<?php

function smarty_function_show_page_load_time($params, &$smarty)
{
  global $g_benchmark_start, $g_enable_benchmarking;

  if (!$g_enable_benchmarking || empty($g_benchmark_start))
    return;

  $difference = round(ft_get_microtime_float() - $g_benchmark_start, 5);

  echo "<div class=\"medium_grey\">Page load time: <b>$difference</b> seconds</div>";
}