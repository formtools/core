<?php
require_once("../examples-config.php");
require_once($path_to_api_v2);
?><!doctype html>
<html>
<head>
</head>
<body>

<h4>Documentation</h4>

See: <a href="https://docs.formtools.org/api/v2/showSubmission/" target="_blank">https://docs.formtools.org/api/v2/showSubmission/</a>

<hr size="1" />

<?php

$api = new FormTools\API();

$form_id = 1;
$view_id = 1;
$export_type_id = 2;
$submission_id = 1;

$api->showSubmission($form_id, $view_id, $export_type_id, $submission_id);

?>

</body>
</html>
