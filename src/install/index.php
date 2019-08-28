<?php
require_once("../global/library.php");

use FormTools\Core;
use FormTools\General;
use FormTools\Installation;
use FormTools\Sessions;

Core::setHooksEnabled(false);
Core::startSessions();

Installation::checkInstallationComplete();

// note we reset sessions here. This prevents weird things happening when a new installation is taking place
// while an old orphaned FT session exists, containing who-knows-what
Sessions::clearAll();

Core::initSmarty();
Core::setCurrLang(General::loadField("lang_file", "lang_file", Core::getDefaultLang()));
$root_url = Core::getRootUrl();
?>
<!doctype html>
<html>
<head>
    <title>Form Tools installation</title>
    <script>
    var g = {};
    g.root_url = '..';
    </script>
</head>
<body>
    <div id="root"></div>
    <script src="../react/main.bundle.js"></script>
</body>
</html>


























