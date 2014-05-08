<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
	<title>API Tests: ft_api_create_client_account</title>
</head>
<body>

  <h1>ft_api_create_client_account()</h1>

  <?php
  require("../global/api/api.php");
  $new_account_info = array(
    "first_name" => "Todd",
    "last_name" => "Atkins",
    "email" => "todd@gmail.com",
    "username" => "chicken",
    "password" => "12345"
  );
  list($success, $message) = ft_api_create_client_account($new_account_info);

  if ($success)
    echo "Account created! #$message";
  else
    echo "Account not created: $message";
  ?>

</body>
</html>