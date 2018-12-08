<?php

namespace FormTools;


class Curl
{
	/**
	 * Download a file via curl and places it in a target folder.
	 * @param $url
	 * @param string $target_folder
	 * @return array
	 */
	public static function downloadFile($url, $target_folder)
	{
		$log = array();
		$filename = basename($url);

		set_time_limit(0);
		if (file_exists("$target_folder/$filename")) {
			$log[] = "$filename already exists $target_folder";
			if (unlink("$target_folder/$filename")) {
				$log[] = "deleted old $filename from $target_folder";
			} else {
				$log[] = "unable to remove old $filename file from $target_folder. Aborting";
				return array(
					"success" => false,
					"log" => $log
				);
			}
		}

		$log[] = "starting download of $filename to $target_folder";

		$fp = fopen("$target_folder/$filename", "w+");

		if ($fp === false) {
			$log[] = "unable to create a $filename file in $target_folder.";
			return array(
				"success" => false,
				"log" => $log
			);
		}

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5 minute timeout
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_exec($ch);

		if (curl_errno($ch)) {
			$log[] = "curl error: " . curl_error($ch);
			return array(
				"success" => false,
				"log" => $log
			);
		}

		$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$log[] = "curl status code: $status_code";

		curl_close($ch);
		fclose($fp);

		return array(
			"success" => true,
			"status_code" => $status_code,
			"file_path" => "$target_folder/$filename",
			"log" => $log
		);
	}


	public static function urlExists($url)
	{
		$ch = @curl_init($url);
		@curl_setopt($ch, CURLOPT_HEADER, TRUE);
		@curl_setopt($ch, CURLOPT_NOBODY, TRUE);
		@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, FALSE);
		@curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$status = array();
		preg_match('/HTTP\/.* ([0-9]+) .*/', @curl_exec($ch) , $status);

		return $status[1] == 200;
	}
}
