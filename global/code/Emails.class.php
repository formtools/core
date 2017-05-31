<?php

/**
 * Emails.
 */

// -------------------------------------------------------------------------------------------------

namespace FormTools;


class Emails {

    /**
     * Returns ALL email templates, sorted alphabetically. Currently just used for generating a list
     * of email templates from which to base a new one on.
     *
     * @param integer $form_id
     */
    public static function getEmailTemplateList($form_id)
    {
        $db = Core::$db;

        $db->query("
            SELECT *
            FROM   {PREFIX}email_templates
            WHERE  form_id = :form_id
            ORDER BY email_template_name
        ");
        $db->bind("form_id", $form_id);
        $db->execute();

        $templates = $db->fetchAll();

        $info = array();
        foreach ($templates as $template) {
            $db->query("SELECT view_id FROM {PREFIX}email_template_when_sent_views WHERE email_id = :email_id");
            $db->bind("email_id", $template["email_id"]);
            $db->execute();

            $results = $db->fetchAll();
            $when_sent_view_ids = array();
            foreach ($results as $result) {
                $when_sent_view_ids[] = $result["view_id"];
            }
            $row["when_sent_view_ids"] = $when_sent_view_ids;
            $info[] = $row;
        }

        extract(Hooks::processHookCalls("end", compact("form_id", "info"), array("info")), EXTR_OVERWRITE);

        return $info;
    }


    /**
     * Completely deletes an email template from the system.
     *
     * @param integer $email_id
     * @return array [0] T/F
     *               [1] success / error message
     */
    public static function deleteEmailTemplate($email_id)
    {
        $db = Core::$db;
        $LANG = Core::$L;

        if (empty($email_id) || !is_numeric($email_id)) {
            return array(false, $LANG["validation_invalid_email_id"]);
        }

        $db->query("DELETE FROM {PREFIX}email_templates WHERE email_id = :email_id");
        $db->bind("email_id", $email_id);
        $db->execute();

        $db->query("DELETE FROM {PREFIX}email_template_recipients WHERE email_template_id = :email_id");
        $db->bind("email_id", $email_id);
        $db->execute();

        $db->query("DELETE FROM {PREFIX}email_template_edit_submission_views WHERE email_id = :email_id");
        $db->bind("email_id", $email_id);
        $db->execute();

        return array(true, $LANG["notify_email_template_deleted"]);
    }


    /**
     * This function is called whenever the user clicks the "Create Email" button on the main email list page.
     *
     * @param integer $form_id
     * @param integer $create_email_from_email_id this option parameter lets the user create a new email based on
     *      an existing one, saving them the effort of having to re-enter everything.
     */
    public static function ft_create_blank_email_template($form_id, $create_email_from_email_id = "")
    {
        $db = Core::$db;

        if (empty($create_email_from_email_id)) {
            $db->query("
                INSERT {PREFIX}email_templates (form_id, email_status, email_event_trigger)
                VALUES (:form_id, 'enabled', 'on_submission')
            ");
            $db->bind("form_id", $form_id);
            $db->execute();
            $email_id = $db->getInsertId();
        } else {
            $email_template_info = self::getEmailTemplate($create_email_from_email_id);

            // WISHLIST: to have a generic "copy_table_row" function...
            $query = mysql_query("
              INSERT INTO {PREFIX}email_templates (form_id, email_template_name, email_status,
                view_mapping_type, limit_email_content_to_fields_in_view, email_event_trigger,
                include_on_edit_submission_page, subject, email_from, email_from_account_id, custom_from_name,
                custom_from_email, email_reply_to, email_reply_to_account_id, custom_reply_to_name, custom_reply_to_email,
                html_template, text_template)
                (SELECT form_id, email_template_name, email_status,
                   view_mapping_type, limit_email_content_to_fields_in_view, email_event_trigger,
                   include_on_edit_submission_page, subject, email_from, email_from_account_id, custom_from_name,
                   custom_from_email, email_reply_to, email_reply_to_account_id, custom_reply_to_name, custom_reply_to_email,
                   html_template, text_template
                 FROM {PREFIX}email_templates WHERE email_id = $create_email_from_email_id)
            ");
            $email_id = mysql_insert_id();

            foreach ($email_template_info["recipients"] as $recipient)
            {
                $recipient_user_type    = $recipient["recipient_user_type"];
                $recipient_type         = $recipient["recipient_type"];
                $account_id             = !empty($recipient["account_id"]) ? $recipient["account_id"] : "NULL";
                $form_email_id          = !empty($recipient["form_email_id"]) ? $recipient["form_email_id"] : "NULL";
                $custom_recipient_name  = $recipient["custom_recipient_name"];
                $custom_recipient_email = $recipient["custom_recipient_email"];

                mysql_query("
        INSERT INTO {$g_table_prefix}email_template_recipients (email_template_id, recipient_user_type,
          recipient_type, account_id, form_email_id, custom_recipient_name, custom_recipient_email)
        VALUES ($email_id, '$recipient_user_type', '$recipient_type', $account_id, $form_email_id,
        '$custom_recipient_name', '$custom_recipient_email')
          ") or die(mysql_error());
            }

            foreach ($email_template_info["edit_submission_page_view_ids"] as $view_id)
            {
                mysql_query("
        INSERT INTO {$g_table_prefix}email_template_edit_submission_views (email_id, view_id)
        VALUES ($email_id, $view_id)
          ");
            }

            foreach ($email_template_info["when_sent_view_ids"] as $view_id)
            {
                mysql_query("
        INSERT INTO {$g_table_prefix}email_template_when_sent_views (email_id, view_id)
        VALUES ($email_id, $view_id)
          ");
            }
        }

        extract(Hooks::processHookCalls("end", compact("email_id"), array()), EXTR_OVERWRITE);

        return $email_id;
    }


    /**
     * Returns all information about a particular email template.
     *
     * @param integer $email_id
     * @return array
     */
    public static function getEmailTemplate($email_id)
    {
        $db = Core::$db;

        $db->query("
            SELECT *
            FROM   {PREFIX}email_templates
            WHERE  email_id = :email_id
        ");
        $db->bind("email_id", $email_id);
        $db->execute();

        $email_template = $db->fetch();
        $form_id = $email_template["form_id"];
        $email_template["recipients"] = self::getEmailTemplateRecipients($form_id, $email_id);

        // get the list of Views that should show this email template on the edit submission page
        $db->query("SELECT view_id FROM {PREFIX}email_template_edit_submission_views WHERE email_id = :email_id");
        $db->bind("email_id", $email_id);
        $db->execute();

        $view_ids = array();
        foreach ($db->fetchAll() as $row) {
            $view_ids[] = $row["view_id"];
        }
        $email_template["edit_submission_page_view_ids"] = $view_ids;

        // get the list of Views for which this email template is assigned to be sent
        $db->query("SELECT view_id FROM {PREFIX}email_template_when_sent_views WHERE email_id = :email_id");
        $db->bind("email_id", $email_id);
        $db->execute();

        $view_ids = array();
        foreach ($db->fetchAll() as $row) {
            $view_ids[] = $row["view_id"];
        }
        $email_template["when_sent_view_ids"] = $view_ids;

        extract(Hooks::processHookCalls("end", compact("email_template"), array("email_template")), EXTR_OVERWRITE);

        return $email_template;
    }


    /**
     * Returns a list of all email templates for a form, in no particular order.
     *
     * @param integer $form_id
     * @return array
     */
    public static function getEmailTemplates($form_id, $page_num = 1)
    {
        $db = Core::$db;

        $num_emails_per_page = isset($_SESSION["ft"]["settings"]["num_emails_per_page"]) ? $_SESSION["ft"]["settings"]["num_emails_per_page"] : 10;

        // determine the LIMIT clause
        if (empty($page_num)) {
            $page_num = 1;
        }

        $first_item = ($page_num - 1) * $num_emails_per_page;
        $limit_clause = "LIMIT $first_item, $num_emails_per_page";

        $db->query("
            SELECT *
            FROM   {PREFIX}email_templates
            WHERE  form_id = :form_id
            $limit_clause
        ");
        $db->bind("form_id", $form_id);
        $db->execute();
        $email_templates = $db->fetchAll();

        $db->query("
            SELECT count(*) as c
            FROM   {PREFIX}email_templates
            WHERE  form_id = :form_id
        ");
        $db->bind("form_id", $form_id);
        $db->execute();
        $count_result = $db->fetch();

        $email_info = array();
        foreach ($email_templates as $row) {
            $info = $row;
            $info["recipients"] = self::getEmailTemplateRecipients($form_id, $row["email_id"]);
            $email_info[] = $info;
        }

        $return_hash["results"] = $email_info;
        $return_hash["num_results"]  = $count_result["c"];

        extract(Hooks::processHookCalls("end", compact("form_id", "return_hash"), array("return_hash")), EXTR_OVERWRITE);

        return $return_hash;
    }


    /**
     * Sends a test email to administrators as they are building their email templates - or just to
     * confirm the emails are working properly.
     *
     * @param array $infohash This parameter should be a hash (e.g. $_POST or $_GET) containing the
     *             various fields from the test email form.
     */
    public static function sendTestEmail($info)
    {
        $LANG = Core::$L;

        extract(Hooks::processHookCalls("start", compact("info"), array("info")), EXTR_OVERWRITE);

        $form_id        = $_SESSION["ft"]["form_id"];
        $email_id       = $_SESSION["ft"]["email_id"];
        $submission_id  = (isset($info["submission_id"]) && !empty($info["submission_id"])) ? $info["submission_id"] : "";

        list ($success, $email_info) = self::getEmailComponents($form_id, $submission_id, $email_id, true, $info);
        if (!$success) {
            return array(false, $email_info);
        }

        $recipient = $info["test_email_recipient"];

        // if Swift Mailer is enabled, send the emails with that
        $continue = true;
        if (Modules::checkModuleEnabled("swift_mailer")) {
            $sm_settings = Modules::getModuleSettings("", "swift_mailer");

            if ($sm_settings["swiftmailer_enabled"] == "yes") {
                Modules::includeModule("swift_mailer");

                // we deliberately ignore anything other than the specified recipient
                $email_info["cc"]  = array();
                $email_info["bcc"] = array();
                $email_info["to"]  = array();
                $email_info["to"][] = array("email" => $recipient);

                return swift_send_email($email_info);
                $continue = false;
            }
        }

        if (!$continue) {
            return;
        }

        // construct the email headers
        $eol = _ft_get_email_eol_char();

        $from = "";
        if (isset($email_info["from"]) && !empty($email_info["from"])) {
            $from = (is_array($email_info["from"])) ? join(", ", $email_info["from"]) : $email_info["from"];
            $from = htmlspecialchars_decode($from);
        }

        $reply_to = "";
        if (isset($email_info["reply_to"]) && !empty($email_info["reply_to"])) {
            $reply_to = (is_array($email_info["reply_to"])) ? join(", ", $email_info["reply_to"]) : $email_info["reply_to"];
            $reply_to = htmlspecialchars_decode($reply_to);
        }

        // as with Swift Mailer, we deliberately ignore anything other than the specified recipient
        $header_info = array(
            "eol"      => $eol,
            "from"     => $from,
            "reply_to" => $reply_to,
            "cc"       => "",
            "bcc"      => ""
        );

        // construct the email headers [move to helper function]
        $headers = _ft_get_email_headers($header_info);

        // stores the content for either text or HTML emails (but not both!)
        $message = "";

        if (!empty($email_info["html_content"]) && !empty($email_info["text_content"])) {
            $headers .= _ft_get_multipart_message($email_info["html_content"], $email_info["text_content"], $eol);
        } else if (!empty($email_info["text_content"])) {
            $headers .= "Content-type: text/plain; charset=UTF-8";
            $message = $email_info["text_content"];
        } else if (!empty($email_info["html_content"])) {
            $headers .= "Content-type: text/html; charset=UTF-8";
            $message = $email_info["html_content"];
        }

        $subject = $email_info["subject"];

        if (!@mail($recipient, $subject, $message, $headers)) {
            return array(false, $LANG["notify_test_email_not_sent"]);
        } else {
            return array(true, $LANG["notify_your_email_sent"]);
        }
    }


    /**
     * This handy function figures out the various components of an email and returns them in a hash:
     *      from, reply_to, to, cc, bcc, subject, html_content and text_content
     *
     * This is used both when sending the emails but also for testing. This should be the only place that
     * email content is actually constructed. All other email functions should be using it, regardless of
     * what mechanism actually sends the email.
     *
     * @param integer $form_id
     * @param mixed $submission_id for non-test emails, this is included. For testing, it may be blank.
     * @param integer $email_id
     * @return array
     */
    public static function getEmailComponents($form_id, $submission_id = "", $email_id, $is_test = false, $test_settings = array())
    {
        $LANG = Core::$L;
        $root_dir = Core::getRootDir();
        $default_theme = Core::getDefaultTheme();

        $email_template = self::getEmailTemplate($email_id);

        // if the administrator limited the email content to fields in a particular View, pass those fields to the
        // template - NOT all of the form fields (which is the default)
        if (!empty($email_template["limit_email_content_to_fields_in_view"])) {
            $view_fields = Views::getViewFields($email_template["limit_email_content_to_fields_in_view"]);

            // here, $view_fields just contains the info from the view_fields table. We need the info from the form_fields
            // table instead - since it contains presentation information likely to be needed in the email templates
            $fields_for_email_template = array();
            foreach ($view_fields as $view_field_info) {
                $fields_for_email_template[] = Fields::getFormField($view_field_info["field_id"], array("include_field_type_info" => true));
            }
        } else {
            $fields_for_email_template = Fields::getFormFields($form_id, array("include_field_type_info" => true));
        }

        // this returns a hash with three keys: html_content, text_content and submission_id
        $templates = self::getEmailTemplateContent($form_id, $submission_id, $email_template, $is_test, $test_settings);
        $submission_id = $templates["submission_id"];

        // unfortunately we need this, even though it was just called in _ft_get_email_template_content()
        $submission_info = Submissions::getSubmission($form_id, $submission_id);

        // retrieve the placeholders and their substitutes
        $submission_placeholders = General::getSubmissionPlaceholders($form_id, $submission_id);
        $admin_info = Administrator::getAdminInfo();

        $updated_fields_for_email_template = array();
        foreach ($fields_for_email_template as $field_info) {
            if ($field_info["is_file_field"] == "yes") {
                $field_id = $field_info["field_id"];
                $field_settings = ft_get_field_settings($field_id);
                $field_info["folder_url"]  = $field_settings["folder_url"];
                $field_info["folder_path"] = $field_settings["folder_path"];
                $filename = $field_info["field_name"];
                $field_info["answer"] = $submission_placeholders["FILENAME_{$filename}"];
            }
            $updated_fields_for_email_template[] = $field_info;
        }

        $fields_for_email_template = $updated_fields_for_email_template;

        $updated_fields_for_email_template = array();
        foreach ($fields_for_email_template as $field_info) {
            while (list($placeholder, $value) = each($submission_placeholders)) {
                if ($placeholder != "ANSWER_{$field_info["field_name"]}") {
                    continue;
                }
                $field_info["answer"] = $value;
                break;
            }
            reset($submission_placeholders);
            $updated_fields_for_email_template[] = $field_info;
        }
        $fields_for_email_template = $updated_fields_for_email_template;


        $return_info = array();
        $return_info["email_id"] = $email_id;
        $return_info["attachments"] = array();

        $smarty = new \Smarty();
        $smarty->template_dir = "$root_dir/global/smarty/";
        $smarty->compile_dir  = "$root_dir/themes/$default_theme/cache/";
        $smarty->assign("LANG", $LANG);
        $smarty->assign("fields", $fields_for_email_template);

        if (!empty($templates["text"])) {
            list($templates["text"], $attachments) = self::extractEmailAttachmentInfo($templates["text"], $form_id,
                $submission_placeholders);

            foreach ($attachments as $attachment_info) {
                if (!in_array($attachment_info, $return_info["attachments"])) {
                    $return_info["attachments"][] = $attachment_info;
                }
            }

            $smarty->assign("eval_str", $templates["text"]);
            while (list($key, $value) = each($submission_placeholders)) {
                $smarty->assign($key, $value);
            }
            reset($submission_placeholders);

            $return_info["text_content"] = $smarty->fetch("eval.tpl");
        }

        if (!empty($templates["html"])) {
            list($templates["html"], $attachments) = self::extractEmailAttachmentInfo($templates["html"], $form_id, $submission_placeholders);
            foreach ($attachments as $attachment_info) {
                if (!in_array($attachment_info, $return_info["attachments"])) {
                    $return_info["attachments"][] = $attachment_info;
                }
            }

            $smarty->assign("eval_str", $templates["html"]);
            while (list($key, $value) = each($submission_placeholders)) {
                // convert any newlines chars to page breaks for any answer fields. Hmm...
                if (strpos($key, "ANSWER_") === 0) {
                    $value = nl2br($value);
                }
                $smarty->assign($key, $value);
            }
            $return_info["html_content"] = $smarty->fetch("eval.tpl");
        }


        // compile the "to" / "from" / "reply-to" recipient list, based on this form submission. Virtually
        // everything is already stored in $email_template["recipients"], but needs to be extracted.
        // The notable exception is the FORM EMAIL FIELD information: that has to be constructed separately
        $return_info["to"]  = array();
        $return_info["cc"]  = array();
        $return_info["bcc"] = array();

        foreach ($email_template["recipients"] as $recipient_info) {
            $recipient_type_key = $recipient_info["recipient_type"];
            if ($recipient_info["recipient_type"] == "" || $recipient_info["recipient_type"] == "main") {
                $recipient_type_key = "to";
            }
            if ($recipient_info["recipient_user_type"] == "form_email_field") {
                $header_info = self::getFormEmailFieldHeaders($recipient_info["form_email_id"], $submission_info);
                $user_recipient  = $header_info["recipient_line"];
                $user_first_name = $header_info["first_name"];
                $user_last_name  = $header_info["last_name"];
                $user_email      = $header_info["email"];

                $curr_recipient_info = array(
                    "recipient_line" => $user_recipient,
                    "name"           => "$user_first_name $user_last_name",
                    "email"          => $user_email
                );
            } else {
                $curr_recipient_info = array(
                    "recipient_line" => $recipient_info["final_recipient"],
                    "name"           => $recipient_info["final_name"],
                    "email"          => $recipient_info["final_email"]
                );
            }

            if (!empty($curr_recipient_info["email"])) {
                $return_info[$recipient_type_key][] = $curr_recipient_info;
            }
        }

        $return_info["from"] = array();
        switch ($email_template["email_from"]) {
            case "admin":
                $return_info["from"] = array(
                    "recipient_line" => "{$admin_info["first_name"]} {$admin_info["last_name"]} &lt;{$admin_info["email"]}&gt;",
                    "name"           => "{$admin_info["first_name"]} {$admin_info["last_name"]}",
                    "email"          => $admin_info["email"]
                );
                break;

            case "client":
                $client_info = Accounts::getAccountInfo($email_template["email_from_account_id"]);
                $return_info["from"] = array(
                    "recipient_line" => "{$client_info["first_name"]} {$client_info["last_name"]} &lt;{$client_info["email"]}&gt;",
                    "name"           => "{$client_info["first_name"]} {$client_info["last_name"]}",
                    "email"          => $client_info["email"]
                );
                break;

            case "form_email_field":
                $header_info = self::getFormEmailFieldHeaders($email_template["email_from_form_email_id"], $submission_info);
                $user_recipient  = $header_info["recipient_line"];
                $user_first_name = $header_info["first_name"];
                $user_last_name  = $header_info["last_name"];
                $user_email      = $header_info["email"];
                $return_info["from"] = array(
                    "recipient_line" => $user_recipient,
                    "name"           => "$user_first_name $user_last_name",
                    "email"          => $user_email
                );
                break;

            case "custom":
                $return_info["from"] = array(
                    "recipient_line" => "{$email_template["custom_from_name"]} &lt;{$email_template["custom_from_email"]}&gt;",
                    "name"           => $email_template["custom_from_name"],
                    "email"          => $email_template["custom_from_email"]
                );
                break;
        }

        $return_info["reply_to"] = array();
        switch ($email_template["email_reply_to"]) {
            case "admin":
                $return_info["reply_to"] = array(
                    "recipient_line" => "{$admin_info["first_name"]} {$admin_info["last_name"]} &lt;{$admin_info["email"]}&gt;",
                    "name"           => "{$admin_info["first_name"]} {$admin_info["last_name"]}",
                    "email"          => $admin_info["email"]
                );
                break;

            case "client":
                $client_info = Accounts::getAccountInfo($email_template["email_reply_to_account_id"]);
                $return_info["reply_to"] = array(
                    "recipient_line" => "{$client_info["first_name"]} {$client_info["last_name"]} &lt;{$client_info["email"]}&gt;",
                    "name"           => "{$client_info["first_name"]} {$client_info["last_name"]}",
                    "email"          => $client_info["email"]
                );
                break;

            case "form_email_field":
                $form_email_id = $email_template["email_reply_to_form_email_id"];
                $header_info = self::getFormEmailFieldHeaders($form_email_id, $submission_info);
                $user_recipient  = $header_info["recipient_line"];
                $user_first_name = $header_info["first_name"];
                $user_last_name  = $header_info["last_name"];
                $user_email      = $header_info["email"];
                $return_info["reply_to"] = array(
                    "recipient_line" => $user_recipient,
                    "name"           => "$user_first_name $user_last_name",
                    "email"          => $user_email
                );
                break;

            case "custom":
                $return_info["reply_to"] = array(
                    "recipient_line" => "{$email_template["custom_reply_to_name"]} &lt;{$email_template["custom_reply_to_email"]}&gt;",
                    "name"           => $email_template["custom_reply_to_name"],
                    "email"          => $email_template["custom_reply_to_email"]
                );
                break;
        }

        $return_info["subject"] = General::evalSmartyString($email_template["subject"], $submission_placeholders);

        return array(true, $return_info);
    }


    /**
     * Returns a record from the form_email_field table.
     *
     * @param integer $form_email_id
     * @return array
     */
    public static function getFormEmailFieldInfo($form_email_id)
    {
        $db = Core::$db;

        $db->query("
            SELECT *
            FROM   {PREFIX}form_email_fields
            WHERE  form_email_id = :form_email_id
        ");
        $db->bind("form_email_id", $form_email_id);
        $db->execute();

        return $db->fetch();
    }


    /**
     * This returns a hash of all email patterns, found in the /global/email_patterns/ folder, sorted
     * alphabetically by pattern name. An email pattern is just an example template used for
     * quickly populating the HTML and text email content in the the email templates. [I *would* have
     * called them "email templates", but that term is already being put to good use, and
     * "email template template" is just plain silly].
     *
     * @param integer $form_id
     */
    public static function getEmailPatterns($form_id)
    {
        $pattern_folder = realpath(__DIR__ . "/../emails/patterns");
        $email_template_patterns = parse_ini_file("$pattern_folder/patterns.ini", true);

        $placeholders = array();
        $placeholders["fields"] = Fields::getFormFields($form_id, array("include_field_type_info" => true));

        // get the HTML email patterns
        $html_patterns = array();
        $count = 1;
        while (true) {
            if (!isset($email_template_patterns["html_patterns"]["pattern{$count}_name"])) {
                break;
            }

            $name     = General::evalSmartyString($email_template_patterns["html_patterns"]["pattern{$count}_name"]);
            $optgroup = General::evalSmartyString($email_template_patterns["html_patterns"]["pattern{$count}_optgroup"]);
            $filename = $email_template_patterns["html_patterns"]["pattern{$count}_file"];
            $content  = "";

            if (is_readable("$pattern_folder/$filename") && is_file("$pattern_folder/$filename")) {
                $content = General::evalSmartyString(file_get_contents("$pattern_folder/$filename"), $placeholders);
            }

            // if this has both a name and some email content, log it
            if (!empty($name) && !empty($content)) {
                $html_patterns[] = array(
                    "pattern_name" => $name,
                    "optgroup"     => $optgroup,
                    "content"      => $content
                );
            }
            $count++;
        }

        // get the text email patterns
        $text_patterns = array();
        $count = 1;
        while (true) {
            if (!isset($email_template_patterns["text_patterns"]["pattern{$count}_name"])) {
                break;
            }

            $name     = General::evalSmartyString($email_template_patterns["text_patterns"]["pattern{$count}_name"]);
            $optgroup = General::evalSmartyString($email_template_patterns["text_patterns"]["pattern{$count}_optgroup"]);
            $filename = $email_template_patterns["text_patterns"]["pattern{$count}_file"];
            $content  = "";

            if (is_readable("$pattern_folder/$filename") && is_file("$pattern_folder/$filename")) {
                $content = General::evalSmartyString(file_get_contents("$pattern_folder/$filename"), $placeholders);
            }

            // if this has both a name and some email content, log it
            if (!empty($name) && !empty($content)) {
                $text_patterns[] = array(
                    "pattern_name" => $name,
                    "optgroup"     => $optgroup,
                    "content"      => $content
                );
            }
            $count++;
        }

        extract(Hooks::processHookCalls("end", compact("text_patterns", "html_patterns"), array("text_patterns", "html_patterns")), EXTR_OVERWRITE);

        return array("text_patterns" => $text_patterns, "html_patterns" => $html_patterns);
    }


    /**
     * Returns all email field info that have been registered for this form.
     *
     * @param integer $form_id
     */
    public static function getEmailFields($form_id)
    {
        $db = Core::$db;

        $db->query("
            SELECT *
            FROM   {PREFIX}form_email_fields
            WHERE  form_id = :form_id
        ");
        $db->bind("form_id", $form_id);
        $db->execute();

        $info = array();
        foreach ($db->fetchAll() as $row) {
            $row["email_field_label"]      = ft_get_field_title_by_field_id($row["email_field_id"]);
            $row["first_name_field_label"] = ft_get_field_title_by_field_id($row["first_name_field_id"]);
            $row["last_name_field_label"]  = ft_get_field_title_by_field_id($row["last_name_field_id"]);
            $info[] = $row;
        }

        return $info;
    }



    // -----------------------------------------------------------------------------------------------------------------


    /**
     * Determines the first name, last name and email address used the email headers based on the
     * form and submission info. This can be used for the "from", "reply-to", or "to" fields.
     *
     * @param array $form_info A hash containing all the form information.
     * @param array $submission_info A hash containing all the submission information.
     * @return array string the email header
     */
    private static function getFormEmailFieldHeaders($form_email_id, $submission_info)
    {
        $form_email_field_info = self::getFormEmailFieldInfo($form_email_id);

        // retrieve the user's name and email address from the form submission
        $first_name_field_id = $form_email_field_info["first_name_field_id"];
        $last_name_field_id  = $form_email_field_info["last_name_field_id"];
        $email_field_id      = $form_email_field_info["email_field_id"];

        $submission_first_name = "";
        $submission_last_name  = "";
        $submission_email      = "";

        foreach ($submission_info as $row) {
            if (!empty($first_name_field_id)) {
                if ($row["field_id"] == $first_name_field_id) {
                    $submission_first_name = trim($row["content"]);
                }
            }
            if (!empty($last_name_field_id)) {
                if ($row["field_id"] == $last_name_field_id) {
                    $submission_last_name = trim($row["content"]);
                }
            }
            // email
            if ($row["field_id"] == $email_field_id) {
                $submission_email = trim($row["content"]);
            }
        }

        // now build the header string
        $name = array();
        if (!empty($submission_first_name)) {
            $name[] = $submission_first_name;
        }
        if (!empty($submission_last_name)) {
            $name[] = $submission_last_name;
        }

        $recipient_line = join(" ", $name);

        if (empty($recipient_line) && !empty($submission_email)) {
            $recipient_line = $submission_email;
        } else if (!empty($submission_email)) {
            $recipient_line .= " &lt;$submission_email&gt;";
        }

        // return EVERYTHING
        return array(
            "recipient_line" => $recipient_line,
            "first_name" => $submission_first_name,
            "last_name" => $submission_last_name,
            "email" => $submission_email
        );
    }

}

