<?php

/**
 * Form Tools API
 * --------------
 *
 * This file is provided for backward compatibility only for users who are using the API 1.x versions and want to
 * upgrade to Form Tools 3. It contains wrapper methods with the old function methods that now call the new API class
 * methods.
 *
 * Please don't use this file! It will be dropped at some point. Use the API.class.php methods directly.
 *
 * Documentation:
 * https://docs.formtools.org/api/
 */

require_once("API.class.php");

use FormTools\API;
use FormTools\Core;


// wrapper functions for the new API class methods
function ft_api_get() {
    return new API();
}

function ft_api_show_submissions($form_id, $view_id, $export_type_id, $page_num = 1, $options = array()) {
    $api = ft_api_get();
    return $api->showSubmissions($form_id, $view_id, $export_type_id, $page_num, $options);
}

function ft_api_show_submission($form_id, $view_id, $export_type_id, $submission_id) {
    $api = ft_api_get();
    $api->showSubmission($form_id, $view_id, $export_type_id, $submission_id);
}

function ft_api_show_submission_count($form_id, $view_id = "") {
    $API = ft_api_get();
    return $API->showSubmissionCount($form_id, $view_id);
}

function ft_api_create_blank_submission($form_id, $finalized = false, $default_values = array()) {
    $api = ft_api_get();
    return $api->createSubmission($form_id, $finalized, $default_values);
}

function ft_api_init_form_page($form_id = "", $mode = "live", $namespace = "form_tools_form") {
    $api = ft_api_get();
    return $api->initFormPage($form_id, $mode, $namespace);
}

function ft_api_clear_form_sessions($namespace = "form_tools_form") {
    $api = ft_api_get();
    $api->clearFormSessions($namespace);
}

function ft_api_process_form($params) {
    $api = ft_api_get();
    return $api->processFormSubmission($params);
}

function ft_api_display_image_field($params) {
    $api = ft_api_get();
    return $api->displayImageField($params);
}

function ft_api_load_field($field_name, $session_name, $default_value) {
    $api = ft_api_get();
    return $api->loadField($field_name, $session_name, $default_value);
}

function ft_api_login($info) {
    $api = new API(array("start_settings" => true));
    return $api->login($info);
}

function ft_api_create_client_account($account_info) {
    $api = ft_api_get();
    return $api->createClientAccount($account_info);
}

function ft_api_update_client_account($account_id, $info) {
    $api = ft_api_get();
    return $api->updateClientAccount($account_id, $info);
}

function ft_api_delete_client_account($account_id) {
    $api = ft_api_get();
    return $api->deleteClientAccount($account_id);
}

function ft_api_delete_unfinalized_submissions($form_id, $delete_all = false) {
    $api = ft_api_get();
    return $api->deleteUnfinalizedSubmissions($form_id, $delete_all);
}

function ft_api_display_captcha() {
    $api = ft_api_get();
    return $api->displayCaptcha();
}

function ft_api_check_submission_is_unique($form_id, $criteria, $current_submission_id = "") {
    $api = ft_api_get();
    return $api->checkSubmissionIsUnique($form_id, $criteria, $current_submission_id);
}

function ft_api_start_sessions() {
    Core::startSessions();
}

function ft_api_display_post_form_captcha_error($message = "") {
    $api = ft_api_get();
    $api->displayPostFormCaptchaError($message);
}

function ft_api_get_submission($form_id, $submission_id) {
    $api = ft_api_get();
    return $api->getSubmission($form_id, $submission_id);
}
