<?php

namespace FormTools;


class Curl
{
    public static function request ($url)
    {
//        $curl_options = array(
//            CURLOPT_FILE => is_resource($destination) ? $destination : fopen($destination, 'w'),
//            CURLOPT_FOLLOWLOCATION => true,
//            CURLOPT_URL => $url,
//            CURLOPT_FAILONERROR => true, // HTTP code > 400 will throw curl error
//        );
//
//        if ($options["progress"]) {
//            $curl_options[CURLOPT_PROGRESSFUNCTION] = $options["progress"];
//        }
//
//        $ch = curl_init();
//        curl_setopt_array($ch, $curl_options);
//        $return = curl_exec($ch);
//
//        if ($return === false) {
//            return curl_error($ch);
//        } else {
//            return true;
//        }

//        $ch = curl_init($url);
//
//        // Configuring curl options
//        $options = array(
//            CURLOPT_RETURNTRANSFER => true,
//            CURLOPT_HTTPHEADER => array("Content-type: application/json")
//        );
//
//        curl_setopt_array( $ch, $options );
//
//        return curl_exec($ch); // Getting jSON result string
    }


	/**
	 * @param $url
	 * @param string $target_folder
	 * @return array
	 */
	public static function downloadFile($url, $target_folder)
	{
		$filename = basename($url);

		set_time_limit(0);
		if (file_exists("$target_folder/$filename")) {
			unlink("$target_folder/$filename");
		}

		$fp = fopen("$target_folder/$filename", "w+");

		if ($fp === false) {
			return array(
				"success" => false,
				"message" => ""
			);
		}

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_setopt($ch, CURLOPT_TIMEOUT, 500);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_exec($ch);

		if (curl_errno($ch)) {
			return array(
				"success" => false,
				"message" => curl_error($ch)
			);
		}

		$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		curl_close($ch);
		fclose($fp);

		return array(
			"success" => true,
			"status_code" => $status_code,
			"file_path" => "$target_folder/$filename"
		);
	}

}
