<?php

/**
 * This file was added in 2.1.5 to contain meta information about the installation database and files to allow
 * for system validation through the Database Integrity and System Check modules. You're probably not looking
 * for this file, but for the config.php file, one folder up!
 */

$STRUCTURE = array();
$STRUCTURE["tables"] = array();
$STRUCTURE["tables"]["account_settings"] = array(
  array(
    "Field"   => "account_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  ),
  array(
    "Field"   => "setting_name",
    "Type"    => "varchar(255)",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  ),
  array(
    "Field"   => "setting_value",
    "Type"    => "mediumtext",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  )
);
$STRUCTURE["tables"]["accounts"] = array(
  array(
    "Field"   => "account_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  ),
  array(
    "Field"   => "account_type",
    "Type"    => "enum('admin','client')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "client"
  ),
  array(
    "Field"   => "account_status",
    "Type"    => "enum('active','disabled','pending')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "disabled"
  ),
  array(
    "Field"   => "last_logged_in",
    "Type"    => "datetime",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "ui_language",
    "Type"    => "varchar(50)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "en_us"
  ),
  array(
    "Field"   => "timezone_offset",
    "Type"    => "varchar(4)",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "sessions_timeout",
    "Type"    => "varchar(10)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "30"
  ),
  array(
    "Field"   => "date_format",
    "Type"    => "varchar(50)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "M jS, g:i A"
  ),
  array(
    "Field"   => "login_page",
    "Type"    => "varchar(50)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "client_forms"
  ),
  array(
    "Field"   => "logout_url",
    "Type"    => "varchar(255)",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "theme",
    "Type"    => "varchar(50)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "default"
  ),
  array(
    "Field"   => "swatch",
    "Type"    => "varchar(255)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "menu_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "first_name",
    "Type"    => "varchar(100)",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "last_name",
    "Type"    => "varchar(100)",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "email",
    "Type"    => "varchar(200)",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "username",
    "Type"    => "varchar(50)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "password",
    "Type"    => "varchar(50)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "temp_reset_password",
    "Type"    => "varchar(50)",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  )
);
$STRUCTURE["tables"]["client_forms"] = array(
  array(
    "Field"   => "account_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  ),
  array(
    "Field"   => "form_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  )
);
$STRUCTURE["tables"]["client_views"] = array(
  array(
    "Field"   => "account_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  ),
  array(
    "Field"   => "view_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  )
);
$STRUCTURE["tables"]["email_template_edit_submission_views"] = array(
  array(
    "Field"   => "email_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  ),
  array(
    "Field"   => "view_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  )
);
$STRUCTURE["tables"]["email_template_recipients"] = array(
  array(
    "Field"   => "recipient_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  ),
  array(
    "Field"   => "email_template_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "recipient_user_type",
    "Type"    => "enum('admin','client','form_email_field','custom')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "recipient_type",
    "Type"    => "enum('main','cc','bcc')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "main"
  ),
  array(
    "Field"   => "account_id",
    "Type"    => "mediumint(9)",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "form_email_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "custom_recipient_name",
    "Type"    => "varchar(200)",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "custom_recipient_email",
    "Type"    => "varchar(200)",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  )
);
$STRUCTURE["tables"]["email_template_when_sent_views"] = array(
  array(
    "Field"   => "email_id",
    "Type"    => "mediumint(9)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "view_id",
    "Type"    => "mediumint(9)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  )
);
$STRUCTURE["tables"]["email_templates"] = array(
  array(
    "Field"   => "email_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  ),
  array(
    "Field"   => "form_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "email_template_name",
    "Type"    => "varchar(100)",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "email_status",
    "Type"    => "enum('enabled','disabled')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "enabled"
  ),
  array(
    "Field"   => "view_mapping_type",
    "Type"    => "enum('all','specific')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "all"
  ),
  array(
    "Field"   => "limit_email_content_to_fields_in_view",
    "Type"    => "mediumint(9)",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "email_event_trigger",
    "Type"    => "set('on_submission','on_edit','on_delete')",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "include_on_edit_submission_page",
    "Type"    => "enum('no','all_views','specific_views')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "no"
  ),
  array(
    "Field"   => "subject",
    "Type"    => "varchar(255)",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "email_from",
    "Type"    => "enum('admin','client','form_email_field','custom','none')",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "email_from_account_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "email_from_form_email_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "custom_from_name",
    "Type"    => "varchar(100)",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "custom_from_email",
    "Type"    => "varchar(100)",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "email_reply_to",
    "Type"    => "enum('admin','client','form_email_field','custom','none')",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "email_reply_to_account_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "email_reply_to_form_email_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "custom_reply_to_name",
    "Type"    => "varchar(100)",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "custom_reply_to_email",
    "Type"    => "varchar(100)",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "html_template",
    "Type"    => "mediumtext",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "text_template",
    "Type"    => "mediumtext",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  )
);
$STRUCTURE["tables"]["field_options"] = array(
  array(
    "Field"   => "list_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  ),
  array(
    "Field"   => "list_group_id",
    "Type"    => "mediumint(9)",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  ),
  array(
    "Field"   => "option_order",
    "Type"    => "smallint(4)",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  ),
  array(
    "Field"   => "option_value",
    "Type"    => "varchar(255)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "option_name",
    "Type"    => "varchar(255)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "is_new_sort_group",
    "Type"    => "enum('yes','no')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  )
);
$STRUCTURE["tables"]["field_settings"] = array(
  array(
    "Field"   => "field_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  ),
  array(
    "Field"   => "setting_id",
    "Type"    => "mediumint(9)",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  ),
  array(
    "Field"   => "setting_value",
    "Type"    => "mediumtext",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  )
);
$STRUCTURE["tables"]["field_type_setting_options"] = array(
  array(
    "Field"   => "setting_id",
    "Type"    => "mediumint(9)",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  ),
  array(
    "Field"   => "option_text",
    "Type"    => "varchar(255)",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "option_value",
    "Type"    => "varchar(255)",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "option_order",
    "Type"    => "smallint(6)",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  ),
  array(
    "Field"   => "is_new_sort_group",
    "Type"    => "enum('yes','no')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  )
);
$STRUCTURE["tables"]["field_type_settings"] = array(
  array(
    "Field"   => "setting_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  ),
  array(
    "Field"   => "field_type_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "field_label",
    "Type"    => "varchar(255)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "field_setting_identifier",
    "Type"    => "varchar(50)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "field_type",
    "Type"    => "enum('textbox','textarea','radios','checkboxes','select','multi-select','option_list_or_form_field')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "field_orientation",
    "Type"    => "enum('horizontal','vertical','na')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "na"
  ),
  array(
    "Field"   => "default_value_type",
    "Type"    => "enum('static','dynamic')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "static"
  ),
  array(
    "Field"   => "default_value",
    "Type"    => "varchar(255)",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "list_order",
    "Type"    => "smallint(6)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  )
);
$STRUCTURE["tables"]["field_types"] = array(
  array(
    "Field"   => "field_type_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  ),
  array(
    "Field"   => "is_editable",
    "Type"    => "enum('yes','no')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "non_editable_info",
    "Type"    => "mediumtext",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "managed_by_module_id",
    "Type"    => "mediumint(9)",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "field_type_name",
    "Type"    => "varchar(255)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "field_type_identifier",
    "Type"    => "varchar(50)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "group_id",
    "Type"    => "smallint(6)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "is_file_field",
    "Type"    => "enum('yes','no')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "no"
  ),
  array(
    "Field"   => "is_date_field",
    "Type"    => "enum('yes','no')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "no"
  ),
  array(
    "Field"   => "raw_field_type_map",
    "Type"    => "varchar(50)",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "raw_field_type_map_multi_select_id",
    "Type"    => "mediumint(9)",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "list_order",
    "Type"    => "smallint(6)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "compatible_field_sizes",
    "Type"    => "varchar(255)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "view_field_rendering_type",
    "Type"    => "enum('none','php','smarty')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "none"
  ),
  array(
    "Field"   => "view_field_php_function_source",
    "Type"    => "varchar(255)",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "view_field_php_function",
    "Type"    => "varchar(255)",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "view_field_smarty_markup",
    "Type"    => "mediumtext",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "edit_field_smarty_markup",
    "Type"    => "mediumtext",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "php_processing",
    "Type"    => "mediumtext",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "resources_css",
    "Type"    => "mediumtext",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "resources_js",
    "Type"    => "mediumtext",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  )
);
$STRUCTURE["tables"]["field_type_validation_rules"] = array(
  array(
    "Field"   => "rule_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  ),
  array(
    "Field"   => "field_type_id",
    "Type"    => "mediumint(9)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "rsv_rule",
    "Type"    => "varchar(50)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "rule_label",
    "Type"    => "varchar(100)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "rsv_field_name",
    "Type"    => "varchar(255)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "custom_function",
    "Type"    => "varchar(100)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "custom_function_required",
    "Type"    => "enum('yes','no','na')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "na"
  ),
  array(
    "Field"   => "default_error_message",
    "Type"    => "mediumtext",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "list_order",
    "Type"    => "smallint(6)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  )
);
$STRUCTURE["tables"]["field_validation"] = array(
  array(
    "Field"   => "rule_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  ),
  array(
    "Field"   => "field_id",
    "Type"    => "mediumint(9)",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  ),
  array(
    "Field"   => "error_message",
    "Type"    => "mediumtext",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  )
);
$STRUCTURE["tables"]["form_email_fields"] = array(
  array(
    "Field"   => "form_email_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  ),
  array(
    "Field"   => "form_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "email_field_id",
    "Type"    => "mediumint(9)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "first_name_field_id",
    "Type"    => "mediumint(9)",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "last_name_field_id",
    "Type"    => "mediumint(9)",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  )
);
$STRUCTURE["tables"]["form_fields"] = array(
  array(
    "Field"   => "field_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  ),
  array(
    "Field"   => "form_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "0"
  ),
  array(
    "Field"   => "field_name",
    "Type"    => "varchar(255)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "field_test_value",
    "Type"    => "mediumtext",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "field_size",
    "Type"    => "varchar(255)",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => "medium"
  ),
  array(
    "Field"   => "field_type_id",
    "Type"    => "smallint(6)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "1"
  ),
  array(
    "Field"   => "is_system_field",
    "Type"    => "enum('yes','no')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "no"
  ),
  array(
    "Field"   => "data_type",
    "Type"    => "enum('string','number','date')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "string"
  ),
  array(
    "Field"   => "field_title",
    "Type"    => "varchar(100)",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "col_name",
    "Type"    => "varchar(100)",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "list_order",
    "Type"    => "smallint(5) unsigned",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "is_new_sort_group",
    "Type"    => "enum('yes','no')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "yes"
  ),
  array(
    "Field"   => "include_on_redirect",
    "Type"    => "enum('yes','no')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "no"
  )
);
$STRUCTURE["tables"]["forms"] = array(
  array(
    "Field"   => "form_id",
    "Type"    => "mediumint(9) unsigned",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  ),
  array(
    "Field"   => "form_type",
    "Type"    => "enum('internal','external','form_builder')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "external"
  ),
  array(
    "Field"   => "access_type",
    "Type"    => "enum('admin','public','private')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "public"
  ),
  array(
    "Field"   => "submission_type",
    "Type"    => "enum('code','direct')",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "date_created",
    "Type"    => "datetime",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "is_active",
    "Type"    => "enum('yes','no')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "no"
  ),
  array(
    "Field"   => "is_initialized",
    "Type"    => "enum('yes','no')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "no"
  ),
  array(
    "Field"   => "is_complete",
    "Type"    => "enum('yes','no')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "no"
  ),
  array(
    "Field"   => "is_multi_page_form",
    "Type"    => "enum('yes','no')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "no"
  ),
  array(
    "Field"   => "form_name",
    "Type"    => "varchar(255)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "form_url",
    "Type"    => "varchar(255)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "redirect_url",
    "Type"    => "varchar(255)",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "auto_delete_submission_files",
    "Type"    => "enum('yes','no')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "yes"
  ),
  array(
    "Field"   => "submission_strip_tags",
    "Type"    => "enum('yes','no')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "yes"
  ),
  array(
    "Field"   => "edit_submission_page_label",
    "Type"    => "text",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "add_submission_button_label",
    "Type"    => "varchar(255)",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => "{\$LANG.word_add_rightarrow}"
  )
);
$STRUCTURE["tables"]["hook_calls"] = array(
  array(
    "Field"   => "hook_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  ),
  array(
    "Field"   => "hook_type",
    "Type"    => "enum('code','template')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "code"
  ),
  array(
    "Field"   => "action_location",
    "Type"    => "varchar(100)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "module_folder",
    "Type"    => "varchar(255)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "function_name",
    "Type"    => "varchar(255)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "hook_function",
    "Type"    => "varchar(255)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "priority",
    "Type"    => "tinyint(4)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "50"
  )
);
$STRUCTURE["tables"]["hooks"] = array(
  array(
    "Field"   => "id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  ),
  array(
    "Field"   => "hook_type",
    "Type"    => "enum('code','template')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "component",
    "Type"    => "enum('core','api','module')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "filepath",
    "Type"    => "varchar(255)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "action_location",
    "Type"    => "varchar(255)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "function_name",
    "Type"    => "varchar(255)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "params",
    "Type"    => "mediumtext",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "overridable",
    "Type"    => "mediumtext",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  )
);
$STRUCTURE["tables"]["list_groups"] = array(
  array(
    "Field"   => "group_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  ),
  array(
    "Field"   => "group_type",
    "Type"    => "varchar(50)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "group_name",
    "Type"    => "varchar(255)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "custom_data",
    "Type"    => "text",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "list_order",
    "Type"    => "smallint(6)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  )
);
$STRUCTURE["tables"]["menu_items"] = array(
  array(
    "Field"   => "menu_item_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  ),
  array(
    "Field"   => "menu_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "display_text",
    "Type"    => "varchar(100)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "page_identifier",
    "Type"    => "varchar(50)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "custom_options",
    "Type"    => "varchar(255)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "url",
    "Type"    => "varchar(255)",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "is_submenu",
    "Type"    => "enum('yes','no')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "no"
  ),
  array(
    "Field"   => "is_new_sort_group",
    "Type"    => "enum('yes','no')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "yes"
  ),
  array(
    "Field"   => "list_order",
    "Type"    => "smallint(5) unsigned",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  )
);
$STRUCTURE["tables"]["menus"] = array(
  array(
    "Field"   => "menu_id",
    "Type"    => "smallint(5) unsigned",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  ),
  array(
    "Field"   => "menu",
    "Type"    => "varchar(255)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "menu_type",
    "Type"    => "enum('admin','client')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "client"
  )
);
$STRUCTURE["tables"]["modules"] = array(
  array(
    "Field"   => "module_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  ),
  array(
    "Field"   => "is_installed",
    "Type"    => "enum('yes','no')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "no"
  ),
  array(
    "Field"   => "is_enabled",
    "Type"    => "enum('yes','no')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "no"
  ),
  array(
    "Field"   => "is_premium",
    "Type"    => "enum('yes','no')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "no"
  ),
  array(
    "Field"   => "module_key",
    "Type"    => "varchar(15)",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "origin_language",
    "Type"    => "varchar(50)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "module_name",
    "Type"    => "varchar(100)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "module_folder",
    "Type"    => "varchar(100)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "version",
    "Type"    => "varchar(50)",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "author",
    "Type"    => "varchar(200)",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "author_email",
    "Type"    => "varchar(200)",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "author_link",
    "Type"    => "varchar(255)",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "description",
    "Type"    => "mediumtext",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "module_date",
    "Type"    => "datetime",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  )
);
$STRUCTURE["tables"]["module_menu_items"] = array(
  array(
    "Field"   => "menu_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  ),
  array(
    "Field"   => "module_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "display_text",
    "Type"    => "varchar(100)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "url",
    "Type"    => "varchar(255)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "is_submenu",
    "Type"    => "enum('yes','no')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "no"
  ),
  array(
    "Field"   => "list_order",
    "Type"    => "smallint(6)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  )
);
$STRUCTURE["tables"]["multi_page_form_urls"] = array(
  array(
    "Field"   => "form_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  ),
  array(
    "Field"   => "form_url",
    "Type"    => "varchar(255)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "page_num",
    "Type"    => "tinyint(4)",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => "2"
  )
);
$STRUCTURE["tables"]["new_view_submission_defaults"] = array(
  array(
    "Field"   => "view_id",
    "Type"    => "mediumint(9)",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  ),
  array(
    "Field"   => "field_id",
    "Type"    => "mediumint(9)",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  ),
  array(
    "Field"   => "default_value",
    "Type"    => "text",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "list_order",
    "Type"    => "smallint(6)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  )
);
$STRUCTURE["tables"]["option_lists"] = array(
  array(
    "Field"   => "list_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  ),
  array(
    "Field"   => "option_list_name",
    "Type"    => "varchar(100)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "is_grouped",
    "Type"    => "enum('yes','no')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "original_form_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  )
);
$STRUCTURE["tables"]["public_form_omit_list"] = array(
  array(
    "Field"   => "form_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  ),
  array(
    "Field"   => "account_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  )
);
$STRUCTURE["tables"]["public_view_omit_list"] = array(
  array(
    "Field"   => "view_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  ),
  array(
    "Field"   => "account_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  )
);
$STRUCTURE["tables"]["sessions"] = array(
  array(
    "Field"   => "session_id",
    "Type"    => "varchar(100)",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  ),
  array(
    "Field"   => "session_data",
    "Type"    => "text",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "expires",
    "Type"    => "int(11)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "0"
  )
);
$STRUCTURE["tables"]["settings"] = array(
  array(
    "Field"   => "setting_id",
    "Type"    => "mediumint(9)",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  ),
  array(
    "Field"   => "setting_name",
    "Type"    => "varchar(100)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "setting_value",
    "Type"    => "text",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "module",
    "Type"    => "varchar(100)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "core"
  )
);
$STRUCTURE["tables"]["themes"] = array(
  array(
    "Field"   => "theme_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  ),
  array(
    "Field"   => "theme_folder",
    "Type"    => "varchar(100)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "theme_name",
    "Type"    => "varchar(50)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "uses_swatches",
    "Type"    => "enum('yes','no')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "no"
  ),
  array(
    "Field"   => "swatches",
    "Type"    => "mediumtext",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "author",
    "Type"    => "varchar(200)",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "author_email",
    "Type"    => "varchar(255)",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "author_link",
    "Type"    => "varchar(255)",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "theme_link",
    "Type"    => "varchar(255)",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "description",
    "Type"    => "mediumtext",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "is_enabled",
    "Type"    => "enum('yes','no')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "yes"
  ),
  array(
    "Field"   => "theme_version",
    "Type"    => "varchar(50)",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  )
);
$STRUCTURE["tables"]["views"] = array(
  array(
    "Field"   => "view_id",
    "Type"    => "smallint(6)",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  ),
  array(
    "Field"   => "form_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "access_type",
    "Type"    => "enum('admin','public','private','hidden')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "public"
  ),
  array(
    "Field"   => "view_name",
    "Type"    => "varchar(100)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "view_order",
    "Type"    => "smallint(6)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "1"
  ),
  array(
    "Field"   => "is_new_sort_group",
    "Type"    => "enum('yes','no')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "group_id",
    "Type"    => "smallint(6)",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "num_submissions_per_page",
    "Type"    => "smallint(6)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "10"
  ),
  array(
    "Field"   => "default_sort_field",
    "Type"    => "varchar(255)",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "submission_date"
  ),
  array(
    "Field"   => "default_sort_field_order",
    "Type"    => "enum('asc','desc')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "desc"
  ),
  array(
    "Field"   => "may_add_submissions",
    "Type"    => "enum('yes','no')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "yes"
  ),
  array(
    "Field"   => "may_edit_submissions",
    "Type"    => "enum('yes','no')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "yes"
  ),
  array(
    "Field"   => "may_delete_submissions",
    "Type"    => "enum('yes','no')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "yes"
  ),
  array(
    "Field"   => "has_client_map_filter",
    "Type"    => "enum('yes','no')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "no"
  ),
  array(
    "Field"   => "has_standard_filter",
    "Type"    => "enum('yes','no')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "no"
  )
);
$STRUCTURE["tables"]["view_columns"] = array(
  array(
    "Field"   => "view_id",
    "Type"    => "mediumint(9)",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  ),
  array(
    "Field"   => "field_id",
    "Type"    => "mediumint(9)",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  ),
  array(
    "Field"   => "list_order",
    "Type"    => "smallint(6)",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  ),
  array(
    "Field"   => "is_sortable",
    "Type"    => "enum('yes','no')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "auto_size",
    "Type"    => "enum('yes','no')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "yes"
  ),
  array(
    "Field"   => "custom_width",
    "Type"    => "varchar(10)",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "truncate",
    "Type"    => "enum('truncate','no_truncate')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "truncate"
  )
);
$STRUCTURE["tables"]["view_fields"] = array(
  array(
    "Field"   => "view_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  ),
  array(
    "Field"   => "field_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  ),
  array(
    "Field"   => "group_id",
    "Type"    => "mediumint(9)",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "is_editable",
    "Type"    => "enum('yes','no')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "yes"
  ),
  array(
    "Field"   => "is_searchable",
    "Type"    => "enum('yes','no')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "yes"
  ),
  array(
    "Field"   => "list_order",
    "Type"    => "smallint(5) unsigned",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "is_new_sort_group",
    "Type"    => "enum('yes','no')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  )
);
$STRUCTURE["tables"]["view_filters"] = array(
  array(
    "Field"   => "filter_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  ),
  array(
    "Field"   => "view_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "filter_type",
    "Type"    => "enum('standard','client_map')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "standard"
  ),
  array(
    "Field"   => "field_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "operator",
    "Type"    => "enum('equals','not_equals','like','not_like','before','after')",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => "equals"
  ),
  array(
    "Field"   => "filter_values",
    "Type"    => "mediumtext",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  ),
  array(
    "Field"   => "filter_sql",
    "Type"    => "mediumtext",
    "Null"    => "NO",
    "Key"     => "",
    "Default" => ""
  )
);
$STRUCTURE["tables"]["view_tabs"] = array(
  array(
    "Field"   => "view_id",
    "Type"    => "mediumint(8) unsigned",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  ),
  array(
    "Field"   => "tab_number",
    "Type"    => "tinyint(3) unsigned",
    "Null"    => "NO",
    "Key"     => "PRI",
    "Default" => ""
  ),
  array(
    "Field"   => "tab_label",
    "Type"    => "varchar(50)",
    "Null"    => "YES",
    "Key"     => "",
    "Default" => ""
  )
);


$FILES = array(
  "admin/",
  "admin/account/",
  "admin/account/index.php",
  "admin/clients/",
  "admin/clients/add.php",
  "admin/clients/edit.php",
  "admin/clients/index.php",
  "admin/clients/page_forms.php",
  "admin/clients/page_main.php",
  "admin/clients/page_settings.php",
  "admin/forms/",
  "admin/forms/add/",
  "admin/forms/add/index.php",
  "admin/forms/add/internal.php",
  "admin/forms/add/step1.php",
  "admin/forms/add/step2.php",
  "admin/forms/add/step3.php",
  "admin/forms/add/step4.php",
  "admin/forms/add/step5.php",
  "admin/forms/add/step6.php",
  "admin/forms/delete_form.php",
  "admin/forms/edit.php",
  "admin/forms/edit_submission.php",
  "admin/forms/edit_submission__code.php",
  "admin/forms/index.php",
  "admin/forms/option_lists/",
  "admin/forms/option_lists/edit.php",
  "admin/forms/option_lists/index.php",
  "admin/forms/option_lists/page_form_fields.php",
  "admin/forms/option_lists/page_main.php",
  "admin/forms/page_edit_email.php",
  "admin/forms/page_edit_view.php",
  "admin/forms/page_email_settings.php",
  "admin/forms/page_emails.php",
  "admin/forms/page_fields.php",
  "admin/forms/page_main.php",
  "admin/forms/page_public_form_omit_list.php",
  "admin/forms/page_public_view_omit_list.php",
  "admin/forms/page_views.php",
  "admin/forms/submissions.php",
  "admin/index.php",
  "admin/modules/",
  "admin/modules/about.php",
  "admin/modules/index.php",
  "admin/settings/",
  "admin/settings/index.php",
  "admin/settings/page_accounts.php",
  "admin/settings/page_edit_admin_menu.php",
  "admin/settings/page_edit_client_menu.php",
  "admin/settings/page_files.php",
  "admin/settings/page_main.php",
  "admin/settings/page_menus.php",
  "admin/themes/",
  "admin/themes/about.php",
  "admin/themes/index.php",
  "clients/",
  "clients/account/",
  "clients/account/index.php",
  "clients/account/page_main.php",
  "clients/account/page_settings.php",
  "clients/forms/",
  "clients/forms/edit_submission.php",
  "clients/forms/edit_submission__code.php",
  "clients/forms/index.php",
  "clients/index.php",
  "error.php",
  "forget_password.php",
  "global/",
  "global/api/",
  "global/code/",
  "global/code/accounts.php",
  "global/code/actions.php",
  "global/code/administrator.php",
  "global/code/clients.php",
  "global/code/emails.php",
  "global/code/field_sizes.php",
  "global/code/field_types.php",
  "global/code/field_validation.php",
  "global/code/fields.php",
  "global/code/files.php",
  "global/code/forms.php",
  "global/code/general.php",
  "global/code/hooks.php",
  "global/code/index.php",
  "global/code/languages.php",
  "global/code/list_groups.php",
  "global/code/menus.php",
  "global/code/modules.php",
  "global/code/option_lists.php",
  "global/code/sessions.php",
  "global/code/settings.php",
  "global/code/submissions.php",
  "global/code/themes.php",
  "global/code/upgrade.php",
  "global/code/validation.php",
  "global/code/views.php",
  "global/codemirror/",
  "global/codemirror/LICENSE",
  "global/codemirror/contrib/",
  "global/codemirror/contrib/csharp/",
  "global/codemirror/contrib/csharp/css/",
  "global/codemirror/contrib/csharp/css/csharpcolors.css",
  "global/codemirror/contrib/csharp/index.html",
  "global/codemirror/contrib/csharp/js/",
  "global/codemirror/contrib/csharp/js/parsecsharp.js",
  "global/codemirror/contrib/csharp/js/tokenizecsharp.js",
  "global/codemirror/contrib/freemarker/",
  "global/codemirror/contrib/freemarker/LICENSE",
  "global/codemirror/contrib/freemarker/css/",
  "global/codemirror/contrib/freemarker/css/freemarkercolors.css",
  "global/codemirror/contrib/freemarker/index.html",
  "global/codemirror/contrib/freemarker/js/",
  "global/codemirror/contrib/freemarker/js/parsefreemarker.js",
  "global/codemirror/contrib/groovy/",
  "global/codemirror/contrib/groovy/index.html",
  "global/codemirror/contrib/java/",
  "global/codemirror/contrib/java/LICENSE",
  "global/codemirror/contrib/java/css/",
  "global/codemirror/contrib/java/css/javacolors.css",
  "global/codemirror/contrib/java/index.html",
  "global/codemirror/contrib/java/js/",
  "global/codemirror/contrib/java/js/parsejava.js",
  "global/codemirror/contrib/java/js/tokenizejava.js",
  "global/codemirror/contrib/lua/",
  "global/codemirror/contrib/lua/LICENSE",
  "global/codemirror/contrib/lua/css/",
  "global/codemirror/contrib/lua/css/luacolors.css",
  "global/codemirror/contrib/lua/index.html",
  "global/codemirror/contrib/lua/js/",
  "global/codemirror/contrib/lua/js/parselua.js",
  "global/codemirror/contrib/ometa/",
  "global/codemirror/contrib/ometa/LICENSE",
  "global/codemirror/contrib/ometa/css/",
  "global/codemirror/contrib/ometa/css/ometacolors.css",
  "global/codemirror/contrib/ometa/index.html",
  "global/codemirror/contrib/ometa/js/",
  "global/codemirror/contrib/ometa/js/parseometa.js",
  "global/codemirror/contrib/ometa/js/tokenizeometa.js",
  "global/codemirror/contrib/php/",
  "global/codemirror/contrib/php/LICENSE",
  "global/codemirror/contrib/php/css/",
  "global/codemirror/contrib/php/css/phpcolors.css",
  "global/codemirror/contrib/php/index.html",
  "global/codemirror/contrib/php/js/",
  "global/codemirror/contrib/php/js/parsephp.js",
  "global/codemirror/contrib/php/js/parsephphtmlmixed.js",
  "global/codemirror/contrib/php/js/tokenizephp.js",
  "global/codemirror/contrib/plsql/",
  "global/codemirror/contrib/plsql/LICENSE",
  "global/codemirror/contrib/plsql/css/",
  "global/codemirror/contrib/plsql/css/plsqlcolors.css",
  "global/codemirror/contrib/plsql/index.html",
  "global/codemirror/contrib/plsql/js/",
  "global/codemirror/contrib/plsql/js/parseplsql.js",
  "global/codemirror/contrib/python/",
  "global/codemirror/contrib/python/LICENSE",
  "global/codemirror/contrib/python/css/",
  "global/codemirror/contrib/python/css/pythoncolors.css",
  "global/codemirror/contrib/python/index.html",
  "global/codemirror/contrib/python/js/",
  "global/codemirror/contrib/python/js/parsepython.js",
  "global/codemirror/contrib/regex/",
  "global/codemirror/contrib/regex/css/",
  "global/codemirror/contrib/regex/css/js-regexcolors.css",
  "global/codemirror/contrib/regex/css/regexcolors.css",
  "global/codemirror/contrib/regex/index.html",
  "global/codemirror/contrib/regex/js/",
  "global/codemirror/contrib/regex/js/parsejavascript_and_regex.js",
  "global/codemirror/contrib/regex/js/parseregex-unicode.js",
  "global/codemirror/contrib/regex/js/parseregex.js",
  "global/codemirror/contrib/regex/js-regex.html",
  "global/codemirror/contrib/scheme/",
  "global/codemirror/contrib/scheme/LICENSE",
  "global/codemirror/contrib/scheme/css/",
  "global/codemirror/contrib/scheme/css/schemecolors.css",
  "global/codemirror/contrib/scheme/index.html",
  "global/codemirror/contrib/scheme/js/",
  "global/codemirror/contrib/scheme/js/parsescheme.js",
  "global/codemirror/contrib/scheme/js/tokenizescheme.js",
  "global/codemirror/contrib/sql/",
  "global/codemirror/contrib/sql/LICENSE",
  "global/codemirror/contrib/sql/css/",
  "global/codemirror/contrib/sql/css/sqlcolors.css",
  "global/codemirror/contrib/sql/index.html",
  "global/codemirror/contrib/sql/js/",
  "global/codemirror/contrib/sql/js/parsesql.js",
  "global/codemirror/contrib/xquery/",
  "global/codemirror/contrib/xquery/LICENSE",
  "global/codemirror/contrib/xquery/css/",
  "global/codemirror/contrib/xquery/css/xqcolors-dark.css",
  "global/codemirror/contrib/xquery/css/xqcolors.css",
  "global/codemirror/contrib/xquery/css/xqcolors2.css",
  "global/codemirror/contrib/xquery/index.html",
  "global/codemirror/contrib/xquery/js/",
  "global/codemirror/contrib/xquery/js/parsexquery.js",
  "global/codemirror/contrib/xquery/js/tokenizexquery.js",
  "global/codemirror/css/",
  "global/codemirror/css/csscolors.css",
  "global/codemirror/css/docs.css",
  "global/codemirror/css/jscolors.css",
  "global/codemirror/css/people.jpg",
  "global/codemirror/css/sparqlcolors.css",
  "global/codemirror/css/xmlcolors.css",
  "global/codemirror/js/",
  "global/codemirror/js/codemirror.js",
  "global/codemirror/js/editor.js",
  "global/codemirror/js/highlight.js",
  "global/codemirror/js/mirrorframe.js",
  "global/codemirror/js/parsecss.js",
  "global/codemirror/js/parsedummy.js",
  "global/codemirror/js/parsehtmlmixed.js",
  "global/codemirror/js/parsejavascript.js",
  "global/codemirror/js/parsesparql.js",
  "global/codemirror/js/parsexml.js",
  "global/codemirror/js/select.js",
  "global/codemirror/js/stringstream.js",
  "global/codemirror/js/tokenize.js",
  "global/codemirror/js/tokenizejavascript.js",
  "global/codemirror/js/undo.js",
  "global/codemirror/js/util.js",
  "global/css/",
  "global/css/field_types.php",
  "global/css/main.css",
  "global/css/ui.daterangepicker.css",
  "global/emails/",
  "global/emails/forget_password.tpl",
  "global/emails/forget_password_subject.tpl",
  "global/emails/installed.tpl",
  "global/emails/installed_subject.tpl",
  "global/emails/patterns/",
  "global/emails/patterns/html_admin.tpl",
  "global/emails/patterns/html_admin_loop.tpl",
  "global/emails/patterns/html_admin_loop_omit_empty.tpl",
  "global/emails/patterns/html_admin_notification.tpl",
  "global/emails/patterns/html_admin_omit_empty.tpl",
  "global/emails/patterns/html_programming_example.tpl",
  "global/emails/patterns/html_user.tpl",
  "global/emails/patterns/html_user_loop.tpl",
  "global/emails/patterns/html_user_loop_omit_empty.tpl",
  "global/emails/patterns/html_user_notification.tpl",
  "global/emails/patterns/html_user_omit_empty.tpl",
  "global/emails/patterns/index.php",
  "global/emails/patterns/patterns.ini",
  "global/emails/patterns/text_admin.tpl",
  "global/emails/patterns/text_admin_loop.tpl",
  "global/emails/patterns/text_admin_loop_omit_empty.tpl",
  "global/emails/patterns/text_admin_notification.tpl",
  "global/emails/patterns/text_admin_omit_empty.tpl",
  "global/emails/patterns/text_programming_example.tpl",
  "global/emails/patterns/text_user.tpl",
  "global/emails/patterns/text_user_loop.tpl",
  "global/emails/patterns/text_user_loop_omit_empty.tpl",
  "global/emails/patterns/text_user_notification.tpl",
  "global/emails/patterns/text_user_omit_empty.tpl",
  "global/fancybox/",
  "global/fancybox/blank.gif",
  "global/fancybox/fancy_close.png",
  "global/fancybox/fancy_loading.png",
  "global/fancybox/fancy_nav_left.png",
  "global/fancybox/fancy_nav_right.png",
  "global/fancybox/fancy_shadow_e.png",
  "global/fancybox/fancy_shadow_n.png",
  "global/fancybox/fancy_shadow_ne.png",
  "global/fancybox/fancy_shadow_nw.png",
  "global/fancybox/fancy_shadow_s.png",
  "global/fancybox/fancy_shadow_se.png",
  "global/fancybox/fancy_shadow_sw.png",
  "global/fancybox/fancy_shadow_w.png",
  "global/fancybox/fancy_title_left.png",
  "global/fancybox/fancy_title_main.png",
  "global/fancybox/fancy_title_over.png",
  "global/fancybox/fancy_title_right.png",
  "global/fancybox/fancybox-x.png",
  "global/fancybox/fancybox-y.png",
  "global/fancybox/fancybox.png",
  "global/fancybox/jquery.easing-1.3.pack.js",
  "global/fancybox/jquery.fancybox-1.3.4.css",
  "global/fancybox/jquery.fancybox-1.3.4.pack.js",
  "global/fancybox/jquery.mousewheel-3.0.4.pack.js",
  "global/images/",
  "global/images/alert.png",
  "global/images/calendar.png",
  "global/images/clock.png",
  "global/images/error.png",
  "global/images/group_block.png",
  "global/images/group_block_connect.png",
  "global/images/group_block_disconnect.png",
  "global/images/index.php",
  "global/images/info.png",
  "global/images/info_small.png",
  "global/images/lang_placeholder_field_icon.gif",
  "global/images/lang_placeholder_field_icon.png",
  "global/images/loading.gif",
  "global/images/loading_small.gif",
  "global/images/open_new_window.png",
  "global/index.php",
  "global/init_page.php",
  "global/lang/",
  "global/lang/en_us.php",
  "global/lang/index.php",
  "global/library.php",
  "global/license.txt",
  "global/misc/",
  "global/misc/config_core.php",
  "global/misc/index.php",
  "global/misc/mysql_reserved_words.txt",
  "global/readme.txt",
  "global/scripts/",
  "global/scripts/daterangepicker.jquery.js",
  "global/scripts/external_form_smart_fill.js",
  "global/scripts/field_types.php",
  "global/scripts/general.js",
  "global/scripts/index.html",
  "global/scripts/jquery-ui-timepicker-addon.js",
  "global/scripts/jquery.js",
  "global/scripts/manage_client_forms.js",
  "global/scripts/manage_email_templates.js",
  "global/scripts/manage_fields.js",
  "global/scripts/manage_fields.min.js",
  "global/scripts/manage_forms.js",
  "global/scripts/manage_menus.js",
  "global/scripts/manage_modules.js",
  "global/scripts/manage_option_lists.js",
  "global/scripts/manage_submissions.js",
  "global/scripts/manage_views.js",
  "global/scripts/rsv.js",
  "global/scripts/sortable.js",
  "global/session_start.php",
  "global/smarty/",
  "global/smarty/Config_File.class.php",
  "global/smarty/Smarty.class.php",
  "global/smarty/Smarty_Compiler.class.php",
  "global/smarty/debug.tpl",
  "global/smarty/eval.tpl",
  "global/smarty/index.html",
  "global/smarty/internals/",
  "global/smarty/internals/core.assemble_plugin_filepath.php",
  "global/smarty/internals/core.assign_smarty_interface.php",
  "global/smarty/internals/core.create_dir_structure.php",
  "global/smarty/internals/core.display_debug_console.php",
  "global/smarty/internals/core.get_include_path.php",
  "global/smarty/internals/core.get_microtime.php",
  "global/smarty/internals/core.get_php_resource.php",
  "global/smarty/internals/core.is_secure.php",
  "global/smarty/internals/core.is_trusted.php",
  "global/smarty/internals/core.load_plugins.php",
  "global/smarty/internals/core.load_resource_plugin.php",
  "global/smarty/internals/core.process_cached_inserts.php",
  "global/smarty/internals/core.process_compiled_include.php",
  "global/smarty/internals/core.read_cache_file.php",
  "global/smarty/internals/core.rm_auto.php",
  "global/smarty/internals/core.rmdir.php",
  "global/smarty/internals/core.run_insert_handler.php",
  "global/smarty/internals/core.smarty_include_php.php",
  "global/smarty/internals/core.write_cache_file.php",
  "global/smarty/internals/core.write_compiled_include.php",
  "global/smarty/internals/core.write_compiled_resource.php",
  "global/smarty/internals/core.write_file.php",
  "global/smarty/plugins/",
  "global/smarty/plugins/block.textformat.php",
  "global/smarty/plugins/compiler.assign.php",
  "global/smarty/plugins/function.assign_debug_info.php",
  "global/smarty/plugins/function.clients_dropdown.php",
  "global/smarty/plugins/function.config_load.php",
  "global/smarty/plugins/function.counter.php",
  "global/smarty/plugins/function.cycle.php",
  "global/smarty/plugins/function.debug.php",
  "global/smarty/plugins/function.display_account_name.php",
  "global/smarty/plugins/function.display_custom_field.php",
  "global/smarty/plugins/function.display_edit_submission_view_dropdown.php",
  "global/smarty/plugins/function.display_email_template_dropdown.php",
  "global/smarty/plugins/function.display_field_type_name.php",
  "global/smarty/plugins/function.display_field_type_settings_dropdown.php",
  "global/smarty/plugins/function.display_field_types_dropdown.php",
  "global/smarty/plugins/function.display_file_field.php",
  "global/smarty/plugins/function.display_form_name.php",
  "global/smarty/plugins/function.display_multi_select_field_values.php",
  "global/smarty/plugins/function.display_option_list.php",
  "global/smarty/plugins/function.display_view_name.php",
  "global/smarty/plugins/function.dropdown.php",
  "global/smarty/plugins/function.edit_custom_field.php",
  "global/smarty/plugins/function.email_patterns_dropdown.php",
  "global/smarty/plugins/function.eval.php",
  "global/smarty/plugins/function.eval_smarty_string.php",
  "global/smarty/plugins/function.fetch.php",
  "global/smarty/plugins/function.field_sizes_dropdown.php",
  "global/smarty/plugins/function.form_fields_dropdown.php",
  "global/smarty/plugins/function.form_view_fields_dropdown.php",
  "global/smarty/plugins/function.forms_dropdown.php",
  "global/smarty/plugins/function.ft_include.php",
  "global/smarty/plugins/function.html_checkboxes.php",
  "global/smarty/plugins/function.html_image.php",
  "global/smarty/plugins/function.html_options.php",
  "global/smarty/plugins/function.html_radios.php",
  "global/smarty/plugins/function.html_select_date.php",
  "global/smarty/plugins/function.html_select_time.php",
  "global/smarty/plugins/function.html_table.php",
  "global/smarty/plugins/function.languages_dropdown.php",
  "global/smarty/plugins/function.mailto.php",
  "global/smarty/plugins/function.math.php",
  "global/smarty/plugins/function.menus_dropdown.php",
  "global/smarty/plugins/function.module_function.php",
  "global/smarty/plugins/function.option_list_dropdown.php",
  "global/smarty/plugins/function.pages_dropdown.php",
  "global/smarty/plugins/function.popup.php",
  "global/smarty/plugins/function.popup_init.php",
  "global/smarty/plugins/function.show_page_load_time.php",
  "global/smarty/plugins/function.submission_dropdown.php",
  "global/smarty/plugins/function.submission_dropdown_multiple.php",
  "global/smarty/plugins/function.submission_listing_quicklinks.php",
  "global/smarty/plugins/function.template_hook.php",
  "global/smarty/plugins/function.themes_dropdown.php",
  "global/smarty/plugins/function.timezone_offset_dropdown.php",
  "global/smarty/plugins/function.view_fields.php",
  "global/smarty/plugins/function.views_dropdown.php",
  "global/smarty/plugins/modifier.capitalize.php",
  "global/smarty/plugins/modifier.cat.php",
  "global/smarty/plugins/modifier.count_characters.php",
  "global/smarty/plugins/modifier.count_paragraphs.php",
  "global/smarty/plugins/modifier.count_sentences.php",
  "global/smarty/plugins/modifier.count_words.php",
  "global/smarty/plugins/modifier.custom_format_date.php",
  "global/smarty/plugins/modifier.date_format.php",
  "global/smarty/plugins/modifier.debug_print_var.php",
  "global/smarty/plugins/modifier.default.php",
  "global/smarty/plugins/modifier.escape.php",
  "global/smarty/plugins/modifier.indent.php",
  "global/smarty/plugins/modifier.lower.php",
  "global/smarty/plugins/modifier.nl2br.php",
  "global/smarty/plugins/modifier.regex_replace.php",
  "global/smarty/plugins/modifier.replace.php",
  "global/smarty/plugins/modifier.spacify.php",
  "global/smarty/plugins/modifier.string_format.php",
  "global/smarty/plugins/modifier.strip.php",
  "global/smarty/plugins/modifier.strip_tags.php",
  "global/smarty/plugins/modifier.truncate.php",
  "global/smarty/plugins/modifier.upper.php",
  "global/smarty/plugins/modifier.wordwrap.php",
  "global/smarty/plugins/outputfilter.trimwhitespace.php",
  "global/smarty/plugins/shared.escape_special_chars.php",
  "global/smarty/plugins/shared.make_timestamp.php",
  "index.php",
  "install/",
  "install/files/",
  "install/files/code.php",
  "install/files/main.css",
  "install/files/sql.php",
  "install/index.php",
  "install/library.php",
  "install/step2.php",
  "install/step3.php",
  "install/step4.php",
  "install/step5.php",
  "install/step6.php",
  "install/templates/",
  "install/templates/index.tpl",
  "install/templates/install_footer.tpl",
  "install/templates/install_header.tpl",
  "install/templates/step2.tpl",
  "install/templates/step3.tpl",
  "install/templates/step4.tpl",
  "install/templates/step5.tpl",
  "install/templates/step6.tpl",
  "modules/",
  "modules/index.php",
  "process.php",
  "themes/",
  "themes/default/",
  "themes/default/about/",
  "themes/default/about/screenshot.gif",
  "themes/default/about/theme.php",
  "themes/default/about/thumbnail.gif",
  "themes/default/admin/",
  "themes/default/admin/account/",
  "themes/default/admin/account/index.tpl",
  "themes/default/admin/clients/",
  "themes/default/admin/clients/add.tpl",
  "themes/default/admin/clients/edit.tpl",
  "themes/default/admin/clients/index.tpl",
  "themes/default/admin/clients/tab_forms.tpl",
  "themes/default/admin/clients/tab_main.tpl",
  "themes/default/admin/clients/tab_settings.tpl",
  "themes/default/admin/forms/",
  "themes/default/admin/forms/add/",
  "themes/default/admin/forms/add/index.tpl",
  "themes/default/admin/forms/add/internal.tpl",
  "themes/default/admin/forms/add/step1.tpl",
  "themes/default/admin/forms/add/step2.tpl",
  "themes/default/admin/forms/add/step3.tpl",
  "themes/default/admin/forms/add/step4.tpl",
  "themes/default/admin/forms/add/step5.tpl",
  "themes/default/admin/forms/add/step6.tpl",
  "themes/default/admin/forms/delete_form.tpl",
  "themes/default/admin/forms/edit.tpl",
  "themes/default/admin/forms/edit_submission.tpl",
  "themes/default/admin/forms/form_placeholders.tpl",
  "themes/default/admin/forms/index.tpl",
  "themes/default/admin/forms/option_lists/",
  "themes/default/admin/forms/option_lists/edit.tpl",
  "themes/default/admin/forms/option_lists/index.tpl",
  "themes/default/admin/forms/option_lists/tab_form_fields.tpl",
  "themes/default/admin/forms/option_lists/tab_main.tpl",
  "themes/default/admin/forms/submissions.tpl",
  "themes/default/admin/forms/tab_edit_email.tpl",
  "themes/default/admin/forms/tab_edit_email_tab1.tpl",
  "themes/default/admin/forms/tab_edit_email_tab2.tpl",
  "themes/default/admin/forms/tab_edit_email_tab3.tpl",
  "themes/default/admin/forms/tab_edit_email_tab4.tpl",
  "themes/default/admin/forms/tab_edit_view.tpl",
  "themes/default/admin/forms/tab_edit_view__fields.tpl",
  "themes/default/admin/forms/tab_edit_view__filters.tpl",
  "themes/default/admin/forms/tab_edit_view__list_page.tpl",
  "themes/default/admin/forms/tab_edit_view__main.tpl",
  "themes/default/admin/forms/tab_edit_view__tabs.tpl",
  "themes/default/admin/forms/tab_email_settings.tpl",
  "themes/default/admin/forms/tab_emails.tpl",
  "themes/default/admin/forms/tab_fields.tpl",
  "themes/default/admin/forms/tab_main.tpl",
  "themes/default/admin/forms/tab_public_form_omit_list.tpl",
  "themes/default/admin/forms/tab_public_view_omit_list.tpl",
  "themes/default/admin/forms/tab_views.tpl",
  "themes/default/admin/index.html",
  "themes/default/admin/modules/",
  "themes/default/admin/modules/about.tpl",
  "themes/default/admin/modules/index.tpl",
  "themes/default/admin/settings/",
  "themes/default/admin/settings/index.tpl",
  "themes/default/admin/settings/tab_accounts.tpl",
  "themes/default/admin/settings/tab_edit_admin_menu.tpl",
  "themes/default/admin/settings/tab_edit_client_menu.tpl",
  "themes/default/admin/settings/tab_files.tpl",
  "themes/default/admin/settings/tab_main.tpl",
  "themes/default/admin/settings/tab_menus.tpl",
  "themes/default/admin/themes/",
  "themes/default/admin/themes/about.tpl",
  "themes/default/admin/themes/index.tpl",
  "themes/default/cache/",
  "themes/default/cache/index.html",
  "themes/default/clients/",
  "themes/default/clients/account/",
  "themes/default/clients/account/index.tpl",
  "themes/default/clients/account/tab_main.tpl",
  "themes/default/clients/account/tab_settings.tpl",
  "themes/default/clients/forms/",
  "themes/default/clients/forms/edit_submission.tpl",
  "themes/default/clients/forms/index.tpl",
  "themes/default/clients/index.tpl",
  "themes/default/css/",
  "themes/default/css/emails.css",
  "themes/default/css/fields.css",
  "themes/default/css/forms.css",
  "themes/default/css/general.css",
  "themes/default/css/index.html",
  "themes/default/css/menus.css",
  "themes/default/css/navigation.css",
  "themes/default/css/option_lists.css",
  "themes/default/css/public.css",
  "themes/default/css/smoothness/",
  "themes/default/css/smoothness/images/",
  "themes/default/css/smoothness/images/ui-bg_flat_0_000000_40x100.png",
  "themes/default/css/smoothness/images/ui-bg_flat_0_444444_40x100.png",
  "themes/default/css/smoothness/images/ui-bg_flat_0_aaaaaa_40x100.png",
  "themes/default/css/smoothness/images/ui-bg_flat_75_ffffff_40x100.png",
  "themes/default/css/smoothness/images/ui-bg_glass_55_fbf9ee_1x400.png",
  "themes/default/css/smoothness/images/ui-bg_glass_65_ffffff_1x400.png",
  "themes/default/css/smoothness/images/ui-bg_glass_75_dadada_1x400.png",
  "themes/default/css/smoothness/images/ui-bg_glass_75_e6e6e6_1x400.png",
  "themes/default/css/smoothness/images/ui-bg_glass_95_fef1ec_1x400.png",
  "themes/default/css/smoothness/images/ui-bg_highlight-soft_75_cccccc_1x100.png",
  "themes/default/css/smoothness/images/ui-icons_222222_256x240.png",
  "themes/default/css/smoothness/images/ui-icons_2e83ff_256x240.png",
  "themes/default/css/smoothness/images/ui-icons_454545_256x240.png",
  "themes/default/css/smoothness/images/ui-icons_888888_256x240.png",
  "themes/default/css/smoothness/images/ui-icons_cd0a0a_256x240.png",
  "themes/default/css/smoothness/jquery-ui-1.8.6.custom.css",
  "themes/default/css/sortable.css",
  "themes/default/css/styles.css",
  "themes/default/css/submissions.css",
  "themes/default/css/swatch_aquamarine.css",
  "themes/default/css/swatch_blue.css",
  "themes/default/css/swatch_dark_blue.css",
  "themes/default/css/swatch_green.css",
  "themes/default/css/swatch_grey.css",
  "themes/default/css/swatch_light_brown.css",
  "themes/default/css/swatch_orange.css",
  "themes/default/css/swatch_purple.css",
  "themes/default/css/swatch_red.css",
  "themes/default/css/swatch_yellow.css",
  "themes/default/css/tabs.css",
  "themes/default/css/views.css",
  "themes/default/css/widgets.css",
  "themes/default/dhtml_pagination.tpl",
  "themes/default/error.tpl",
  "themes/default/footer.tpl",
  "themes/default/forget_password.tpl",
  "themes/default/header.tpl",
  "themes/default/images/",
  "themes/default/images/account_section_bg_aquamarine.jpg",
  "themes/default/images/account_section_bg_blue.jpg",
  "themes/default/images/account_section_bg_dark_blue.jpg",
  "themes/default/images/account_section_bg_green.jpg",
  "themes/default/images/account_section_bg_grey.jpg",
  "themes/default/images/account_section_bg_light_brown.jpg",
  "themes/default/images/account_section_bg_orange.jpg",
  "themes/default/images/account_section_bg_purple.jpg",
  "themes/default/images/account_section_bg_red.jpg",
  "themes/default/images/account_section_bg_yellow.jpg",
  "themes/default/images/account_section_left_aquamarine.jpg",
  "themes/default/images/account_section_left_blue.jpg",
  "themes/default/images/account_section_left_dark_blue.jpg",
  "themes/default/images/account_section_left_green.jpg",
  "themes/default/images/account_section_left_grey.jpg",
  "themes/default/images/account_section_left_light_brown.jpg",
  "themes/default/images/account_section_left_orange.jpg",
  "themes/default/images/account_section_left_purple.jpg",
  "themes/default/images/account_section_left_red.jpg",
  "themes/default/images/account_section_left_yellow.jpg",
  "themes/default/images/account_section_right_aquamarine.jpg",
  "themes/default/images/account_section_right_blue.jpg",
  "themes/default/images/account_section_right_dark_blue.jpg",
  "themes/default/images/account_section_right_green.jpg",
  "themes/default/images/account_section_right_grey.jpg",
  "themes/default/images/account_section_right_light_brown.jpg",
  "themes/default/images/account_section_right_orange.jpg",
  "themes/default/images/account_section_right_purple.jpg",
  "themes/default/images/account_section_right_red.jpg",
  "themes/default/images/account_section_right_yellow.jpg",
  "themes/default/images/admin_edit.png",
  "themes/default/images/admin_view.png",
  "themes/default/images/ajax_activity.gif",
  "themes/default/images/ajax_activity_grey.gif",
  "themes/default/images/ajax_activity_light_grey.gif",
  "themes/default/images/ajax_activity_yellow.gif",
  "themes/default/images/ajax_no_activity.gif",
  "themes/default/images/ajax_no_activity_grey.gif",
  "themes/default/images/ajax_no_activity_light_grey.gif",
  "themes/default/images/ajax_no_activity_yellow.gif",
  "themes/default/images/columns.png",
  "themes/default/images/delete.png",
  "themes/default/images/delete_bg.jpg",
  "themes/default/images/delete_group.png",
  "themes/default/images/edit.png",
  "themes/default/images/edit_form.png",
  "themes/default/images/edit_small.gif",
  "themes/default/images/favicon.ico",
  "themes/default/images/fields.png",
  "themes/default/images/fields_table_bg.png",
  "themes/default/images/filter.png",
  "themes/default/images/icon_accounts.gif",
  "themes/default/images/icon_forms.gif",
  "themes/default/images/icon_login.gif",
  "themes/default/images/icon_modules.gif",
  "themes/default/images/icon_option_lists.gif",
  "themes/default/images/icon_settings.gif",
  "themes/default/images/icon_themes.gif",
  "themes/default/images/index.php",
  "themes/default/images/left_bg.jpg",
  "themes/default/images/left_nav_bg.jpg",
  "themes/default/images/list_table_heading.jpg",
  "themes/default/images/list_table_heading_over.jpg",
  "themes/default/images/login.png",
  "themes/default/images/logo_aquamarine.jpg",
  "themes/default/images/logo_blue.jpg",
  "themes/default/images/logo_dark_blue.jpg",
  "themes/default/images/logo_green.jpg",
  "themes/default/images/logo_grey.jpg",
  "themes/default/images/logo_light_brown.jpg",
  "themes/default/images/logo_orange.jpg",
  "themes/default/images/logo_purple.jpg",
  "themes/default/images/logo_red.jpg",
  "themes/default/images/logo_yellow.jpg",
  "themes/default/images/main_bg.jpg",
  "themes/default/images/nav_down.jpg",
  "themes/default/images/nav_row_bg.jpg",
  "themes/default/images/page_content_bg.jpg",
  "themes/default/images/placeholders.png",
  "themes/default/images/popup_bg.jpg",
  "themes/default/images/popup_header_bg_green.png",
  "themes/default/images/popup_header_bg_grey.png",
  "themes/default/images/sort.png",
  "themes/default/images/sort_down.gif",
  "themes/default/images/sort_up.gif",
  "themes/default/images/submenu_item.gif",
  "themes/default/images/tab_not_selected_bg.gif",
  "themes/default/images/tabs.png",
  "themes/default/images/top_banner_bg.jpg",
  "themes/default/images/top_row_aquamarine.jpg",
  "themes/default/images/top_row_blue.jpg",
  "themes/default/images/top_row_dark_blue.jpg",
  "themes/default/images/top_row_green.jpg",
  "themes/default/images/top_row_grey.jpg",
  "themes/default/images/top_row_light_brown.jpg",
  "themes/default/images/top_row_orange.jpg",
  "themes/default/images/top_row_purple.jpg",
  "themes/default/images/top_row_red.jpg",
  "themes/default/images/top_row_yellow.jpg",
  "themes/default/images/up.jpg",
  "themes/default/images/up_down.png",
  "themes/default/images/utilities.png",
  "themes/default/images/utilities_small.png",
  "themes/default/images/view.png",
  "themes/default/index.tpl",
  "themes/default/menu.tpl",
  "themes/default/messages.tpl",
  "themes/default/module_menu.tpl",
  "themes/default/modules_footer.tpl",
  "themes/default/modules_header.tpl",
  "themes/default/pagination.tpl",
  "themes/default/scripts/",
  "themes/default/scripts/jquery-ui.js",
  "themes/default/tabset_close.tpl",
  "themes/default/tabset_open.tpl",
  "themes/index.html",
  "upload/",
  "upload/index.html",
  "upload/thumbs/",
  "upload/thumbs/emptyfile.txt"
);