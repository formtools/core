<?php

/**
 * Actions.php
 *
 * This file handles all server-side responses for Ajax requests. As of 2.0.0, it returns information
 * in JSON format to be handled by JS.
 */

require_once("../library.php");

use FormTools\Core;
use FormTools\Emails;
use FormTools\Fields;
use FormTools\FieldValidation;
use FormTools\Files;
use FormTools\Forms;
use FormTools\General;
use FormTools\ListGroups;
use FormTools\OptionLists;
use FormTools\Sessions;
use FormTools\Settings;
use FormTools\Themes;
use FormTools\Views;

Core::initNoLogout();
$root_url = Core::getRootUrl();
$LANG = Core::$L;

// check the permissions
$permission_check = Core::$user->checkAuth("user", false);

// check the sessions haven't time-outted
$sessions_still_valid = General::checkSessionsTimeout(false);
if (!$sessions_still_valid) {
	@session_destroy();
    Sessions::clearAll();
	$permission_check["has_permission"] = false;
	$permission_check["message"] = "session_expired";
}

// the action to take and the ID of the page where it will be displayed (allows for
// multiple calls on same page to load content in unique areas)
$request = array_merge($_GET, $_POST);
$action  = $request["action"];


if (!$permission_check["has_permission"]) {
	echo constructReturnValue(array(
        "success" => 0,
        "ft_logout" => 1,
        "message" => $permission_check["message"]
    ));
	exit;
}

switch ($action) {
	case "test_folder_permissions":
		list($success, $message) = Files::checkUploadFolder($request["file_upload_dir"]);
		$success = ($success) ? 1 : 0;
		echo constructReturnValue(array("success" => $success, "message" => $message));
		break;

	case "test_folder_url_match":
		list($success, $message) = Files::checkFolderUrlMatch($request["file_upload_dir"], $request["file_upload_url"]);
		$success = ($success) ? 1 : 0;
        echo constructReturnValue(array("success" => $success, "message" => $message));
		break;

	case "clear_cache_folder":
		list($success, $message) = General::clearCacheFolder();
		$success = ($success) ? 1 : 0;
		echo constructReturnValue(array("success" => $success, "message" => $message));
		break;

	// expects the tabset name and inner_tab to contain an alphanumeric string only
	case "remember_inner_tab":
		$tabset = strip_tags($request["tabset"]);
		$tab    = strip_tags($request["tab"]);
		Sessions::createIfNotExists("inner_tabs", array());
		Sessions::set("inner_tabs.{$tabset}", $tab);
		break;

	case "select_submission":
		$form_id       = $request["form_id"];
		$submission_id = $request["submission_id"];

		if (Sessions::isEmpty("form_{$form_id}_select_all_submissions")) {

		    $session_key = "form_{$form_id}_selected_submissions";
		    Sessions::setIfNotExists($session_key, array());
			if (!in_array($submission_id, Sessions::get($session_key))) {
                Sessions::appendArrayItem($session_key, $submission_id);
            }
		} else {
			// if it's in the omit list, remove it
            $omit_list = Sessions::get("form_{$form_id}_all_submissions_selected_omit_list");
			if (in_array($submission_id, $omit_list)) {
                array_splice($omit_list, array_search($submission_id, $omit_list), 1);
            }
		}
		break;

	// this unselects a submission ID from sessions. If the user had previous selected all submissions in the result
	// set, it adds the submission ID to the form's submission ID omit list; otherwise it just logs the submission ID
	// in the form's selected submission ID array
	case "unselect_submission":
		$form_id      = $request["form_id"];
		$submission_id = $request["submission_id"];

		if (Sessions::isEmpty("form_{$form_id}_select_all_submissions")) {
            Sessions::setIfNotExists("form_{$form_id}_selected_submissions", array());
            Sessions::removeArrayItem("form_{$form_id}_selected_submissions", $submission_id);
		} else {
            Sessions::setIfNotExists("form_{$form_id}_all_submissions_selected_omit_list", array());
			if (!in_array($submission_id, Sessions::get("form_{$form_id}_all_submissions_selected_omit_list"))) {
                Sessions::appendArrayItem("form_{$form_id}_all_submissions_selected_omit_list", $submission_id);
            }
		}
		break;

	case "select_submissions":
		$form_id        = $request["form_id"];
		$submission_ids = explode(",", $request["submission_ids"]);

		// user HASN'T selected all submissions
		if (Sessions::exists("form_{$form_id}_select_all_submissions")) {
		    Sessions::setIfNotExists("form_{$form_id}_selected_submissions", array());

		    $selected_submissions = Sessions::get("form_{$form_id}_selected_submissions");
			foreach ($submission_ids as $submission_id) {
				if (!in_array($submission_id, $selected_submissions)) {
				    Sessions::appendArrayItem("form_{$form_id}_selected_submissions", $submission_id);
                }
			}

        // user has already selected all submissions. Here, we actually REMOVE the newly selected submissions from
        // the form submission omit list
		} else {
		    Sessions::setIfNotExists("form_{$form_id}_all_submissions_selected_omit_list", array());
            $omit_list = Sessions::get("form_{$form_id}_all_submissions_selected_omit_list");
			foreach ($submission_ids as $submission_id) {
				if (in_array($submission_id, $omit_list)) {
                    array_splice($omit_list, array_search($submission_id, $omit_list), 1);
                }
			}
		}
		break;

	// this is called when the user has selected all submissions in a result set, regardless of page
	case "select_all_submissions":
		$form_id = $request["form_id"];
		Sessions::set("form_{$form_id}_select_all_submissions", "1");
        Sessions::set("form_{$form_id}_selected_submissions", array()); // empty the specific selected submission
		Sessions::set("form_{$form_id}_all_submissions_selected_omit_list", array());
		break;

	case "unselect_all_submissions":
		$form_id = $request["form_id"];
        Sessions::set("form_{$form_id}_select_all_submissions", "");
        Sessions::set("form_{$form_id}_selected_submissions", array());
        Sessions::set("form_{$form_id}_all_submissions_selected_omit_list", array());
		break;

	case "send_test_email":
		list($success, $message) = Emails::sendTestEmail($request);
		$success = ($success) ? 1 : 0;
        echo constructReturnValue(array("success" => $success, "message" => $message));
		break;

	case "display_test_email":
		$form_id  = Sessions::get("form_id");
		$email_id = Sessions::get("email_id");
		$info = Emails::getEmailComponents($form_id, "", $email_id, true, $request);
		echo returnJSON($info);
		break;

	case "edit_submission_send_email":
		$form_id       = $request["form_id"];
		$submission_id = $request["submission_id"];
		$email_id      = $request["email_id"];

		list($success, $message) = Emails::processEmailTemplate($form_id, $submission_id, $email_id);
		if ($success) {
			$success = 1;
			$message = $LANG["notify_email_sent"];
		} else {
			$edit_email_template_link = "[<a href=\"{$root_url}/admin/forms/edit.php?form_id=$form_id&email_id=$email_id&page=edit_email\">edit email template</a>]";
			$success = 0;
			$message = $LANG["notify_email_not_sent_c"] . mb_strtolower($message) . " " . $edit_email_template_link;
		}
		$message = addslashes($message);
        echo constructReturnValue(array("success" => $success, "message" => $message));
		break;

	case "remember_edit_email_advanced_settings":
		Sessions::set("edit_email_advanced_settings", $request["edit_email_advanced_settings"]);
		break;

	case "smart_fill":
		$scrape_method = $request["scrape_method"];
		$url           = $request["url"];
		switch ($scrape_method) {
			case "file_get_contents":
				$url = General::constructUrl($url, "ft_sessions_url_override=1");
				$html = file_get_contents($url);
				header("Cache-Control: no-cache, must-revalidate");
				header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
				echo $html;
				break;

			case "curl":
				$url = General::constructUrl($url, "ft_sessions_url_override=1");
				$c = curl_init();
				curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($c, CURLOPT_URL, $url);
				$html = curl_exec($c);
				curl_close($c);
				header("Cache-Control: no-cache, must-revalidate");
				header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
				echo $html;
				break;

			case "redirect":
				header("location: $url");
				exit;
		}
		break;

	case "process_smart_fill_contents":
		$form_id = Sessions::get("add_form_form_id");
		Forms::setFormFieldTypes($form_id, $request);

		// finalize the form and redirect to step 6
		$form_info = Forms::getForm($form_id);
		if ($form_info["is_complete"] != 'yes') {
			$response = Forms::finalizeForm($form_id);
			echo returnJSON($response);
		} else {
            echo constructReturnValue(array("success" => 1, "message" => ""));
		}
		break;

	case "get_js_webpage_parse_method":
		$method = General::getJsWebpageParseMethod($request["url"]);
        echo constructReturnValue(array("scrape_method" => $method));
		break;

	// used on the Add Form Step 5 page and Edit Field Options pages. It uploads
	// the files to the /upload folder and returns the filenames (renamed & stored in sessions).
	// That information is then used by the JS to load and process the page content
	case "upload_scraped_pages_for_smart_fill":
		$num_pages = $request["num_pages"];
		$settings = Settings::get(array("file_upload_dir", "file_upload_url"), "core");
		$file_upload_dir = $settings["file_upload_dir"];
		$file_upload_url = $settings["file_upload_url"];
		$upload_tmp_file_prefix = "ft_sf_tmp_";

		Sessions::setIfNotExists("smart_fill_tmp_uploaded_files", array());

		$uploaded_file_info = array();
		$error = false;
		for ($i=1; $i<=$num_pages; $i++) {
			if (!isset($_FILES["form_page_{$i}"])) {
                continue;
            }

			$filename     = $upload_tmp_file_prefix . $_FILES["form_page_{$i}"]["name"];
			$tmp_location = $_FILES["form_page_{$i}"]["tmp_name"];

			list($g_success, $g_message, $final_filename) = Files::uploadFile($file_upload_dir, $filename, $tmp_location);
			if ($g_success) {
				$uploaded_file_info[] = "$file_upload_url/$final_filename";
                $filenames = Sessions::get("smart_fill_tmp_uploaded_files");
                $filenames[] = "$file_upload_dir/$final_filename";
				Sessions::set("smart_fill_tmp_uploaded_files", $filenames);
			} else {
				$error = true;
				break;
			}
		}

		if ($error) {
            echo constructReturnValue(array("success" => 0, "message" => $LANG["notify_smart_fill_upload_fields_fail"]));
		} else {
			$params = array("success" => 1);
			$count = 1;
			foreach ($uploaded_file_info as $url) {
				$params["url_{$count}"] = $url;
				$count++;
			}
            echo constructReturnValue($params);
		}
		break;

	// used on Edit Field Options pages. It uploads the files to the /upload folder and returns the filenames (renamed
	// & stored in sessions). That information is then used by the JS to load and process the page content
	case "upload_scraped_page_for_smart_fill":
		$settings = Settings::get(array("file_upload_dir", "file_upload_url"), "core");
		$file_upload_dir = $settings["file_upload_dir"];
		$file_upload_url = $settings["file_upload_url"];
		$upload_tmp_file_prefix = "ft_sf_tmp_";

        Sessions::setIfNotExists("smart_fill_tmp_uploaded_files", array());

		$uploaded_file_info = array();
		$error = false;

		if (!isset($_FILES["form_page_1"])) {
            exit;
        }

		$filename     = $upload_tmp_file_prefix . $_FILES["form_page_1"]["name"];
		$tmp_location = $_FILES["form_page_1"]["tmp_name"];

		list($g_success, $g_message, $final_filename) = Files::uploadFile($file_upload_dir, $filename, $tmp_location);
		if ($g_success) {
		    Sessions::appendArrayItem("smart_fill_tmp_uploaded_files", "$file_upload_dir/$final_filename");
			header("location: $file_upload_url/$final_filename");
			exit;
		} else {
		    echo constructReturnValue(array("success" => 0, "message" => $LANG["notify_smart_fill_upload_fields_fail"]));
			exit;
		}
		break;

	case "get_upgrade_form_html":
	    $upgrade_url = Core::getUpgradeUrl();
		$components = General::getFormtoolsInstalledComponents();
		echo "<form action=\"$upgrade_url\" id=\"upgrade_form\" method=\"post\" target=\"_blank\">";
		foreach ($components as $key => $value) {
			echo "<input type=\"hidden\" name=\"$key\" value=\"$value\" />\n";
		}
		echo "</form>";
		break;

	case "get_extended_field_settings":
		$field_id      = $request["field_id"];
		$field_type_id = $request["field_type_id"];
		$settings      = Fields::getExtendedFieldSettings($field_id, "", true);
		$validation    = FieldValidation::get($field_id);
		echo constructReturnValue(array(
			"field_id"      => $field_id,
			"field_type_id" => $field_type_id,
			"settings"      => $settings,
			"validation"    => $validation
		));
		break;

	case "get_option_lists":
		$option_lists = OptionLists::getList(array(
		    "per_page" => Sessions::get("settings.num_option_lists_per_page")
        ));
		$option_list_info = array();
		foreach ($option_lists["results"] as $option_list) {
			$option_list_info[$option_list["list_id"]] = $option_list["option_list_name"];
		}
        echo constructReturnValue($option_list_info);
		break;

	// used on the Edit Form -> Fields tab
	case "get_form_list":
		$form_list = Forms::getFormList();
		$forms = array();
		foreach ($form_list as $form_info) {
			$forms[$form_info["form_id"]] = $form_info["form_name"];
		}
        echo constructReturnValue($forms);
		break;

	// used for the Edit Form -> fields tab. Note that any dynamic settings ARE evaluated.
	case "get_form_fields":
		$form_id     = $request["form_id"];
		$field_id    = $request["field_id"];
		$field_order = $request["field_order"];
		$form_fields = Fields::getFormFields($form_id); // array("evaluate_dynamic_settings" => true)
		$fields = array();
		foreach ($form_fields as $field_info) {
			$fields[$field_info["field_id"]] = $field_info["field_title"];
		}
        echo constructReturnValue(array(
			"form_id"     => $form_id,
			"field_id"    => $field_id,
			"field_order" => $field_order,
			"fields"      => $fields
		));
		break;

	case "create_new_view":
		$form_id   = $request["form_id"];
		$group_id  = $request["group_id"];
		$view_name = $request["view_name"];
		$duplicate_view_id = "";

		// here, create_view_from_view_id either contains the ID of the View that the user wants to copy,
		// or "blank_view_no_fields", meaning a totally blank View or "blank_view_all_fields" meaning
		// they want all View fields added by default
		if (isset($request["create_view_from_view_id"]) && !empty($request["create_view_from_view_id"])) {
			$duplicate_view_id = $request["create_view_from_view_id"];
		}

		$view_id = Views::createView($form_id, $group_id, $view_name, $duplicate_view_id);

		// always set the default Edit View tab to the first one
        Sessions::set("edit_view_tab", 1);
        echo constructReturnValue(array("success" => 1, "view_id" => $view_id));
		break;

	case "create_new_view_group":
		$form_id    = Sessions::get("form_id");
		$group_type = "form_{$form_id}_view_group";
		$group_name = $request["group_name"];
		$info = ListGroups::addListGroup($group_type, $group_name);
        echo constructReturnValue($info);
		break;

	case "delete_view":
		$view_id = $request["view_id"];
		Views::deleteView($view_id);
        echo constructReturnValue(array("success" => "1", "view_id" => $view_id));
		break;

	// this is called when the user clicks on the "Save Changes" button on the Edit Field dialog on the
	// Fields tab
	case "update_form_fields":
		$form_id = $request["form_id"];
		$changed_field_ids = $request["data"]["changed_field_ids"];

		// update whatever information has been included in the request
		$problems = array();
		$count = 1;
		$new_field_map = array();
		foreach ($changed_field_ids as $field_id) {
			if (!isset($request["data"]["field_$field_id"])) {
                continue;
            }

			// if this is a NEW field, we just ignore it here. New fields are only added by updating the main page, not
			// via the Edit Field dialog
			if (preg_match("/^NEW/", $field_id)) {
                continue;
            }

			list($success, $message) = Fields::updateField($form_id, $field_id, $request["data"]["field_$field_id"]);
			if (!$success) {
				$problems[] = array("field_id" => $field_id, "error" => $message);
			}
		}

		if (!empty($problems)) {
            echo constructReturnValue(array("success" => 0, "problems" => $problems));
		} else {
            echo constructReturnValue(array("success" => 1));
		}
		break;

	// used to return a page outlining all the form field placeholders available
	case "get_form_field_placeholders":
		$form_id = $request["form_id"];

		$text_reference_tab_info = General::evalSmartyString($LANG["text_reference_tab_info"], array("g_root_url" => $root_url));

		$page_vars = array();
		$page_vars["form_id"] = $form_id;
		$page_vars["form_fields"] = Fields::getFormFields($form_id, array("include_field_type_info" => true));
		$page_vars["text_reference_tab_info"] = $text_reference_tab_info;

		echo constructReturnValue(array(
			"success" => true,
			"html" => Themes::getPage("admin/forms/form_placeholders.tpl", $page_vars)
		));
		break;
}


/**
 * There are two ways to pass data to be returned by these requests in this file:
 * - pass a return_vals string with a `:` delimited key/value pairs
 * - pass a return_vars object
 */
function constructReturnValue($data)
{
    global $request;

    $data_to_return = array();
    if (isset($request["return_vals"])) {
        foreach ($request["return_vals"] as $pair) {
            list($key, $value) = explode(":", $pair);
            $data_to_return[$key] = $value;
        }
    }

    $obj_data_to_return = array();
    if (isset($request["return_vars"]) && is_array($request["return_vars"])) {
        $obj_data_to_return = $request["return_vars"];
    }

    $return_info = array_merge($data_to_return, $obj_data_to_return);

    // this is because if $data has numbers as the keys, array_merge appends them to the end and the keys are lost (sigh).
    foreach ($data as $key => $value) {
        $return_info[$key] = $value;
    }

    return returnJSON($return_info);
}


function returnJSON($php)
{
    header("Content-Type: application/json");
    return json_encode($php);
}
