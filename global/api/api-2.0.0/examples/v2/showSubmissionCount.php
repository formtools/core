<?php
require_once("../examples-config.php");
require_once($path_to_api_v2);
?><!doctype html>
<html>
<head>
</head>
<body>

<h4>Documentation</h4>

See: <a href="https://docs.formtools.org/api/v2/showSubmissionCount/" target="_blank">https://docs.formtools.org/api/v2/showSubmissionCount/</a>

<hr size="1" />

Number of submissions in form view:
<?php
$api = new FormTools\API();
$form_id = 1;
$view_id = 1;
echo $api->showSubmissionCount($form_id, $view_id);
?>

</body>
</html>
