<?php

namespace FormTools;


class CurlTransport
{
    public static function request ($url, $destination, $options)
    {
        $curl_options = array(
            CURLOPT_FILE => is_resource($destination) ? $destination : fopen($destination, 'w'),
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_URL => $url,
            CURLOPT_FAILONERROR => true, // HTTP code > 400 will throw curl error
        );

        if ($options["progress"]) {
            $curl_options[CURLOPT_PROGRESSFUNCTION] = $options["progress"];
        }

        $ch = curl_init();
        curl_setopt_array($ch, $curl_options);
        $return = curl_exec($ch);

        if ($return === false) {
            return curl_error($ch);
        } else {
            return true;
        }
    }
}
