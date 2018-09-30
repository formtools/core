<?php
require_once("../examples-config.php");
require_once($path_to_api_v2);
?><!doctype html>
<html>
<head>
</head>
<body>

<h4>Documentation</h4>

See: <a href="https://docs.formtools.org/api/v2/deleteClientAccount/" target="_blank">https://docs.formtools.org/api/v2/deleteClientAccount/</a>

<hr size="1" />

<?php

$api = new FormTools\API();

$account_id = 2;
$api->deleteClientAccount($account_id);

?>

</body>
</html>
