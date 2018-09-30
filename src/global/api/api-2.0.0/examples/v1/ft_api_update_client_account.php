<?php
require_once("../examples-config.php");
require_once($path_to_api_v1);
?><!doctype html>
<html>
<head>
</head>
<body>

<h4>Documentation</h4>

See: <a href="https://docs.formtools.org/api/ft_api_update_client_account/" target="_blank">https://docs.formtools.org/api/ft_api_update_client_account/</a>

<hr size="1" />

<?php
$account_id = 3;
$account_info = array(
    "first_name" => "Todd" . rand(1, 100)
);
list($success, $info) = ft_api_update_client_account($account_id, $account_info);

if ($success) {
    echo "The account was updated. Log into Form Tools to see the first name has been renamed for the client account.";
} else {
    echo "There was a problem updating this account: ";
    print_r($info);
}
?>

</body>
</html>
