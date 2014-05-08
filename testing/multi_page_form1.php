<?php
require_once("../global/api/api.php");
$fields = ft_api_init_form_page(37);
$params = array(
  "submit_button" => "continue",
  "next_page" => "multi_page_form2.php",
  "form_data" => $_POST
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

    <h4>Page 1 of 3</h4>

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
	    <td><input type="text" name="name" value="<?=htmlspecialchars(@$fields["name"])?>" /></td>
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
	    <td>Favourite Foods:</td>
	    <td>
	      <input type="checkbox" name="foods[]" value="pizza" <?php if (@in_array("pizza", $fields["foods"])) echo "checked"; ?> />Pizza<br />
	      <input type="checkbox" name="foods[]" value="pasta" <?php if (@in_array("pasta", $fields["foods"])) echo "checked"; ?> />Pasta<br />
	      <input type="checkbox" name="foods[]" value="seafood" <?php if (@in_array("seafood", $fields["foods"])) echo "checked"; ?> />Seafood<br />
	      <input type="checkbox" name="foods[]" value="chinese" <?php if (@in_array("chinese", $fields["foods"])) echo "checked"; ?> />Chinese<br />
	    </td>
	  </tr>
	  <tr>
	    <td>Favourite Authors:</td>
	    <td>
	      <select name="authors[]" size="4" multiple>
	        <option value="bunin" <?php if (@in_array("bunin", $fields["authors"])) echo "selected"; ?>>Bunin</option>
	        <option value="chekhov" <?php if (@in_array("chekhov", $fields["authors"])) echo "selected"; ?>>Chekhov</option>
	        <option value="dostoevsky" <?php if (@in_array("dostoevsky", $fields["authors"])) echo "selected"; ?>>Dostoevsky</option>
	        <option value="gogol" <?php if (@in_array("gogol", $fields["authors"])) echo "selected"; ?>>Gogol</option>
	        <option value="karamzin" <?php if (@in_array("karamzin", $fields["authors"])) echo "selected"; ?>>Karamzin</option>
	        <option value="olesha" <?php if (@in_array("olesha", $fields["authors"])) echo "selected"; ?>>Olesha</option>
	        <option value="pushkin" <?php if (@in_array("pushkin", $fields["authors"])) echo "selected"; ?>>Pushkin</option>
	        <option value="tolstoy" <?php if (@in_array("tolstoy", $fields["authors"])) echo "selected"; ?>>Tolstoy</option>
	        <option value="turgenev" <?php if (@in_array("turgenev", $fields["authors"])) echo "selected"; ?>>Turgenev</option>
	      </select>
	    </td>
	  </tr>
	  <tr>
	    <td>Marital Status:</td>
	    <td>
	      <select name="marital_status">
	        <option value="">Please select</option>
	        <option value="married" <?php if (@$fields["marital_status"] == "married") echo "selected"; ?>>Married</option>
	        <option value="single" <?php if (@$fields["marital_status"] == "single") echo "selected"; ?>>Single</option>
	        <option value="common-law" <?php if (@$fields["marital_status"] == "common-law") echo "selected"; ?>>Common-law</option>
	        <option value="divorced" <?php if (@$fields["marital_status"] == "divorced") echo "selected"; ?>>Divorced</option>
	      </select>
	    </td>
	  </tr>
	  <tr>
	    <td>Age:</td>
	    <td><input type="text" size="5" name="age" value="<?=@$fields["age"]?>" /></td>
	  </tr>
	  </table>

		<p>
		  <input type="submit" name="continue" value="Continue &raquo" />
		</p>

  </form>

</body>
</html>