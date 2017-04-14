<?php

use FormTools\Core;
use FormTools\General;

function smarty_function_show_page_load_time($params, &$smarty)
{
    if (!Core::isBenchmarkingEnabled()) {
        return;
    }

    $difference = round(General::getMicrotimeFloat() - Core::getBenchmarkStart(), 5);

    echo "<div class=\"medium_grey\">Page load time: <b>$difference</b> seconds</div>";
}
