<?php
require_once("../examples-config.php");
require_once($path_to_api_v1);
?><!doctype html>
<html>
<head>
</head>
<body>

<h4>Documentation</h4>

See: <a href="https://docs.formtools.org/api/ft_api_show_submission/" target="_blank">https://docs.formtools.org/api/ft_api_show_submission/</a>

<hr size="1" />

<?php

$form_id = 1;
$view_id = 1;
$export_type_id = 2;
$submission_id = 1;

ft_api_show_submission($form_id, $view_id, $export_type_id, $submission_id);
?>

</body>
</html>
