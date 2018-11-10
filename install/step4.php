<?php

require_once("../global/library.php");

use FormTools\Core;
use FormTools\General;
use FormTools\Installation;

Core::setHooksEnabled(false);
Core::startSessions();

Installation::checkInstallationComplete();

Core::initSmarty();
Core::setCurrLang(General::loadField("lang_file", "lang_file", Core::getDefaultLang()));

$config_file = Installation::getConfigFileContents();

$config_file_generated = "";
if (isset($_POST["generate_file"])) {
	$config_file_generated = Installation::generateConfigFile($config_file);
	if ($config_file_generated) {
        General::redirect("step5.php");
		exit;
	}
}

if (isset($_POST["check_config_contents"])) {
	list ($g_success, $g_message) = Installation::checkConfigFileExists();

	// great: the user has managed to manually create the file. Continue to the next step.
	if ($g_success) {
		header("location: step5.php");
		exit;
	}
	$config_file_generated = false;
}

$page = array(
    "step" => 4,
    "config_file" => $config_file,
    "config_file_generated" => $config_file_generated
);

$page["head_string"] =<<< END
<link href="../global/codemirror/lib/codemirror.css" rel="stylesheet" type="text/css" />
<script src="../global/codemirror/lib/codemirror.js"></script>
<script src="../global/codemirror/mode/xml/xml.js"></script>
<script src="../global/codemirror/mode/smarty/smarty.js"></script>
<script src="../global/codemirror/mode/php/php.js"></script>
<script src="../global/codemirror/mode/htmlmixed/htmlmixed.js"></script>
<script src="../global/codemirror/mode/css/css.js"></script>
<script src="../global/codemirror/mode/javascript/javascript.js"></script>
<script src="../global/codemirror/mode/clike/clike.js"></script>
END;

Installation::displayPage("templates/step4.tpl", $page);
