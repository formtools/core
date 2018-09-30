<?php
require_once("../examples-config.php");
require_once($path_to_api_v1);
?><!doctype html>
<html>
<head>
</head>
<body>

<h4>Documentation</h4>

See: <a href="https://docs.formtools.org/api/ft_api_get_submission/" target="_blank">https://docs.formtools.org/api/ft_api_get_submission/</a>

<hr size="1" />

<?php

$form_id = 1;
$view_id = 1;

$submission = ft_api_get_submission($form_id, $view_id);
print_r($submission);

?>

</body>
</html>
