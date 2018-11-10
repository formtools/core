<?php
require_once("../examples-config.php");
require_once($path_to_api_v1);
?><!doctype html>
<html>
<head>
</head>
<body>

<h4>Documentation</h4>

See: <a href="https://docs.formtools.org/api/ft_api_show_submissions/" target="_blank">https://docs.formtools.org/api/ft_api_show_submissions/</a>

<hr size="1" />

<?php

$page = ft_api_load_field("page", "page", 1);

$form_id = 1;
$view_id = 1;
$export_type_id = 1;

$options = array(
	"show_columns_only" => false,
	"num_per_page" => 5
);

ft_api_show_submissions($form_id, $view_id, $export_type_id, $page, $options);
?>

</body>
</html>
