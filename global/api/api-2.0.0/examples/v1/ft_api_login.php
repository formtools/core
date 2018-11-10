<?php
require_once("../examples-config.php");
require_once($path_to_api_v1);

$error = "";
if (isset($_POST["username"])) {
    $account_info = array(
        "username" => $_POST["username"],
        "password" => $_POST["password"]
    );
    $error = ft_api_login($account_info);
}
?><!doctype html>
<html>
<head>
</head>
<body>

<h4>Documentation</h4>

See: <a href="https://docs.formtools.org/api/ft_api_login/" target="_blank">https://docs.formtools.org/api/ft_api_login/</a>

<hr size="1" />

<form action="ft_api_login.php" method="post">
    <div>Username: <input type="text" name="username" /></div>
    <div>Password: <input type="password" name="password" /></div>
    <p>
        <input type="submit" value="Login" />
    </p>
    <?php
    if (!empty($error)) {
        print_r($error);
    }
    ?>
</form>

</body>
</html>
