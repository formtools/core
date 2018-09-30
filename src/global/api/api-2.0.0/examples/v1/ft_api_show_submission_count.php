<?php
require_once("../examples-config.php");
require_once($path_to_api_v1);
?><!doctype html>
<html>
<head>
</head>
<body>

<h4>Documentation</h4>

See: <a href="https://docs.formtools.org/api/ft_api_show_submission_count/" target="_blank">https://docs.formtools.org/api/ft_api_show_submission_count/</a>

<hr size="1" />

Number of submissions in form view:
<?php
$form_id = 1;
$view_id = 1;
echo ft_api_show_submission_count($form_id, $view_id);
?>

</body>
</html>
