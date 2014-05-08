<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
	<title>API Tests</title>
</head>
<body>

  <h1>ft_create_blank_submission()</h1>

  <?php
  require("../global/api/api.php");
  $submission_id = ft_create_blank_submission(26);
  echo $submission_id;
  ?>

</body>
</html>