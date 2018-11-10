<?php
require_once("../examples-config.php");
require_once($path_to_api_v1);
?><!doctype html>
<html>
<head>
</head>
<body>

<h4>Documentation</h4>

See: <a href="https://docs.formtools.org/api/ft_api_delete_client_account/" target="_blank">https://docs.formtools.org/api/ft_api_delete_client_account/</a>

<hr size="1" />

<?php
$account_id = 3;
ft_api_delete_client_account($account_id);
?>

</body>
</html>
