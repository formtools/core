<?php
require_once("../global/api/api.php");
$fields = ft_api_init_form_page(40, "initialize");
$params = array(
  "submit_button" => "submit_button",
  "next_page" => "thanks.php",
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

    <h1>Form Submissions via the API (single-page example)</h1>

    <p>
      This example shows how to use the API functions to submit a form for storage in Form Tools. For illustration
      purposes, all form field types are used over the pages. First put through a form to see how it functions, then
      download the code!
    </p>

	  <table>
	  <tr>
	    <td width="120">Email:</td>
	    <td><input type="text" name="email" value="<?=@$fields["email"]?>" /></td>
	  </tr>
	  <tr>
	    <td>Name:</td>
	    <td><input type="text" name="name" value="<?=@$fields["name"]?>" /></td>
	  </tr>
	  <tr>
	    <td>Gender:</td>
	    <td>
				<input type="radio" name="gender" value="male" id="gen1" <?php if (@$fields["gender"] == "male") echo "checked"; ?> />
				  <label for="gen1">Male</label>
				<input type="radio" name="gender" value="female" id="gen2" <?php if (@$fields["gender"] == "female") echo "checked"; ?> />
				  <label for="gen2">Female</label>
	    </td>
	  </tr>
	  <tr>
	    <td>Age:</td>
	    <td><input type="text" size="5" name="age" value="<?=@$fields["age"]?>" /></td>
	  </tr>
	  </table>

		<p>
		  <input type="submit" name="submit_button" value="Continue &raquo" />
		</p>

  </form>

</body>
</html>