<?php

/**
 * Undoes the "helpfulness" of Magic Quotes.
 *
 * @param mixed $input
 * @return mixed
 */
function ft_undo_magic_quotes($input)
{
	if (!get_magic_quotes_gpc())
		return $input;

	if (is_array($input))
	{
		$output = array();
		foreach ($input as $k=>$i)
			$output[$k] = ft_undo_magic_quotes($i);
	}
	else
	{
		$output = stripslashes($input);
	}

	return $output;
}


/**
 * Recursively strips tags from an array / string.
 *
 * @param mixed $input an array or string
 * @return mixes
 */
function ft_strip_tags($input)
{
	if (is_array($input))
	{
		$output = array();
		foreach ($input as $k=>$i)
			$output[$k] = ft_strip_tags($i);
	}
	else
	{
		$output = strip_tags($input);
	}

	return $output;
}


/**
 * Checks a user-defined string is a valid MySQL datetime.
 *
 * @param string $datetime
 * @return boolean
 */
function ft_is_valid_datetime($datetime)
{
	if (preg_match("/^(\d{4})-(\d{2})-(\d{2}) ([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/", $datetime, $matches))
	{
		if (checkdate($matches[2], $matches[3], $matches[1]))
			return true;
	}

	return false;
}


/**
 * Also called on the login page. This does a quick test to confirm the database tables exist as they should.
 * If not, it throws a serious error and prevents the user from logging in.
 */
function ft_verify_core_tables_exist()
{
	global $g_table_prefix, $g_ft_tables, $g_db_name;

	$g_db_name = ft_get_clean_db_entity($g_db_name);

	$result = mysql_query("SHOW TABLES FROM $g_db_name");
	$found_tables = array();
	while ($row = mysql_fetch_array($result))
		$found_tables[] = $row[0];

	$all_tables_found = true;
	$missing_tables = array();
	foreach ($g_ft_tables as $table_name)
	{
		if (!in_array("{$g_table_prefix}$table_name", $found_tables))
		{
			$all_tables_found = false;
			$missing_tables[] = "{$g_table_prefix}$table_name";
		}
	}

	if (!$all_tables_found)
	{
		$missing_tables_str = "<blockquote><pre>" . implode("\n", $missing_tables) . "</pre></blockquote>";
		General::displaySeriousError("Form Tools couldn't find all the database tables. Please check your /global/config.php file to confirm the <b>\$g_table_prefix</b> setting. The following tables are missing: {$missing_tables_str}");
		exit;
	}
}


/**
 * A multibyte version of str_split. Splits a string into chunks and returns the pieces in
 * an array.
 *
 * @param string $string The string to manipulate.
 * @param integer $split_length The number of characters in each chunk.
 * @return array an array of chunks, each of size $split_length. The last index contains the leftovers.
 *      If <b>$split_length</b> is less than 1, return false.
 */
function mb_str_split($string, $split_length = 1)
{
	if ($split_length < 1)
		return false;

	$result = array();
	for ($i=0; $i<mb_strlen($string); $i+=$split_length)
		$result[] = mb_substr($string, $i, $split_length);

	return $result;
}


/**
 * Extracted from validate_fields. Simple function to test if a string is an email or not.
 *
 * @param string $str
 * @return boolean
 */
function ft_is_valid_email($str)
{
	$regexp="/^[a-z0-9]+([_\\.-][a-z0-9]+)*@([a-z0-9]+([\.-][a-z0-9]+)*)+\\.[a-z]{2,}$/i";
	return preg_match($regexp, $str);
}


/**
 * Returns a list of MySQL reserved words, to prevent the user accidentally entering a database field name
 * that has a special meaning for MySQL.
 */
function ft_get_mysql_reserved_words()
{
	global $g_root_dir;

	$words = @file("$g_root_dir/global/misc/mysql_reserved_words.txt");

	$clean_words = array();
	foreach ($words as $word)
	{
		$word = trim($word);
		if (!empty($word) && !in_array($word, $clean_words))
			$clean_words[] = $word;
	}

	return $clean_words;
}


/**
 * A case insensitive version of in_array.
 */
function ft_in_array_case_insensitive($value, $array)
{
	foreach ($array as $item)
	{
		if (is_array($item))
			$return = ft_in_array_case_insensitive($value, $item);
		else
			$return = strtolower($item) == strtolower($value);

		if ($return)
			return $return;
	}

	return false;
}


/**
 * A simple helper function to convert any string to a "slug" - an alphanumeric, "_" and/or "-" string
 * for use in (e.g.) generating filenames.
 *
 * @param string $string
 * @return string
 */
function ft_create_slug($string)
{
	$str = trim($string);
	$str = preg_replace('/[^a-zA-Z0-9]/', '_', $str);
	$str = preg_replace('/_{2,}/', "_", $str);

	return $str;
}


/**
 * Generates a random password of a certain length.
 *
 * @param integer $length the number of characters in the password
 * @return string the password
 */
function ft_generate_password($length = 8)
{
	$password = "";
	$possible = "0123456789abcdfghjkmnpqrstvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_-";
	$i=0;

	// add random characters to $password until $length is reached
	while ($i <$length)
	{
		// pick a random character from the possible ones
		$char = substr($possible, mt_rand(0, strlen($possible)-1), 1);

		// we don't want this character if it's already in the password
		if (!strstr($password, $char))
		{
			$password .= $char;
			$i++;
		}
	}

	return $password;
}


// ------------------------------------------------------------------------------------------------


if (!function_exists("mb_strtoupper"))
{
	/**
	 * A fallback function for servers that don't include the mbstring PHP extension. Note:
	 * this function is NOT multi-byte; it can't be emulated without the extension. However,
	 * this will at least allow the user to use Form Tools without errors.
	 *
	 * @param string $str
	 * @return string the uppercased string
	 */
	function mb_strtoupper($str)
	{
		return strtoupper($str);
	}
}

if (!function_exists("mb_strtolower"))
{
	/**
	 * A fallback function for servers that don't include the mbstring PHP extension. Note:
	 * this function is NOT multi-byte; it can't be emulated without the extension. However,
	 * this will at least allow the user to use Form Tools without errors.
	 *
	 * @param string $str
	 * @return string the uppercased string
	 */
	function mb_strtolower($str)
	{
		return strtolower($str);
	}
}

if (!function_exists("mb_strlen"))
{
	/**
	 * A fallback function for servers that don't include the mbstring PHP extension. Note:
	 * this function is NOT multi-byte; it can't be emulated without the extension. However,
	 * this will at least allow the user to use Form Tools without errors.
	 *
	 * @param string $str
	 * @return string the length of the string
	 */
	function mb_strlen($str)
	{
		return strlen($str);
	}
}

if (!function_exists("mb_substr"))
{
	/**
	 * A fallback function for servers that don't include the mbstring PHP extension. Note:
	 * this function is NOT multi-byte; it can't be emulated without the extension. However,
	 * this will at least allow the user to use Form Tools without errors.
	 *
	 * @param string $str
	 * @return string the length of the string
	 */
	function mb_substr($str, $start, $length)
	{
		return substr($str, $start, $length);
	}
}

if (!function_exists("htmlspecialchars_decode"))
{
	function htmlspecialchars_decode($string, $style=ENT_COMPAT)
	{
		$translation = array_flip(get_html_translation_table(HTML_SPECIALCHARS, $style));
		if ($style === ENT_QUOTES)
			$translation['&#039;'] = '\'';

		return strtr($string, $translation);
	}
}

if (!function_exists('mime_content_type'))
{
	function mime_content_type($filename)
	{
		$mime_types = array(
			'txt' => 'text/plain',
			'htm' => 'text/html',
			'html' => 'text/html',
			'php' => 'text/html',
			'css' => 'text/css',
			'js' => 'application/javascript',
			'json' => 'application/json',
			'xml' => 'application/xml',
			'swf' => 'application/x-shockwave-flash',
			'flv' => 'video/x-flv',

			// images
			'png' => 'image/png',
			'jpe' => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'jpg' => 'image/jpeg',
			'gif' => 'image/gif',
			'bmp' => 'image/bmp',
			'ico' => 'image/vnd.microsoft.icon',
			'tiff' => 'image/tiff',
			'tif' => 'image/tiff',
			'svg' => 'image/svg+xml',
			'svgz' => 'image/svg+xml',

			// archives
			'zip' => 'application/zip',
			'rar' => 'application/x-rar-compressed',
			'exe' => 'application/x-msdownload',
			'msi' => 'application/x-msdownload',
			'cab' => 'application/vnd.ms-cab-compressed',

			// audio/video
			'mp3' => 'audio/mpeg',
			'qt' => 'video/quicktime',
			'mov' => 'video/quicktime',

			// adobe
			'pdf' => 'application/pdf',
			'psd' => 'image/vnd.adobe.photoshop',
			'ai' => 'application/postscript',
			'eps' => 'application/postscript',
			'ps' => 'application/postscript',

			// ms office
			'doc' => 'application/msword',
			'rtf' => 'application/rtf',
			'xls' => 'application/vnd.ms-excel',
			'ppt' => 'application/vnd.ms-powerpoint',

			// open office
			'odt' => 'application/vnd.oasis.opendocument.text',
			'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
		);

		$ext = strtolower(array_pop(explode('.', $filename)));
		if (array_key_exists($ext, $mime_types))
		{
			return $mime_types[$ext];
		}
		elseif (function_exists('finfo_open'))
		{
			$finfo = finfo_open(FILEINFO_MIME);
			$mimetype = finfo_file($finfo, $filename);
			finfo_close($finfo);
			return $mimetype;
		}
		else {
			return 'application/octet-stream';
		}
	}
}


/**
 * This was added in 2.1.0. and replaces ft_build_and_cache_upgrade_info() which really wasn't necessary.
 * It returns a hash of information to pass in a hidden form when the user clicks "Update".
 */
function ft_get_formtools_installed_components()
{
	global $g_current_version, $g_release_type, $g_release_date;

	$settings = Settings::get();

	// a hash storing the installed component info
	$components = array();

	// get the main build version
	$program_version = $g_current_version;
	$release_date    = $g_release_date;
	$release_type    = $g_release_type;

	$version = $program_version;
	if ($release_type == "alpha")
	{
		$version = "{$program_version}-alpha-{$release_date}";
	}
	else if ($release_type == "beta")
	{
		$version = "{$program_version}-beta-{$release_date}";
	}

	$components["m"]   = $version;
	$components["rt"]  = $release_type;
	$components["rd"]  = $release_date;
	$components["api"] = $settings["api_version"];

	// not sure about this, but I've added it for backward compatibility, just in case...
	if ($release_type == "beta")
	{
		$components["beta"] = "yes";
		$components["bv"]   = $version;
	}

	// get the theme info
	$themes = Themes::getList();
	$count = 1;
	foreach ($themes as $theme_info) {
		$components["t{$count}"]  = $theme_info["theme_folder"];
		$components["tv{$count}"] = $theme_info["theme_version"];
		$count++;
	}

	// get the module info
	$modules = ft_get_modules();
	$count = 1;
	foreach ($modules as $module_info)
	{
		$components["m{$count}"]  = $module_info["module_folder"];
		$components["mv{$count}"] = $module_info["version"];
		$count++;
	}

	return $components;
}


/**
 * Generates the placeholders for a particular form submission. This is used in the email templates, and here and there
 * for providing placeholder functionality to fields (like the "Edit Submission Label" textfield for a form, where they can
 * enter placeholders populated here).
 *
 * This returns ALL available placeholders for a form, regardless of View.
 *
 * @param integer $form_id
 * @param integer $submission_id
 * @param array $client_info a hash of information about the appropriate user (optional)
 * @return array a hash of placeholders and their replacement values (e.g. $arr["FORMURL"] => 17)
 */
function ft_get_submission_placeholders($form_id, $submission_id, $client_info = "")
{
	global $g_root_url;

	$placeholders = array();

	$settings        = Settings::get();
	$form_info       = Forms::getForm($form_id);
	$submission_info = ft_get_submission($form_id, $submission_id);
	$admin_info      = Administrator::getAdminInfo();
	$file_field_type_ids = FieldTypes::getFileFieldTypeIds();
	$field_types     = FieldTypes::get(true);

	// now loop through the info stored for this particular submission and for this particular field,
	// add the custom submission responses to the placeholder hash

	$form_field_params = array(
		"include_field_type_info"   => true,
		"include_field_settings"    => true,
		"evaluate_dynamic_settings" => true
	);
	$form_fields = Fields::getFormFields($form_id, $form_field_params);

	foreach ($submission_info as $field_info)
	{
		$field_id      = $field_info["field_id"];
		$field_name    = $field_info["field_name"];
		$field_type_id = $field_info["field_type_id"];

		if ($field_info["is_system_field"] == "no")
			$placeholders["QUESTION_$field_name"] = $field_info["field_title"];

		if (in_array($field_type_id, $file_field_type_ids))
		{
			$field_settings = Fields::getFieldSettings($field_id);
			$placeholders["FILENAME_$field_name"] = $field_info["content"];
			$placeholders["FILEURL_$field_name"]  = "{$field_settings["folder_url"]}/{$field_info["content"]}";
		}
		else
		{
			$detailed_field_info = array();
			foreach ($form_fields as $curr_field_info)
			{
				if ($curr_field_info["field_id"] != $field_id)
					continue;

				$detailed_field_info = $curr_field_info;
				break;
			}

			$params = array(
				"form_id"       => $form_id,
				"submission_id" => $submission_id,
				"value"         => $field_info["content"],
				"field_info"    => $detailed_field_info,
				"field_types"   => $field_types,
				"settings"      => $settings,
				"context"       => "email_template"
			);
			$value = FieldTypes::generateViewableField($params);
			$placeholders["ANSWER_$field_name"] = $value;

			// for backward compatibility
			if ($field_name == "core__submission_date")
				$placeholders["SUBMISSIONDATE"] = $value;
			else if ($field_name == "core__last_modified")
				$placeholders["LASTMODIFIEDDATE"] = $value;
			else if ($field_name == "core__ip_address")
				$placeholders["IPADDRESS"] = $value;
		}
	}

	// other misc placeholders
	$placeholders["ADMINEMAIL"]   = $admin_info["email"];
	$placeholders["FORMNAME"]     = $form_info["form_name"];
	$placeholders["FORMURL"]      = $form_info["form_url"];
	$placeholders["SUBMISSIONID"] = $submission_id;
	$placeholders["LOGINURL"]     = $g_root_url . "/index.php";

	if (!empty($client_info))
	{
		$placeholders["EMAIL"]       = $client_info["email"];
		$placeholders["FIRSTNAME"]   = $client_info["first_name"];
		$placeholders["LASTNAME"]    = $client_info["last_name"];
		$placeholders["COMPANYNAME"] = $client_info["company_name"];
	}

	extract(Hooks::processHookCalls("end", compact("placeholders"), array("placeholders")), EXTR_OVERWRITE);

	return $placeholders;
}


/**
 * Added in 2.1.0, to get around a problem with database names having hyphens in them. I named the function
 * generically because it may come in handy for escaping other db aspects, like col names etc.
 *
 * @param string $str
 * @param string
 */
function ft_get_clean_db_entity($str)
{
	if (strpos($str, "-") !== false)
		$str = "`$str`";

	return $str;
}


/**
 * Helper function to remove all empty strings from an array.
 *
 * @param array $array
 * @return array
 */
function ft_array_remove_empty_els($array)
{
	$updated_array = array();
	foreach ($array as $el)
	{
		if (!empty($el))
			$updated_array[] = $el;
	}

	return $updated_array;
}
