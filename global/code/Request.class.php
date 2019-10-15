<?php

namespace FormTools;



class Request
{
	/**
	 * Make a curl request for a file
	 * @param $url
	 * @return array
	 */
    public static function getJsonFileFromUrl($url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $url);
		$data = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($http_code !== 200) {
			$result = array(
				"success" => false,
				"http_code" => $http_code
			);
		} else {
			$result = array(
				"success" => true,
				"http_code" => $http_code,
				"data" => json_decode($data)
			);
		}

		return $result;
	}

}
