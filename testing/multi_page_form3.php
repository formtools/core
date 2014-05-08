<?php
require_once("../global/api/api.php");
$fields = ft_api_init_form_page();
$params = array(
  "submit_button" => "continue",
  "next_page" => "multi_page_form4.php",
  "no_sessions_url" => "multi_page_form1.php",
  "form_data" => $_POST,
  "finalize" => true
    );
ft_api_process_form($params);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
	<title>Form Tools API</title>
</head>
<body>

  <form action="<?=$_SERVER["PHP_SELF"]?>" method="post">

    <h1>Form Submissions via the API (multi-page example)</h1>

    <h4>Page 3 of 3</h4>

	  <table>
	  <tr>
	    <td width="120">Mystery Field!:</td>
	    <td><input type="text" name="mystery" value="" /></td>
	  </tr>
	  </table>

    <?php
    ft_api_display_captcha();
    ?>

		<p>
		  <input type="button" value="&laquo; Previous" onclick="window.location='multi_page_form2.php'" />
		  <input type="submit" name="continue" value="Continue &raquo" />
		</p>

  </form>

</body>
</html>