<?php

use FormTools\Core;
use FormTools\Emails;
use FormTools\Templates;

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.display_email_template_dropdown
 * Type:     function
 * Name:     form_view_fields_dropdown
 * Purpose:  used on the edit submission page. This displays a list of whatever email templates have been
 *           configured to be shown on the page. This lets the user define as many email templates as they
 *           wish, each of which can be emailed from the edit submission page by the admin or clients.
 *
 *           The emails are sent via an Ajax request, and the.
 *
 *           The JS for this template is found in manage_submissions.js.
 * -------------------------------------------------------------
 */
function smarty_function_display_email_template_dropdown($params, &$smarty)
{
    $LANG = Core::$L;

    if (!Templates::hasRequiredParams($smarty, $params, array("form_id", "view_id", "submission_id"))) {
        return "";
    }

    $submission_id = $params["submission_id"];
    $view_id       = $params["view_id"];
    $form_id       = $params["form_id"];
    $email_templates = Emails::getEditSubmissionEmailTemplates($form_id, $view_id);

    $html = "";
    if (!empty($email_templates)) {
  	    // potential issue, if the user names their field this... (hence the form_tools prefix)
  	    $html = "<select id=\"form_tools_email_template_id\">
  	                <option value\"\">{$LANG["phrase_please_select"]}</option>";

        foreach ($email_templates as $template_info) {
            $html .= "<option value=\"{$template_info["email_id"]}\">{$template_info["email_template_name"]}</option>\n";
        }

        $html .= "</select>
        
        <input type=\"button\" value=\"{$LANG["phrase_send_email"]}\" onclick=\"ms.edit_submission_page_send_email($submission_id)\" />";
    }

    return $html;
}
