<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
	<title>API Tests: ft_api_show_submissions</title>
</head>
<body>

  <h1>ft_api_show_submissions()</h1>

  <?php
  require("../global/api/api.php");
  $page = (isset($_GET["page"])) ? $_GET["page"] : 1;
  $options = array(
    "show_columns_only" => true,
    "num_per_page" => 5,
    "order" => "name-ASC",
    "return_as_string" => true
  );
  list($success, $html) = ft_api_show_submissions(26, 23, 2, $page, $options);
  echo $html;
  ?>

</body>
</html>