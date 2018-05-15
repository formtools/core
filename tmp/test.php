<?php

require_once(realpath(__DIR__ . "/../global/library.php"));

ob_start();
ob_flush();
flush();

$url = "https://github.com/formtools/core/archive/3.0.0.zip";
$file = __DIR__ . "/target/file.zip";


function progress($resource, $downloadSize, $downloaded, $uploadSize, $uploaded)
{
//    echo "$resource, $downloadSize, $downloaded, $uploadSize, $uploaded";

    if ($downloadSize > 0) {
        echo $downloaded / $downloadSize * 100;
    }

    ob_flush();
    flush();
    sleep(1);
}


FormTools\CurlTransport::request($url, $file, array(
    "progress" => "progress"
));

echo "Complete";

ob_flush();
flush();
