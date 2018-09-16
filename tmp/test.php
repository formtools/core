<?php

use FormTools\Curl;
use FormTools\General;

require_once("../global/library.php");

$url = "http://localhost:8888/formtools-site/cdn.formtools.org/modules/arbitrary_settings-2.0.2.zip";
$zipfile_name = basename($url);
list($module_folder, $module_version) = explode("-", basename($url, ".zip"));

$tmp_target_folder = "/Applications/MAMP/htdocs/core3.1/cache";
$modules_folder = "/Applications/MAMP/htdocs/core3.1/modules";

if (General::curlEnabled()) {

	// download the file
	$result = Curl::downloadFile($url, $tmp_target_folder);
	$downloaded_zipfile = $result["file_path"];

	// unzip it to the modules folder
	$zip = new ZipArchive;
	$res = $zip->open($downloaded_zipfile);
	if ($res === true) {

		$github_repo_name = "module-{$module_folder}";

		// just in case, remove any orphaned previous unzipped folder
		if (file_exists("$modules_folder/$github_repo_name")) {
			unlink("$modules_folder/$github_repo_name");
		}

		$zip->extractTo($modules_folder);
		$zip->close();

		// rename the folder to the final module name
		if (rename("$modules_folder/module-={$module_folder}-{$module_version}", "$modules_folder/$module_folder")) {
			@unlink("$tmp_target_folder/$downloaded_zipfile");
		}
	}
}
