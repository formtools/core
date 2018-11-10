<?php
require_once("../examples-config.php");
require_once($path_to_api_v2);
?><!doctype html>
<html>
<head>
</head>
<body>

<h4>Documentation</h4>

See: <a href="https://docs.formtools.org/api/v2/updateClientAccount/" target="_blank">https://docs.formtools.org/api/v2/updateClientAccount/</a>

<hr size="1" />

<?php

$api = new FormTools\API();

$account_id = 2;
$account_info = array(
    "first_name" => "Todd" . rand(1, 100)
);
list($success, $info) = $api->updateClientAccount($account_id, $account_info);

if ($success) {
    echo "The account was updated. Log into Form Tools to see the first name has been renamed for the client account.";
} else {
    echo "There was a problem creating this account: ";
    print_r($info);
}
?>

</body>
</html>
