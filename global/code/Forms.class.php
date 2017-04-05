<?php

/**
 * Forms.
 */

// -------------------------------------------------------------------------------------------------

namespace FormTools;


class Forms {


    /**
     * This function processes the form submissions, after the form has been set up in the database.
     */
    public static function processForm($form_data)
    {
        global $g_table_prefix, $g_multi_val_delimiter, $g_query_str_multi_val_separator, $LANG,
               $g_api_version, $g_api_recaptcha_private_key;

        // ensure the incoming values are escaped
        $form_id = $form_data["form_tools_form_id"];
        $form_info = ft_get_form($form_id);

        // do we have a form for this id?
        if (!ft_check_form_exists($form_id)) {
            $page_vars = array("message_type" => "error", "message" => $LANG["processing_invalid_form_id"]);
            Themes::displayPage("error.tpl", $page_vars);
            exit;
        }

        extract(Hooks::processHookCalls("start", compact("form_info", "form_id", "form_data"), array("form_data")), EXTR_OVERWRITE);

        // check to see if this form has been completely set up
        if ($form_info["is_complete"] == "no") {
            $page_vars = array("message_type" => "error", "message" => $LANG["processing_form_incomplete"]);
            Themes::displayPage("error.tpl", $page_vars);
            exit;
        }

        // check to see if this form has been disabled
        if ($form_info["is_active"] == "no") {
            if (isset($form_data["form_tools_inactive_form_redirect_url"])) {
                header("location: {$form_data["form_tools_inactive_form_redirect_url"]}");
                exit;
            }
            $page_vars = array("message_type" => "error", "message" => $LANG["processing_form_disabled"]);
            Themes::displayPage("error.tpl", $page_vars);
            exit;
        }

        // do we have a form for this id?
        if (!ft_check_form_exists($form_id)) {
            $page_vars = array("message_type" => "error", "message" => $LANG["processing_invalid_form_id"]);
            Themes::displayPage("error.tpl", $page_vars);
            exit;
        }


        // was there a reCAPTCHA response? If so, a recaptcha was just submitted. This generally implies the
        // form page included the API, so check it was entered correctly. If not, return the user to the webpage
        if (isset($g_api_version) && isset($form_data["recaptcha_response_field"])) {
            $passes_captcha = false;
            $recaptcha_challenge_field = $form_data["recaptcha_challenge_field"];
            $recaptcha_response_field  = $form_data["recaptcha_response_field"];

            require_once(__DIR__ . "/global/api/recaptchalib.php");

            $resp = recaptcha_check_answer($g_api_recaptcha_private_key, $_SERVER["REMOTE_ADDR"], $recaptcha_challenge_field, $recaptcha_response_field);

            if ($resp->is_valid) {
                $passes_captcha = true;
            } else {
                // since we need to pass all the info back to the form page we do it by storing the data in sessions. Enable 'em.
                //@ft_api_start_sessions();
                $_SESSION["form_tools_form_data"] = $form_data;
                $_SESSION["form_tools_form_data"]["api_recaptcha_error"] = $resp->error;

                // if there's a form_tools_form_url specified, redirect to that
                if (isset($form_data["form_tools_form_url"])) {
                    header("location: {$form_data["form_tools_form_url"]}");
                    exit;

                    // if not, see if the server has the redirect URL specified
                } else if (isset($_SERVER["HTTP_REFERER"])) {
                    header("location: {$_SERVER["HTTP_REFERER"]}");
                    exit;

                    // no luck! Throw an error
                } else {
                    $page_vars = array("message_type" => "error", "message" => $LANG["processing_no_form_url_for_recaptcha"]);
                    Themes::displayPage("error.tpl", $page_vars);
                    exit;
                }
            }
        }


        // get a list of the custom form fields (i.e. non-system) for this form
        $form_fields = ft_get_form_fields($form_id, array("include_field_type_info" => true));

        $custom_form_fields = array();
        $file_fields = array();
        foreach ($form_fields as $field_info) {
            $field_id        = $field_info["field_id"];
            $is_system_field = $field_info["is_system_field"];
            $field_name      = $field_info["field_name"];

            // ignore system fields
            if ($is_system_field == "yes") {
                continue;
            }

            if ($field_info["is_file_field"] == "no") {
                $custom_form_fields[$field_name] = array(
                "field_id"    => $field_id,
                "col_name"    => $field_info["col_name"],
                "field_title" => $field_info["field_title"],
                "include_on_redirect" => $field_info["include_on_redirect"],
                "field_type_id" => $field_info["field_type_id"],
                "is_date_field" => $field_info["is_date_field"]
                );
            } else {
                $file_fields[] = array(
                "field_id"   => $field_id,
                "field_info" => $field_info
                );
            }
        }

        // now examine the contents of the POST/GET submission and get a list of those fields
        // which we're going to update
        $valid_form_fields = array();
        while (list($form_field, $value) = each($form_data)) {
            // if this field is included, store the value for adding to DB
            if (array_key_exists($form_field, $custom_form_fields)) {
                $curr_form_field = $custom_form_fields[$form_field];

                $cleaned_value = $value;
                if (is_array($value)) {
                    if ($form_info["submission_strip_tags"] == "yes") {
                        for ($i=0; $i<count($value); $i++)
                            $value[$i] = strip_tags($value[$i]);
                    }

                    $cleaned_value = implode("$g_multi_val_delimiter", $value);
                } else {
                    if ($form_info["submission_strip_tags"] == "yes")
                        $cleaned_value = strip_tags($value);
                }

                $valid_form_fields[$curr_form_field["col_name"]] = "'$cleaned_value'";
            }
        }

        $now = General::getCurrentDatetime();
        $ip_address = $_SERVER["REMOTE_ADDR"];

        $col_names = array_keys($valid_form_fields);
        $col_names_str = join(", ", $col_names);
        if (!empty($col_names_str)) {
            $col_names_str .= ", ";
        }

        $col_values = array_values($valid_form_fields);
        $col_values_str = join(", ", $col_values);
        if (!empty($col_values_str)) {
            $col_values_str .= ", ";
        }

        // build our query
        $query = "
            INSERT INTO {$g_table_prefix}form_$form_id ($col_names_str submission_date, last_modified_date, ip_address, is_finalized)
            VALUES ($col_values_str '$now', '$now', '$ip_address', 'yes')
        ";

        // add the submission to the database (if form_tools_ignore_submission key isn't set by either the form or a module)
        $submission_id = "";
        if (!isset($form_data["form_tools_ignore_submission"])) {
            $result = mysql_query($query);

            if (!$result) {
                $page_vars = array("message_type" => "error", "error_code" => 304, "error_type" => "system",
                    "debugging"=> "Failed query in <b>" . __FUNCTION__ . ", " . __FILE__ . "</b>, line " . __LINE__ .
                    ": <i>" . nl2br($query) . "</i>", mysql_error());
                Themes::displayPage("error.tpl", $page_vars);
                exit;
            }

            $submission_id = mysql_insert_id();
            extract(Hooks::processHookCalls("end", compact("form_id", "submission_id"), array()), EXTR_OVERWRITE);
        }


        $redirect_query_params = array();

        // build the redirect query parameter array
        foreach ($form_fields as $field_info) {
            if ($field_info["include_on_redirect"] == "no" || $field_info["is_file_field"] == "yes") {
                continue;
            }

            switch ($field_info["col_name"]) {
                case "submission_id":
                    $redirect_query_params[] = "submission_id=$submission_id";
                    break;
                case "submission_date":
                    $settings = Settings::get();
                    $submission_date_formatted = ft_get_date($settings["default_timezone_offset"], $now, $settings["default_date_format"]);
                    $redirect_query_params[] = "submission_date=" . rawurlencode($submission_date_formatted);
                    break;
                case "last_modified_date":
                    $settings = Settings::get();
                    $submission_date_formatted = ft_get_date($settings["default_timezone_offset"], $now, $settings["default_date_format"]);
                    $redirect_query_params[] = "last_modified_date=" . rawurlencode($submission_date_formatted);
                    break;
                case "ip_address":
                    $redirect_query_params[] = "ip_address=$ip_address";
                    break;

                default:
                    $field_name = $field_info["field_name"];

                    // if $value is an array, convert it to a string, separated by $g_query_str_multi_val_separator
                    if (isset($form_data[$field_name])) {
                        if (is_array($form_data[$field_name])) {
                            $value_str = join($g_query_str_multi_val_separator, $form_data[$field_name]);
                            $redirect_query_params[] = "$field_name=" . rawurlencode($value_str);
                        } else {
                            $redirect_query_params[] = "$field_name=" . rawurlencode($form_data[$field_name]);
                        }
                    }
                    break;
            }
        }

        // only upload files & send emails if we're not ignoring the submission
        if (!isset($form_data["form_tools_ignore_submission"])) {
            // now process any file fields. This is placed after the redirect query param code block above to allow whatever file upload
            // module to append the filename to the query string, if needed
            extract(Hooks::processHookCalls("manage_files", compact("form_id", "submission_id", "file_fields", "redirect_query_params"), array("success", "message", "redirect_query_params")), EXTR_OVERWRITE);

            // send any emails
            ft_send_emails("on_submission", $form_id, $submission_id);
        }

        // if the redirect URL has been specified either in the database or as part of the form
        // submission, redirect the user [form submission form_tools_redirect_url value overrides
        // database value]
        if (!empty($form_info["redirect_url"]) || !empty($form_data["form_tools_redirect_url"])) {
            // build redirect query string
            $redirect_url = (isset($form_data["form_tools_redirect_url"]) && !empty($form_data["form_tools_redirect_url"]))
                ? $form_data["form_tools_redirect_url"] : $form_info["redirect_url"];

            $query_str = "";
            if (!empty($redirect_query_params)) {
                $query_str = join("&", $redirect_query_params);
            }

            if (!empty($query_str)) {
                // only include the ? if it's not already there
                if (strpos($redirect_url, "?")) {
                    $redirect_url .= "&" . $query_str;
                } else {
                    $redirect_url .= "?" . $query_str;
                }
            }

            header("Location: " . $redirect_url);
            exit;
        }

        // the user should never get here! This means that the no redirect URL has been specified
        $page_vars = array("message_type" => "error", "message" => $LANG["processing_no_redirect_url"]);
        Themes::displayPage("error.tpl", $page_vars);
        exit;
    }


    /**
     * Caches the total number of (finalized) submissions in a particular form - or all forms - in the
     * $_SESSION["ft"]["form_{$form_id}_num_submissions"] key. That value is used on the administrator's main Forms
     * page to list the form submission count.
     *
     * @param integer $form_id
     */
    public static function cacheFormStats($form_id = "")
    {
        $db = Core::$db;

        $where_clause = "";
        if (!empty($form_id)) {
            $where_clause = "AND form_id = :form_id";
        }

        $db->query("
            SELECT form_id
            FROM   {PREFIX}forms
            WHERE  is_complete = 'yes'
            $where_clause
        ");
        if (!empty($form_id)) {
            $db->bind("form_id", $form_id);
        }
        $db->execute();

        // loop through all forms, extract the submission count and first submission date
        foreach ($db->fetchAll() as $form_info) {
            $form_id = $form_info["form_id"];

            $db->query("
                SELECT count(*) as c
                FROM   {PREFIX}form_$form_id
                WHERE  is_finalized = 'yes'
            ");
            $db->execute();
            $info = $db->fetch();
            Sessions::set("form_{$form_id}_num_submissions", $info["c"]);
        }
    }


    /**
     * Retrieves information about all forms associated with a particular account. Since 2.0.0 this function lets you
     * SEARCH the forms, but it still returns all results - not a page worth (the reason being: the vast majority of
     * people use Form Tools for a small number of forms < 100) so the form tables are displaying via JS, with all
     * results actually returned and hidden in the page ready to be displayed.
     *
     * @param integer $account_id if blank, return all finalized forms, otherwise returns the forms associated with this
     *                  particular client.
     * @param boolean $is_admin whether or not the user retrieving the data is an administrator or not. If it is, ALL
     *                  forms are retrieved - even those that aren't yet finalized.
     * @param array $search_criteria an optional hash with any of the following keys:
     *                 "status"  - (string) online / offline
     *                 "keyword" - (any string)
     *                 "order"   - (string) form_id-DESC, form_id-ASC, form_name-DESC, form-name-ASC,
     *                             status-DESC, status-ASC
     * @return array returns an array of form hashes
     */
    public static function searchForms($account_id = "", $is_admin = false, $search_criteria = array())
    {
        extract(Hooks::processHookCalls("start", compact("account_id", "is_admin", "search_criteria"), array("search_criteria")), EXTR_OVERWRITE);

        $search_criteria["account_id"] = $account_id;
        $search_criteria["is_admin"]   = $is_admin;
        $results = self::getSearchFormSqlClauses($search_criteria);

        // get the form IDs. All info about the forms will be retrieved in a separate query
        $form_query = mysql_query("
            SELECT form_id
            FROM   {PREFIX}forms
            {$results["where_clause"]}
            {$results["order_clause"]}
        ");

        // now retrieve the basic info (id, first and last name) about each client assigned to this form. This
        // takes into account whether it's a public form or not and if so, what clients are in the omit list
        $client_omitted_from_public_forms = $results["client_omitted_from_public_forms"];
        $form_info = array();
        while ($row = mysql_fetch_assoc($form_query))
        {
            $form_id = $row["form_id"];

            // if this was a search for a single client, filter out those public forms which include their account ID
            // on the form omit list
            if (!empty($client_omitted_from_public_forms) && in_array($form_id, $client_omitted_from_public_forms))
                continue;

            $form_info[] = ft_get_form($form_id);
        }

        extract(Hooks::processHookCalls("end", compact("account_id", "is_admin", "search_criteria", "form_info"), array("form_info")), EXTR_OVERWRITE);

        return $form_info;
    }


    // --------------------------------------------------------------------------------------------

    /**
     * Used in ft_search_forms and ft_get_form_prev_next_links, this function looks at the current search and figures
     * out the WHERE and ORDER BY clauses so that the calling function can retrieve the appropriate form results in
     * the appropriate order.
     *
     * @param array $search_criteria
     * @return array $clauses
     */
    private static function getSearchFormSqlClauses($search_criteria)
    {
        if (!isset($search_criteria["order"])) {
            $search_criteria["order"] = "form_id-DESC";
        }

        // verbose, but at least it prevents any invalid sorting...
        $order_clause = "";
        switch ($search_criteria["order"])
        {
            case "form_id-DESC":
                $order_clause = "form_id DESC";
                break;
            case "form_id-ASC":
                $order_clause = "form_id ASC";
                break;
            case "form_name-ASC":
                $order_clause = "form_name ASC";
                break;
            case "form_name-DESC":
                $order_clause = "form_name DESC";
                break;
            case "form_type-ASC":
                $order_clause = "form_type ASC";
                break;
            case "form_type-DESC":
                $order_clause = "form_type DESC";
                break;
            case "status-DESC":
                $order_clause = "(is_initialized = 'no' AND is_complete = 'no'), is_active = 'no', is_active = 'yes'";
                break;
            case "status-ASC":
                $order_clause = "is_active = 'yes', is_active = 'no', (is_initialized = 'no' AND is_complete = 'no')";
                break;

            default:
                $order_clause = "form_id DESC";
                break;
        }
        $order_clause = "ORDER BY $order_clause";

        $status_clause = "";
        if (isset($search_criteria["status"]))
        {
            switch ($search_criteria["status"])
            {
                case "online":
                    $status_clause = "is_active = 'yes' ";
                    break;
                case "offline":
                    $status_clause = "(is_active = 'no' AND is_complete = 'yes')";
                    break;
                case "incomplete":
                    $status_clause = "(is_initialized = 'no' OR is_complete = 'no')";
                    break;
                default:
                    $status_clause = "";
                    break;
            }
        }

        $keyword_clause = "";
        if (isset($search_criteria["keyword"]) && !empty($search_criteria["keyword"]))
        {
            $search_criteria["keyword"] = trim($search_criteria["keyword"]);
            $string = $search_criteria["keyword"];
            $fields = array("form_name", "form_url", "redirect_url", "form_id");

            $clauses = array();
            foreach ($fields as $field)
                $clauses[] = "$field LIKE '%$string%'";

            $keyword_clause = join(" OR ", $clauses);
        }

        // if a user ID has been specified, find out which forms have been assigned to this client
        // so we can limit our query
        $form_clause = "";

        // this var is populated ONLY for searches on a particular client account. It stores those public forms on
        // which the client is on the Omit List. This value is used at the end of this function to trim the results
        // returned to NOT include those forms
        $client_omitted_from_public_forms = array();

        if (!empty($search_criteria["account_id"]))
        {
            $account_id = $search_criteria["account_id"];
            // a bit weird, but necessary. This adds a special clause to the query so that when it searches for a
            // particular account, it also (a) returns all public forms and (b) only returns those forms that are
            // completed. This is because incomplete forms are still set to access_type = "public".
            // Note: this does NOT take into account the public_form_omit_list - that's handled afterwards, to
            // keep the SQL as simple as possible
            $is_public_clause = "(access_type = 'public')";
            $is_setup_clause = "is_complete = 'yes' AND is_initialized = 'yes'";

            // first, grab all those forms that are explicitly associated with this client
            $query = mysql_query("
      SELECT *
      FROM   {$g_table_prefix}client_forms
      WHERE  account_id = $account_id
        ");

            $form_clauses = array();
            while ($result = mysql_fetch_assoc($query))
                $form_clauses[] = "form_id = {$result['form_id']}";

            if (count($form_clauses) > 1)
                $form_clause = "(((" . join(" OR ", $form_clauses) . ") OR $is_public_clause) AND ($is_setup_clause))";
            else
                $form_clause = isset($form_clauses[0]) ? "(({$form_clauses[0]} OR $is_public_clause) AND ($is_setup_clause))" :
                "($is_public_clause AND ($is_setup_clause))";

            // see if this client account has been omitted from any public forms. If it is, this will be used to
            // filter the results
            $query = mysql_query("SELECT form_id FROM {$g_table_prefix}public_form_omit_list WHERE account_id = $account_id");
            while ($row = mysql_fetch_assoc($query))
                $client_omitted_from_public_forms[] = $row["form_id"];
        }

        $admin_clause = (!$search_criteria["is_admin"]) ? "is_complete = 'yes' AND is_initialized = 'yes'" : "";

        // add up the where clauses
        $where_clauses = array();
        if (!empty($status_clause))  $where_clauses[] = $status_clause;
        if (!empty($keyword_clause)) $where_clauses[] = "($keyword_clause)";
        if (!empty($form_clause))    $where_clauses[] = $form_clause;
        if (!empty($admin_clause))   $where_clauses[] = $admin_clause;

        if (!empty($where_clauses))
            $where_clause = "WHERE " . join(" AND ", $where_clauses);
        else
            $where_clause = "";

        return array(
        "order_clause" => $order_clause,
        "where_clause" => $where_clause,
        "client_omitted_from_public_forms" => $client_omitted_from_public_forms
        );
    }

}
