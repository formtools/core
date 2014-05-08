<?php
require_once("../global/api/api.php");
$fields = ft_api_init_form_page();
$params = array(
  "submit_button" => "continue",
  "next_page" => "multi_page_form3.php",
  "form_data" => $_POST,
  "file_data" => $_FILES,
  "no_sessions_url" => "multi_page_form1.php"
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

  <form action="<?=$_SERVER["PHP_SELF"]?>" method="post" enctype="multipart/form-data">

    <h1>Form Submissions via the API (multi-page example)</h1>

    <h4>Page 2 of 3</h4>

	  <table>
	  <tr>
	    <td width="120">Contact Name:</td>
	    <td><input type="text" name="contact_name" value="<?=@htmlspecialchars($fields["contact_name"])?>" /></td>
	  </tr>
	  <tr>
	    <td>Contact Email:</td>
	    <td><input type="text" name="contact_email" value="<?=@htmlspecialchars($fields["contact_email"])?>" /></td>
	  </tr>
	  <tr>
	    <td>Relationship:</td>
	    <td>
	      <select name="relationship">
	        <option value="Friend">Friend</option>
	        <option value="Relative">Relative</option>
	        <option value="Arch-Nemesis">Arch-Nemesis</option>
	      </select>
	    </td>
	  </tr>
	  <tr>
	    <td>File</td>
	    <td>
	      <input type="file" name="regular_file" />

	      <?php
	      $params = array(
	        "field_name" => "regular_file",
	        "width" => 400
	          );
	      ft_api_display_image_field($params);
	      ?>

	    </td>
	  </tr>
	  </table>

		<p>
		  <input type="button" value="&laquo; Previous" onclick="window.location='multi_page_form1.php'" />
		  <input type="submit" name="continue" value="Continue &raquo" />
		</p>

  </form>

</body>
</html>