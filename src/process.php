<?php

/**
 * File: process.php
 *
 * This file processes any form submissions for forms already added and configured within Form Tools. To
 * use it, just point your form to this file, like so:
 *
 *   <form method="post" action="/path/to/process.php">
 *
 * Once the form has been added through the Form Tools UI, this script parses the form contents
 * and adds it to the database then redirects the user to whatever page is required. In addition,
 * this script is used to initially set up the form within the database, to map input fields to
 * database columns and types.
 */

use FormTools\Core;
use FormTools\Forms;
use FormTools\Submissions;
use FormTools\Themes;


// always include the core library functions
require_once(__DIR__ . "/global/library.php");

// if the API is supplied, include it as well
@include_once(__DIR__ . "/global/api/api.php");

Core::init();
$LANG = Core::$L;


// check we're receiving something
if (empty($_POST)) {
    $page_vars = array("message_type" => "error", "message" => $LANG["processing_no_post_vars"]);
    Themes::displayPage("error.tpl", $page_vars);
    exit;

// check there's a form ID included
} else if (empty($_POST["form_tools_form_id"])) {
    $page_vars = array("message_type" => "error", "message" => $LANG["processing_no_form_id"]);
    Themes::displayPage("error.tpl", $page_vars);
    exit;

// is this an initialization submission?
} else if (isset($_POST["form_tools_initialize_form"])) {
    Forms::initializeForm($_POST);

// otherwise, it's a regular form submission. Process it!
} else {
    Submissions::processFormSubmission($_POST);
}
