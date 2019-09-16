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
// TODO This should be fine... but check
//Sessions::clearAll();

Core::initSmarty();
Core::setCurrLang(General::loadField("lang", "lang", Core::getDefaultLang()));
$root_url = Core::getRootUrl();
$LANG = Core::$L;
?>
<!doctype html>
<html>
<head>
    <title><?php echo $LANG["phrase_ft_installation"]; ?></title>
</head>
<body>
    <div id="root"></div>
    <script src="../react/main.bundle.js?v=<?php Core::getVersionString(); ?>"></script>
</body>
</html>
