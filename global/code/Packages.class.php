<?php

namespace FormTools;


class Packages
{
	// $url = "http://localhost:8888/formtools-site/cdn.formtools.org/modules/arbitrary_settings-2.0.2.zip";

	/**
	 * Downloads a single component and unpacks it at the appropriate location.
	 * - cleans up after itself, deleting any downloaded zipfiles
	 * - will REMOVE any previous version of the component being downloaded. To be safe, it backs up the old
	 * folder before removing by adding a BACKUP- prefix. If the new component is properly installed the BACKUP- folder
	 * is removed.
	 *
	 * @param $url
	 * @param $component_type "theme", "module", "core" or "api"
	 * @return array
	 */
	public static function downloadAndUnpack($url, $component_type)
	{
		$cache_dir = Core::getCacheDir();
		$target_folder = Core::getRootDir();

		if ($component_type == "module") {
			$target_folder .= "/modules";
		}  else if ($component_type == "theme") {
			$target_folder .= "/themes";
		} else if ($component_type == "api") {
			$target_folder .= "/global";
		} else {
			return array(
				"success" => false,
				"log" => array("Invalid component type passed to Packages::downloadAndUnpack()")
			);
		}

		list($component_folder, $component_version) = explode("-", basename($url, ".zip"));

		if (General::curlEnabled()) {

			if (!Curl::urlExists($url)) {
				return array(
					"success" => false,
					"log" => "Zipfile not found at url: $url"
				);
			}

			$result = Curl::downloadFile($url, $cache_dir);

			if (!$result["success"]) {
				return $result;
			}

			$log = $result["log"];
			$downloaded_zipfile = $result["file_path"];

			// unzip it to the modules folder
			$log[] = "unzipping $downloaded_zipfile";
			$zip = new \ZipArchive;
			$res = $zip->open($downloaded_zipfile);

			if ($res === true) {

				// the unzipped content will have the content within a folder with the same name as the repo, plus the version number
				if ($component_type === "module") {
					$unzipped_folder_name = "module-{$component_folder}-{$component_version}";
				} else if ($component_type == "theme") {
					$unzipped_folder_name = "theme-{$component_folder}-{$component_version}";
				} else {
					$unzipped_folder_name = "api-{$component_version}";
				}

				// just in case, remove any previous unzipped folder
				if (file_exists("$target_folder/$unzipped_folder_name")) {
					unlink("$target_folder/$unzipped_folder_name");
					$log[] = "remove previous undeleted unzip folder: $target_folder/$unzipped_folder_name";
				}

				$log[] = "extracting to $target_folder";

				$zip->extractTo($target_folder);
				$zip->close();

				// backup the old component folder if it exists
				$backup_folder = "$target_folder/BACKUP-{$component_folder}";

				if (file_exists("$target_folder/$component_folder")) {
					$log[] = "existing component folder already exists";
					if (rename("$target_folder/$component_folder", $backup_folder)) {
						$log[] = "existing component folder backed up to $backup_folder";
						chmod($backup_folder, 0777);
					} else {
						$log[] = "unable to back up $target_folder/BACKUP-{$component_folder}";
						return array(
							"success" => false,
							"log" => $log
						);
					}
				}

				// rename the folder to its final correct name
				if (rename("$target_folder/$unzipped_folder_name", "$target_folder/$component_folder")) {
					$log[] = "new component folder created: $target_folder/$component_folder";

					if (unlink($downloaded_zipfile)) {
						$log[] = "$downloaded_zipfile cache file removed";

						// now remove the backup folder
						if (file_exists($backup_folder)) {
							if (Files::deleteFolder($backup_folder)) {
								$log[] = "backup folder removed: $backup_folder";
							} else {
								$log[] = "Error removing backup folder";
							}
						}

					} else {
						$log[] = "error removing cache file $downloaded_zipfile";
					}
				}

				return array(
					"success" => true,
					"log" => $log
				);

			} else {
				$error = "error unzipping: ";

				switch ($res) {
					case \ZipArchive::ER_EXISTS:
						$error .= "file already exists";
						break;
					case \ZipArchive::ER_INCONS:
						$error .= "zip file inconsistent";
						break;
					case \ZipArchive::ER_INVAL:
						$error .= "invalid argument";
						break;
					case \ZipArchive::ER_MEMORY:
						$error .= "malloc failure";
						break;
					case \ZipArchive::ER_NOENT:
						$error .= "no such file";
						break;
					case \ZipArchive::ER_NOZIP:
						$error .= "not a zip archive";
						break;
					case \ZipArchive::ER_OPEN:
						$error .= "can't open file";
						break;
					case \ZipArchive::ER_READ:
						$error .= "read error";
						break;
					case \ZipArchive::ER_SEEK:
						$error .= "seek error";
						break;
				}

				$log[] = $error;

				return array(
					"success" => false,
					"log" => $log
				);
			}
		}
	}
}

