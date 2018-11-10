<?php
require_once("../examples-config.php");
require_once($path_to_api_v1);
?><!doctype html>
<html>
<head>
</head>
<body>

<h4>Documentation</h4>

See: <a href="https://docs.formtools.org/api/ft_api_create_client_account/" target="_blank">https://docs.formtools.org/api/ft_api_create_client_account/</a>

<hr size="1" />

<?php
$account_info = array(
    "first_name" => "Todd",
    "last_name" => "Atkins",
    "email" => "todd@gmail.com",
    "username" => "todd",
    "password" => "todd12345"
);
list($success, $info) = ft_api_create_client_account($account_info);

if ($success) {
    echo "The account was created. Check your Form Tools interface to see the account - note: refreshing this page will create another account with the same user info.";
} else {
    echo "There was a problem creating this account: ";
    print_r($info);
}
?>

</body>
</html>
