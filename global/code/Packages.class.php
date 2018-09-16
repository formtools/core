<?php

namespace FormTools;


class Packages
{
//    public static function getCompatibleComponents()
//    {
//        $url = Core::getFormToolsDataSource();
//        $version = Core::getCoreVersion();
//
//        return CurlTransport::request("{$url}/core-{$version}.json");
//    }

    public static function downloadComponentZip($url)
	{
		$root_dir = Core::getRootDir();

		$filename = basename($url);

		set_time_limit(0);

		if (file_exists("$root_dir/cache/$filename")) {
			unlink("$root_dir/cache/$filename");
		}

		$fp = fopen("$root_dir/cache/$filename", "w+");

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 50);
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

		$result = curl_exec($ch);
		curl_close($ch);
		fclose($fp);

		return $result;
	}

}
