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
        "Field"   => "view_mapping_view_id",
        "Type"    => "mediumint(9)",
        "Null"    => "YES",
        "Key"     => "",
        "Default" => ""
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
		"Field"   => "is_enabled",
		"Type"    => "enum('yes','no')",
		"Null"    => "NO",
		"Key"     => "",
		"Default" => "yes"
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
        "Default" => ""
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
	"LICENSE.txt",
	"admin/account/index.php",
	"admin/clients/add.php",
	"admin/clients/edit.php",
	"admin/clients/index.php",
	"admin/clients/page_forms.php",
	"admin/clients/page_main.php",
	"admin/clients/page_settings.php",
	"admin/forms/add/index.php",
	"admin/forms/add/internal.php",
	"admin/forms/add/step1.php",
	"admin/forms/add/step2.php",
	"admin/forms/add/step3.php",
	"admin/forms/add/step4.php",
	"admin/forms/add/step5.php",
	"admin/forms/add/step6.php",
	"admin/forms/delete_form.php",
	"admin/forms/edit/index.php",
	"admin/forms/edit/page_edit_email.php",
	"admin/forms/edit/page_edit_view.php",
	"admin/forms/edit/page_email_settings.php",
	"admin/forms/edit/page_emails.php",
	"admin/forms/edit/page_fields.php",
	"admin/forms/edit/page_main.php",
	"admin/forms/edit/page_public_form_omit_list.php",
	"admin/forms/edit/page_public_view_omit_list.php",
	"admin/forms/edit/page_views.php",
	"admin/forms/edit_submission.php",
	"admin/forms/index.php",
	"admin/forms/option_lists/edit.php",
	"admin/forms/option_lists/index.php",
	"admin/forms/option_lists/page_form_fields.php",
	"admin/forms/option_lists/page_main.php",
	"admin/forms/submissions.php",
	"admin/index.php",
	"admin/modules/about.php",
	"admin/modules/index.php",
	"admin/settings/index.php",
	"admin/settings/page_accounts.php",
	"admin/settings/page_edit_admin_menu.php",
	"admin/settings/page_edit_client_menu.php",
	"admin/settings/page_files.php",
	"admin/settings/page_main.php",
	"admin/settings/page_menus.php",
	"admin/themes/about.php",
	"admin/themes/index.php",
	"cache/index.html",
	"clients/account/index.php",
	"clients/account/page_main.php",
	"clients/account/page_settings.php",
	"clients/forms/edit_submission.php",
	"clients/forms/index.php",
	"clients/index.php",
	"error.php",
	"forget_password.php",
	"global/code/Accounts.class.php",
	"global/code/Administrator.class.php",
	"global/code/Clients.class.php",
	"global/code/Constants.class.php",
	"global/code/Core.class.php",
	"global/code/CoreFieldTypes.class.php",
	"global/code/Database.class.php",
	"global/code/DatabaseSessions.class.php",
	"global/code/Emails.class.php",
	"global/code/Errors.class.php",
	"global/code/FieldOptions.class.php",
	"global/code/FieldSettings.class.php",
	"global/code/FieldSizes.class.php",
	"global/code/FieldTypes.class.php",
	"global/code/FieldValidation.class.php",
	"global/code/Fields.class.php",
	"global/code/Files.class.php",
	"global/code/Forms.class.php",
	"global/code/General.class.php",
	"global/code/Hooks.class.php",
	"global/code/Installation.class.php",
	"global/code/ListGroups.class.php",
	"global/code/Menus.class.php",
	"global/code/Module.abstract.class.php",
	"global/code/ModuleMenu.class.php",
	"global/code/Modules.class.php",
	"global/code/OmitLists.class.php",
	"global/code/OptionLists.class.php",
	"global/code/Pages.class.php",
	"global/code/SecureSmarty.class.php",
	"global/code/Sessions.class.php",
	"global/code/Settings.class.php",
	"global/code/Submissions.class.php",
	"global/code/Templates.class.php",
	"global/code/Themes.class.php",
	"global/code/Translations.class.php",
	"global/code/Upgrade.class.php",
	"global/code/User.class.php",
	"global/code/ViewColumns.class.php",
	"global/code/ViewFields.class.php",
	"global/code/ViewFilters.class.php",
	"global/code/ViewTabs.class.php",
	"global/code/Views.class.php",
	"global/code/actions.php",
	"global/code/field_types/Checkbox.class.php",
	"global/code/field_types/Code.class.php",
	"global/code/field_types/Date.class.php",
	"global/code/field_types/Dropdown.class.php",
	"global/code/field_types/MultiSelect.class.php",
	"global/code/field_types/Password.class.php",
	"global/code/field_types/Phone.class.php",
	"global/code/field_types/Radio.class.php",
	"global/code/field_types/Textarea.class.php",
	"global/code/field_types/Textbox.class.php",
	"global/code/field_types/Time.class.php",
	"global/code/index.php",
	"global/code/polyfills.php",
	"global/code/validation.php",
	"global/codemirror/AUTHORS",
	"global/codemirror/CHANGELOG.md",
	"global/codemirror/CONTRIBUTING.md",
	"global/codemirror/LICENSE",
	"global/codemirror/README.md",
	"global/codemirror/addon/comment/comment.js",
	"global/codemirror/addon/comment/continuecomment.js",
	"global/codemirror/addon/dialog/dialog.css",
	"global/codemirror/addon/dialog/dialog.js",
	"global/codemirror/addon/display/autorefresh.js",
	"global/codemirror/addon/display/fullscreen.css",
	"global/codemirror/addon/display/fullscreen.js",
	"global/codemirror/addon/display/panel.js",
	"global/codemirror/addon/display/placeholder.js",
	"global/codemirror/addon/display/rulers.js",
	"global/codemirror/addon/edit/closebrackets.js",
	"global/codemirror/addon/edit/closetag.js",
	"global/codemirror/addon/edit/continuelist.js",
	"global/codemirror/addon/edit/matchbrackets.js",
	"global/codemirror/addon/edit/matchtags.js",
	"global/codemirror/addon/edit/trailingspace.js",
	"global/codemirror/addon/fold/brace-fold.js",
	"global/codemirror/addon/fold/comment-fold.js",
	"global/codemirror/addon/fold/foldcode.js",
	"global/codemirror/addon/fold/foldgutter.css",
	"global/codemirror/addon/fold/foldgutter.js",
	"global/codemirror/addon/fold/indent-fold.js",
	"global/codemirror/addon/fold/markdown-fold.js",
	"global/codemirror/addon/fold/xml-fold.js",
	"global/codemirror/addon/hint/anyword-hint.js",
	"global/codemirror/addon/hint/css-hint.js",
	"global/codemirror/addon/hint/html-hint.js",
	"global/codemirror/addon/hint/javascript-hint.js",
	"global/codemirror/addon/hint/show-hint.css",
	"global/codemirror/addon/hint/show-hint.js",
	"global/codemirror/addon/hint/sql-hint.js",
	"global/codemirror/addon/hint/xml-hint.js",
	"global/codemirror/addon/lint/coffeescript-lint.js",
	"global/codemirror/addon/lint/css-lint.js",
	"global/codemirror/addon/lint/html-lint.js",
	"global/codemirror/addon/lint/javascript-lint.js",
	"global/codemirror/addon/lint/json-lint.js",
	"global/codemirror/addon/lint/lint.css",
	"global/codemirror/addon/lint/lint.js",
	"global/codemirror/addon/lint/yaml-lint.js",
	"global/codemirror/addon/merge/merge.css",
	"global/codemirror/addon/merge/merge.js",
	"global/codemirror/addon/mode/loadmode.js",
	"global/codemirror/addon/mode/multiplex.js",
	"global/codemirror/addon/mode/multiplex_test.js",
	"global/codemirror/addon/mode/overlay.js",
	"global/codemirror/addon/mode/simple.js",
	"global/codemirror/addon/runmode/colorize.js",
	"global/codemirror/addon/runmode/runmode-standalone.js",
	"global/codemirror/addon/runmode/runmode.js",
	"global/codemirror/addon/runmode/runmode.node.js",
	"global/codemirror/addon/scroll/annotatescrollbar.js",
	"global/codemirror/addon/scroll/scrollpastend.js",
	"global/codemirror/addon/scroll/simplescrollbars.css",
	"global/codemirror/addon/scroll/simplescrollbars.js",
	"global/codemirror/addon/search/jump-to-line.js",
	"global/codemirror/addon/search/match-highlighter.js",
	"global/codemirror/addon/search/matchesonscrollbar.css",
	"global/codemirror/addon/search/matchesonscrollbar.js",
	"global/codemirror/addon/search/search.js",
	"global/codemirror/addon/search/searchcursor.js",
	"global/codemirror/addon/selection/active-line.js",
	"global/codemirror/addon/selection/mark-selection.js",
	"global/codemirror/addon/selection/selection-pointer.js",
	"global/codemirror/addon/tern/tern.css",
	"global/codemirror/addon/tern/tern.js",
	"global/codemirror/addon/tern/worker.js",
	"global/codemirror/addon/wrap/hardwrap.js",
	"global/codemirror/bin/authors.sh",
	"global/codemirror/bin/compress",
	"global/codemirror/bin/lint",
	"global/codemirror/bin/release",
	"global/codemirror/bin/source-highlight",
	"global/codemirror/bin/upload-release.js",
	"global/codemirror/demo/activeline.html",
	"global/codemirror/demo/anywordhint.html",
	"global/codemirror/demo/bidi.html",
	"global/codemirror/demo/btree.html",
	"global/codemirror/demo/buffers.html",
	"global/codemirror/demo/changemode.html",
	"global/codemirror/demo/closebrackets.html",
	"global/codemirror/demo/closetag.html",
	"global/codemirror/demo/complete.html",
	"global/codemirror/demo/emacs.html",
	"global/codemirror/demo/folding.html",
	"global/codemirror/demo/fullscreen.html",
	"global/codemirror/demo/hardwrap.html",
	"global/codemirror/demo/html5complete.html",
	"global/codemirror/demo/indentwrap.html",
	"global/codemirror/demo/lint.html",
	"global/codemirror/demo/loadmode.html",
	"global/codemirror/demo/marker.html",
	"global/codemirror/demo/markselection.html",
	"global/codemirror/demo/matchhighlighter.html",
	"global/codemirror/demo/matchtags.html",
	"global/codemirror/demo/merge.html",
	"global/codemirror/demo/multiplex.html",
	"global/codemirror/demo/mustache.html",
	"global/codemirror/demo/panel.html",
	"global/codemirror/demo/placeholder.html",
	"global/codemirror/demo/preview.html",
	"global/codemirror/demo/requirejs.html",
	"global/codemirror/demo/resize.html",
	"global/codemirror/demo/rulers.html",
	"global/codemirror/demo/runmode.html",
	"global/codemirror/demo/search.html",
	"global/codemirror/demo/simplemode.html",
	"global/codemirror/demo/simplescrollbars.html",
	"global/codemirror/demo/spanaffectswrapping_shim.html",
	"global/codemirror/demo/sublime.html",
	"global/codemirror/demo/tern.html",
	"global/codemirror/demo/theme.html",
	"global/codemirror/demo/trailingspace.html",
	"global/codemirror/demo/variableheight.html",
	"global/codemirror/demo/vim.html",
	"global/codemirror/demo/visibletabs.html",
	"global/codemirror/demo/widget.html",
	"global/codemirror/demo/xmlcomplete.html",
	"global/codemirror/doc/activebookmark.js",
	"global/codemirror/doc/docs.css",
	"global/codemirror/doc/internals.html",
	"global/codemirror/doc/logo.png",
	"global/codemirror/doc/logo.svg",
	"global/codemirror/doc/manual.html",
	"global/codemirror/doc/realworld.html",
	"global/codemirror/doc/releases.html",
	"global/codemirror/doc/reporting.html",
	"global/codemirror/doc/upgrade_v2.2.html",
	"global/codemirror/doc/upgrade_v3.html",
	"global/codemirror/doc/upgrade_v4.html",
	"global/codemirror/doc/yinyang.png",
	"global/codemirror/index.html",
	"global/codemirror/keymap/emacs.js",
	"global/codemirror/keymap/sublime.js",
	"global/codemirror/keymap/vim.js",
	"global/codemirror/lib/codemirror.css",
	"global/codemirror/lib/codemirror.js",
	"global/codemirror/mode/apl/apl.js",
	"global/codemirror/mode/apl/index.html",
	"global/codemirror/mode/asciiarmor/asciiarmor.js",
	"global/codemirror/mode/asciiarmor/index.html",
	"global/codemirror/mode/asn.1/asn.1.js",
	"global/codemirror/mode/asn.1/index.html",
	"global/codemirror/mode/asterisk/asterisk.js",
	"global/codemirror/mode/asterisk/index.html",
	"global/codemirror/mode/brainfuck/brainfuck.js",
	"global/codemirror/mode/brainfuck/index.html",
	"global/codemirror/mode/clike/clike.js",
	"global/codemirror/mode/clike/index.html",
	"global/codemirror/mode/clike/scala.html",
	"global/codemirror/mode/clike/test.js",
	"global/codemirror/mode/clojure/clojure.js",
	"global/codemirror/mode/clojure/index.html",
	"global/codemirror/mode/cmake/cmake.js",
	"global/codemirror/mode/cmake/index.html",
	"global/codemirror/mode/cobol/cobol.js",
	"global/codemirror/mode/cobol/index.html",
	"global/codemirror/mode/coffeescript/coffeescript.js",
	"global/codemirror/mode/coffeescript/index.html",
	"global/codemirror/mode/commonlisp/commonlisp.js",
	"global/codemirror/mode/commonlisp/index.html",
	"global/codemirror/mode/crystal/crystal.js",
	"global/codemirror/mode/crystal/index.html",
	"global/codemirror/mode/css/css.js",
	"global/codemirror/mode/css/gss.html",
	"global/codemirror/mode/css/gss_test.js",
	"global/codemirror/mode/css/index.html",
	"global/codemirror/mode/css/less.html",
	"global/codemirror/mode/css/less_test.js",
	"global/codemirror/mode/css/scss.html",
	"global/codemirror/mode/css/scss_test.js",
	"global/codemirror/mode/css/test.js",
	"global/codemirror/mode/cypher/cypher.js",
	"global/codemirror/mode/cypher/index.html",
	"global/codemirror/mode/cypher/test.js",
	"global/codemirror/mode/d/d.js",
	"global/codemirror/mode/d/index.html",
	"global/codemirror/mode/d/test.js",
	"global/codemirror/mode/dart/dart.js",
	"global/codemirror/mode/dart/index.html",
	"global/codemirror/mode/diff/diff.js",
	"global/codemirror/mode/diff/index.html",
	"global/codemirror/mode/django/django.js",
	"global/codemirror/mode/django/index.html",
	"global/codemirror/mode/dockerfile/dockerfile.js",
	"global/codemirror/mode/dockerfile/index.html",
	"global/codemirror/mode/dtd/dtd.js",
	"global/codemirror/mode/dtd/index.html",
	"global/codemirror/mode/dylan/dylan.js",
	"global/codemirror/mode/dylan/index.html",
	"global/codemirror/mode/dylan/test.js",
	"global/codemirror/mode/ebnf/ebnf.js",
	"global/codemirror/mode/ebnf/index.html",
	"global/codemirror/mode/ecl/ecl.js",
	"global/codemirror/mode/ecl/index.html",
	"global/codemirror/mode/eiffel/eiffel.js",
	"global/codemirror/mode/eiffel/index.html",
	"global/codemirror/mode/elm/elm.js",
	"global/codemirror/mode/elm/index.html",
	"global/codemirror/mode/erlang/erlang.js",
	"global/codemirror/mode/erlang/index.html",
	"global/codemirror/mode/factor/factor.js",
	"global/codemirror/mode/factor/index.html",
	"global/codemirror/mode/fcl/fcl.js",
	"global/codemirror/mode/fcl/index.html",
	"global/codemirror/mode/forth/forth.js",
	"global/codemirror/mode/forth/index.html",
	"global/codemirror/mode/fortran/fortran.js",
	"global/codemirror/mode/fortran/index.html",
	"global/codemirror/mode/gas/gas.js",
	"global/codemirror/mode/gas/index.html",
	"global/codemirror/mode/gfm/gfm.js",
	"global/codemirror/mode/gfm/index.html",
	"global/codemirror/mode/gfm/test.js",
	"global/codemirror/mode/gherkin/gherkin.js",
	"global/codemirror/mode/gherkin/index.html",
	"global/codemirror/mode/go/go.js",
	"global/codemirror/mode/go/index.html",
	"global/codemirror/mode/groovy/groovy.js",
	"global/codemirror/mode/groovy/index.html",
	"global/codemirror/mode/haml/haml.js",
	"global/codemirror/mode/haml/index.html",
	"global/codemirror/mode/haml/test.js",
	"global/codemirror/mode/handlebars/handlebars.js",
	"global/codemirror/mode/handlebars/index.html",
	"global/codemirror/mode/haskell-literate/haskell-literate.js",
	"global/codemirror/mode/haskell-literate/index.html",
	"global/codemirror/mode/haskell/haskell.js",
	"global/codemirror/mode/haskell/index.html",
	"global/codemirror/mode/haxe/haxe.js",
	"global/codemirror/mode/haxe/index.html",
	"global/codemirror/mode/htmlembedded/htmlembedded.js",
	"global/codemirror/mode/htmlembedded/index.html",
	"global/codemirror/mode/htmlmixed/htmlmixed.js",
	"global/codemirror/mode/htmlmixed/index.html",
	"global/codemirror/mode/http/http.js",
	"global/codemirror/mode/http/index.html",
	"global/codemirror/mode/idl/idl.js",
	"global/codemirror/mode/idl/index.html",
	"global/codemirror/mode/index.html",
	"global/codemirror/mode/javascript/index.html",
	"global/codemirror/mode/javascript/javascript.js",
	"global/codemirror/mode/javascript/json-ld.html",
	"global/codemirror/mode/javascript/test.js",
	"global/codemirror/mode/javascript/typescript.html",
	"global/codemirror/mode/jinja2/index.html",
	"global/codemirror/mode/jinja2/jinja2.js",
	"global/codemirror/mode/jsx/index.html",
	"global/codemirror/mode/jsx/jsx.js",
	"global/codemirror/mode/jsx/test.js",
	"global/codemirror/mode/julia/index.html",
	"global/codemirror/mode/julia/julia.js",
	"global/codemirror/mode/livescript/index.html",
	"global/codemirror/mode/livescript/livescript.js",
	"global/codemirror/mode/lua/index.html",
	"global/codemirror/mode/lua/lua.js",
	"global/codemirror/mode/markdown/index.html",
	"global/codemirror/mode/markdown/markdown.js",
	"global/codemirror/mode/markdown/test.js",
	"global/codemirror/mode/mathematica/index.html",
	"global/codemirror/mode/mathematica/mathematica.js",
	"global/codemirror/mode/mbox/index.html",
	"global/codemirror/mode/mbox/mbox.js",
	"global/codemirror/mode/meta.js",
	"global/codemirror/mode/mirc/index.html",
	"global/codemirror/mode/mirc/mirc.js",
	"global/codemirror/mode/mllike/index.html",
	"global/codemirror/mode/mllike/mllike.js",
	"global/codemirror/mode/modelica/index.html",
	"global/codemirror/mode/modelica/modelica.js",
	"global/codemirror/mode/mscgen/index.html",
	"global/codemirror/mode/mscgen/mscgen.js",
	"global/codemirror/mode/mscgen/mscgen_test.js",
	"global/codemirror/mode/mscgen/msgenny_test.js",
	"global/codemirror/mode/mscgen/xu_test.js",
	"global/codemirror/mode/mumps/index.html",
	"global/codemirror/mode/mumps/mumps.js",
	"global/codemirror/mode/nginx/index.html",
	"global/codemirror/mode/nginx/nginx.js",
	"global/codemirror/mode/nsis/index.html",
	"global/codemirror/mode/nsis/nsis.js",
	"global/codemirror/mode/ntriples/index.html",
	"global/codemirror/mode/ntriples/ntriples.js",
	"global/codemirror/mode/octave/index.html",
	"global/codemirror/mode/octave/octave.js",
	"global/codemirror/mode/oz/index.html",
	"global/codemirror/mode/oz/oz.js",
	"global/codemirror/mode/pascal/index.html",
	"global/codemirror/mode/pascal/pascal.js",
	"global/codemirror/mode/pegjs/index.html",
	"global/codemirror/mode/pegjs/pegjs.js",
	"global/codemirror/mode/perl/index.html",
	"global/codemirror/mode/perl/perl.js",
	"global/codemirror/mode/php/index.html",
	"global/codemirror/mode/php/php.js",
	"global/codemirror/mode/php/test.js",
	"global/codemirror/mode/pig/index.html",
	"global/codemirror/mode/pig/pig.js",
	"global/codemirror/mode/powershell/index.html",
	"global/codemirror/mode/powershell/powershell.js",
	"global/codemirror/mode/powershell/test.js",
	"global/codemirror/mode/properties/index.html",
	"global/codemirror/mode/properties/properties.js",
	"global/codemirror/mode/protobuf/index.html",
	"global/codemirror/mode/protobuf/protobuf.js",
	"global/codemirror/mode/pug/index.html",
	"global/codemirror/mode/pug/pug.js",
	"global/codemirror/mode/puppet/index.html",
	"global/codemirror/mode/puppet/puppet.js",
	"global/codemirror/mode/python/index.html",
	"global/codemirror/mode/python/python.js",
	"global/codemirror/mode/python/test.js",
	"global/codemirror/mode/q/index.html",
	"global/codemirror/mode/q/q.js",
	"global/codemirror/mode/r/index.html",
	"global/codemirror/mode/r/r.js",
	"global/codemirror/mode/rpm/changes/index.html",
	"global/codemirror/mode/rpm/index.html",
	"global/codemirror/mode/rpm/rpm.js",
	"global/codemirror/mode/rst/index.html",
	"global/codemirror/mode/rst/rst.js",
	"global/codemirror/mode/ruby/index.html",
	"global/codemirror/mode/ruby/ruby.js",
	"global/codemirror/mode/ruby/test.js",
	"global/codemirror/mode/rust/index.html",
	"global/codemirror/mode/rust/rust.js",
	"global/codemirror/mode/rust/test.js",
	"global/codemirror/mode/sas/index.html",
	"global/codemirror/mode/sas/sas.js",
	"global/codemirror/mode/sass/index.html",
	"global/codemirror/mode/sass/sass.js",
	"global/codemirror/mode/sass/test.js",
	"global/codemirror/mode/scheme/index.html",
	"global/codemirror/mode/scheme/scheme.js",
	"global/codemirror/mode/shell/index.html",
	"global/codemirror/mode/shell/shell.js",
	"global/codemirror/mode/shell/test.js",
	"global/codemirror/mode/sieve/index.html",
	"global/codemirror/mode/sieve/sieve.js",
	"global/codemirror/mode/slim/index.html",
	"global/codemirror/mode/slim/slim.js",
	"global/codemirror/mode/slim/test.js",
	"global/codemirror/mode/smalltalk/index.html",
	"global/codemirror/mode/smalltalk/smalltalk.js",
	"global/codemirror/mode/smarty/index.html",
	"global/codemirror/mode/smarty/smarty.js",
	"global/codemirror/mode/solr/index.html",
	"global/codemirror/mode/solr/solr.js",
	"global/codemirror/mode/soy/index.html",
	"global/codemirror/mode/soy/soy.js",
	"global/codemirror/mode/soy/test.js",
	"global/codemirror/mode/sparql/index.html",
	"global/codemirror/mode/sparql/sparql.js",
	"global/codemirror/mode/spreadsheet/index.html",
	"global/codemirror/mode/spreadsheet/spreadsheet.js",
	"global/codemirror/mode/sql/index.html",
	"global/codemirror/mode/sql/sql.js",
	"global/codemirror/mode/stex/index.html",
	"global/codemirror/mode/stex/stex.js",
	"global/codemirror/mode/stex/test.js",
	"global/codemirror/mode/stylus/index.html",
	"global/codemirror/mode/stylus/stylus.js",
	"global/codemirror/mode/swift/index.html",
	"global/codemirror/mode/swift/swift.js",
	"global/codemirror/mode/swift/test.js",
	"global/codemirror/mode/tcl/index.html",
	"global/codemirror/mode/tcl/tcl.js",
	"global/codemirror/mode/textile/index.html",
	"global/codemirror/mode/textile/test.js",
	"global/codemirror/mode/textile/textile.js",
	"global/codemirror/mode/tiddlywiki/index.html",
	"global/codemirror/mode/tiddlywiki/tiddlywiki.css",
	"global/codemirror/mode/tiddlywiki/tiddlywiki.js",
	"global/codemirror/mode/tiki/index.html",
	"global/codemirror/mode/tiki/tiki.css",
	"global/codemirror/mode/tiki/tiki.js",
	"global/codemirror/mode/toml/index.html",
	"global/codemirror/mode/toml/toml.js",
	"global/codemirror/mode/tornado/index.html",
	"global/codemirror/mode/tornado/tornado.js",
	"global/codemirror/mode/troff/index.html",
	"global/codemirror/mode/troff/troff.js",
	"global/codemirror/mode/ttcn-cfg/index.html",
	"global/codemirror/mode/ttcn-cfg/ttcn-cfg.js",
	"global/codemirror/mode/ttcn/index.html",
	"global/codemirror/mode/ttcn/ttcn.js",
	"global/codemirror/mode/turtle/index.html",
	"global/codemirror/mode/turtle/turtle.js",
	"global/codemirror/mode/twig/index.html",
	"global/codemirror/mode/twig/twig.js",
	"global/codemirror/mode/vb/index.html",
	"global/codemirror/mode/vb/vb.js",
	"global/codemirror/mode/vbscript/index.html",
	"global/codemirror/mode/vbscript/vbscript.js",
	"global/codemirror/mode/velocity/index.html",
	"global/codemirror/mode/velocity/velocity.js",
	"global/codemirror/mode/verilog/index.html",
	"global/codemirror/mode/verilog/test.js",
	"global/codemirror/mode/verilog/verilog.js",
	"global/codemirror/mode/vhdl/index.html",
	"global/codemirror/mode/vhdl/vhdl.js",
	"global/codemirror/mode/vue/index.html",
	"global/codemirror/mode/vue/vue.js",
	"global/codemirror/mode/webidl/index.html",
	"global/codemirror/mode/webidl/webidl.js",
	"global/codemirror/mode/xml/index.html",
	"global/codemirror/mode/xml/test.js",
	"global/codemirror/mode/xml/xml.js",
	"global/codemirror/mode/xquery/index.html",
	"global/codemirror/mode/xquery/test.js",
	"global/codemirror/mode/xquery/xquery.js",
	"global/codemirror/mode/yacas/index.html",
	"global/codemirror/mode/yacas/yacas.js",
	"global/codemirror/mode/yaml-frontmatter/index.html",
	"global/codemirror/mode/yaml-frontmatter/yaml-frontmatter.js",
	"global/codemirror/mode/yaml/index.html",
	"global/codemirror/mode/yaml/yaml.js",
	"global/codemirror/mode/z80/index.html",
	"global/codemirror/mode/z80/z80.js",
	"global/codemirror/package.json",
	"global/codemirror/rollup.config.js",
	"global/codemirror/src/codemirror.js",
	"global/codemirror/src/display/Display.js",
	"global/codemirror/src/display/focus.js",
	"global/codemirror/src/display/gutters.js",
	"global/codemirror/src/display/highlight_worker.js",
	"global/codemirror/src/display/line_numbers.js",
	"global/codemirror/src/display/mode_state.js",
	"global/codemirror/src/display/operations.js",
	"global/codemirror/src/display/scroll_events.js",
	"global/codemirror/src/display/scrollbars.js",
	"global/codemirror/src/display/scrolling.js",
	"global/codemirror/src/display/selection.js",
	"global/codemirror/src/display/update_display.js",
	"global/codemirror/src/display/update_line.js",
	"global/codemirror/src/display/update_lines.js",
	"global/codemirror/src/display/view_tracking.js",
	"global/codemirror/src/edit/CodeMirror.js",
	"global/codemirror/src/edit/commands.js",
	"global/codemirror/src/edit/deleteNearSelection.js",
	"global/codemirror/src/edit/drop_events.js",
	"global/codemirror/src/edit/fromTextArea.js",
	"global/codemirror/src/edit/global_events.js",
	"global/codemirror/src/edit/key_events.js",
	"global/codemirror/src/edit/legacy.js",
	"global/codemirror/src/edit/main.js",
	"global/codemirror/src/edit/methods.js",
	"global/codemirror/src/edit/mouse_events.js",
	"global/codemirror/src/edit/options.js",
	"global/codemirror/src/edit/utils.js",
	"global/codemirror/src/input/ContentEditableInput.js",
	"global/codemirror/src/input/TextareaInput.js",
	"global/codemirror/src/input/indent.js",
	"global/codemirror/src/input/input.js",
	"global/codemirror/src/input/keymap.js",
	"global/codemirror/src/input/keynames.js",
	"global/codemirror/src/input/movement.js",
	"global/codemirror/src/line/highlight.js",
	"global/codemirror/src/line/line_data.js",
	"global/codemirror/src/line/pos.js",
	"global/codemirror/src/line/saw_special_spans.js",
	"global/codemirror/src/line/spans.js",
	"global/codemirror/src/line/utils_line.js",
	"global/codemirror/src/measurement/position_measurement.js",
	"global/codemirror/src/measurement/widgets.js",
	"global/codemirror/src/model/Doc.js",
	"global/codemirror/src/model/change_measurement.js",
	"global/codemirror/src/model/changes.js",
	"global/codemirror/src/model/chunk.js",
	"global/codemirror/src/model/document_data.js",
	"global/codemirror/src/model/history.js",
	"global/codemirror/src/model/line_widget.js",
	"global/codemirror/src/model/mark_text.js",
	"global/codemirror/src/model/selection.js",
	"global/codemirror/src/model/selection_updates.js",
	"global/codemirror/src/modes.js",
	"global/codemirror/src/util/StringStream.js",
	"global/codemirror/src/util/bidi.js",
	"global/codemirror/src/util/browser.js",
	"global/codemirror/src/util/dom.js",
	"global/codemirror/src/util/event.js",
	"global/codemirror/src/util/feature_detection.js",
	"global/codemirror/src/util/misc.js",
	"global/codemirror/src/util/operation_group.js",
	"global/codemirror/test/comment_test.js",
	"global/codemirror/test/contenteditable_test.js",
	"global/codemirror/test/doc_test.js",
	"global/codemirror/test/driver.js",
	"global/codemirror/test/emacs_test.js",
	"global/codemirror/test/index.html",
	"global/codemirror/test/lint.js",
	"global/codemirror/test/mode_test.css",
	"global/codemirror/test/mode_test.js",
	"global/codemirror/test/multi_test.js",
	"global/codemirror/test/phantom_driver.js",
	"global/codemirror/test/run.js",
	"global/codemirror/test/scroll_test.js",
	"global/codemirror/test/search_test.js",
	"global/codemirror/test/sql-hint-test.js",
	"global/codemirror/test/sublime_test.js",
	"global/codemirror/test/test.js",
	"global/codemirror/test/vim_test.js",
	"global/codemirror/theme/3024-day.css",
	"global/codemirror/theme/3024-night.css",
	"global/codemirror/theme/abcdef.css",
	"global/codemirror/theme/ambiance-mobile.css",
	"global/codemirror/theme/ambiance.css",
	"global/codemirror/theme/base16-dark.css",
	"global/codemirror/theme/base16-light.css",
	"global/codemirror/theme/bespin.css",
	"global/codemirror/theme/blackboard.css",
	"global/codemirror/theme/cobalt.css",
	"global/codemirror/theme/colorforth.css",
	"global/codemirror/theme/dracula.css",
	"global/codemirror/theme/duotone-dark.css",
	"global/codemirror/theme/duotone-light.css",
	"global/codemirror/theme/eclipse.css",
	"global/codemirror/theme/elegant.css",
	"global/codemirror/theme/erlang-dark.css",
	"global/codemirror/theme/hopscotch.css",
	"global/codemirror/theme/icecoder.css",
	"global/codemirror/theme/isotope.css",
	"global/codemirror/theme/lesser-dark.css",
	"global/codemirror/theme/liquibyte.css",
	"global/codemirror/theme/material.css",
	"global/codemirror/theme/mbo.css",
	"global/codemirror/theme/mdn-like.css",
	"global/codemirror/theme/midnight.css",
	"global/codemirror/theme/monokai.css",
	"global/codemirror/theme/neat.css",
	"global/codemirror/theme/neo.css",
	"global/codemirror/theme/night.css",
	"global/codemirror/theme/panda-syntax.css",
	"global/codemirror/theme/paraiso-dark.css",
	"global/codemirror/theme/paraiso-light.css",
	"global/codemirror/theme/pastel-on-dark.css",
	"global/codemirror/theme/railscasts.css",
	"global/codemirror/theme/rubyblue.css",
	"global/codemirror/theme/seti.css",
	"global/codemirror/theme/solarized.css",
	"global/codemirror/theme/the-matrix.css",
	"global/codemirror/theme/tomorrow-night-bright.css",
	"global/codemirror/theme/tomorrow-night-eighties.css",
	"global/codemirror/theme/ttcn.css",
	"global/codemirror/theme/twilight.css",
	"global/codemirror/theme/vibrant-ink.css",
	"global/codemirror/theme/xq-dark.css",
	"global/codemirror/theme/xq-light.css",
	"global/codemirror/theme/yeti.css",
	"global/codemirror/theme/zenburn.css",
	"global/css/field_types.php",
	"global/css/main.css",
	"global/css/ui.daterangepicker.css",
	"global/emails/forget_password.tpl",
	"global/emails/forget_password_subject.tpl",
	"global/emails/installed.tpl",
	"global/emails/installed_subject.tpl",
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
	"global/lang/README.md",
	"global/lang/af.php",
	"global/lang/ar.php",
	"global/lang/az.php",
	"global/lang/be.php",
	"global/lang/bg.php",
	"global/lang/ca.php",
	"global/lang/cs.php",
	"global/lang/cy.php",
	"global/lang/da.php",
	"global/lang/de.php",
	"global/lang/el.php",
	"global/lang/en_us.php",
	"global/lang/es.php",
	"global/lang/et.php",
	"global/lang/fa.php",
	"global/lang/fi.php",
	"global/lang/fr.php",
	"global/lang/ga.php",
	"global/lang/gl.php",
	"global/lang/hi.php",
	"global/lang/hr.php",
	"global/lang/hu.php",
	"global/lang/id.php",
	"global/lang/index.html",
	"global/lang/is.php",
	"global/lang/it.php",
	"global/lang/ja.php",
	"global/lang/ko.php",
	"global/lang/la.php",
	"global/lang/lt.php",
	"global/lang/manifest.json",
	"global/lang/mk.php",
	"global/lang/ms.php",
	"global/lang/mt.php",
	"global/lang/nl.php",
	"global/lang/no.php",
	"global/lang/pl.php",
	"global/lang/pt.php",
	"global/lang/pt_br.php",
	"global/lang/pt_eu.php",
	"global/lang/ro.php",
	"global/lang/ru.php",
	"global/lang/sk.php",
	"global/lang/sl.php",
	"global/lang/sr.php",
	"global/lang/sv.php",
	"global/lang/sw.php",
	"global/lang/th.php",
	"global/lang/tl.php",
	"global/lang/tr.php",
	"global/lang/uk.php",
	"global/lang/vi.php",
	"global/lang/yi.php",
	"global/lang/zh_cn.php",
	"global/lang/zh_tw.php",
	"global/library.php",
	"global/misc/config_core.php",
	"global/misc/index.php",
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
	"global/smarty_plugins/eval.tpl",
	"global/smarty_plugins/function.clients_dropdown.php",
	"global/smarty_plugins/function.css_files.php",
	"global/smarty_plugins/function.display_account_name.php",
	"global/smarty_plugins/function.display_custom_field.php",
	"global/smarty_plugins/function.display_edit_submission_view_dropdown.php",
	"global/smarty_plugins/function.display_email_template_dropdown.php",
	"global/smarty_plugins/function.display_field_type_name.php",
	"global/smarty_plugins/function.display_field_type_settings_dropdown.php",
	"global/smarty_plugins/function.display_field_types_dropdown.php",
	"global/smarty_plugins/function.display_file_field.php",
	"global/smarty_plugins/function.display_form_name.php",
	"global/smarty_plugins/function.display_multi_select_field_values.php",
	"global/smarty_plugins/function.display_num_form_submissions.php",
	"global/smarty_plugins/function.display_option_list.php",
	"global/smarty_plugins/function.display_view_name.php",
	"global/smarty_plugins/function.dropdown.php",
	"global/smarty_plugins/function.edit_custom_field.php",
	"global/smarty_plugins/function.edit_phone_field.php",
	"global/smarty_plugins/function.email_patterns_dropdown.php",
	"global/smarty_plugins/function.eval_smarty_string.php",
	"global/smarty_plugins/function.field_sizes_dropdown.php",
	"global/smarty_plugins/function.form_fields_dropdown.php",
	"global/smarty_plugins/function.form_view_fields_dropdown.php",
	"global/smarty_plugins/function.forms_dropdown.php",
	"global/smarty_plugins/function.ft_include.php",
	"global/smarty_plugins/function.js_files.php",
	"global/smarty_plugins/function.languages_dropdown.php",
	"global/smarty_plugins/function.menus_dropdown.php",
	"global/smarty_plugins/function.module_function.php",
	"global/smarty_plugins/function.option_list_dropdown.php",
	"global/smarty_plugins/function.pages_dropdown.php",
	"global/smarty_plugins/function.show_page_load_time.php",
	"global/smarty_plugins/function.submission_dropdown.php",
	"global/smarty_plugins/function.submission_dropdown_multiple.php",
	"global/smarty_plugins/function.submission_listing_quicklinks.php",
	"global/smarty_plugins/function.template_hook.php",
	"global/smarty_plugins/function.themes_dropdown.php",
	"global/smarty_plugins/function.timezone_offset_dropdown.php",
	"global/smarty_plugins/function.view_fields.php",
	"global/smarty_plugins/function.view_phone_field.php",
	"global/smarty_plugins/function.views_dropdown.php",
	"global/smarty_plugins/modifier.custom_format_date.php",
	"global/smarty_plugins/modifier.hook_call_defined.php",
	"index.php",
	"install/files/main.css",
	"install/index.php",
	"install/step2.php",
	"install/step3.php",
	"install/step4.php",
	"install/step5.php",
	"install/step6.php",
	"install/templates/index.tpl",
	"install/templates/install_footer.tpl",
	"install/templates/install_header.tpl",
	"install/templates/step2.tpl",
	"install/templates/step3.tpl",
	"install/templates/step4.tpl",
	"install/templates/step5.tpl",
	"install/templates/step6.tpl",
	"modules/index.html",
	"process.php",
	"themes/default/about/screenshot.gif",
	"themes/default/about/theme.php",
	"themes/default/about/thumbnail.gif",
	"themes/default/admin/account/index.tpl",
	"themes/default/admin/clients/add.tpl",
	"themes/default/admin/clients/edit.tpl",
	"themes/default/admin/clients/index.tpl",
	"themes/default/admin/clients/tab_forms.tpl",
	"themes/default/admin/clients/tab_main.tpl",
	"themes/default/admin/clients/tab_settings.tpl",
	"themes/default/admin/forms/add/index.tpl",
	"themes/default/admin/forms/add/internal.tpl",
	"themes/default/admin/forms/add/step1.tpl",
	"themes/default/admin/forms/add/step2.tpl",
	"themes/default/admin/forms/add/step3.tpl",
	"themes/default/admin/forms/add/step4.tpl",
	"themes/default/admin/forms/add/step5.tpl",
	"themes/default/admin/forms/add/step6.tpl",
	"themes/default/admin/forms/delete_form.tpl",
	"themes/default/admin/forms/edit/index.tpl",
	"themes/default/admin/forms/edit/tab_edit_email.tpl",
	"themes/default/admin/forms/edit/tab_edit_email_tab1.tpl",
	"themes/default/admin/forms/edit/tab_edit_email_tab2.tpl",
	"themes/default/admin/forms/edit/tab_edit_email_tab3.tpl",
	"themes/default/admin/forms/edit/tab_edit_email_tab4.tpl",
	"themes/default/admin/forms/edit/tab_edit_view.tpl",
	"themes/default/admin/forms/edit/tab_edit_view__fields.tpl",
	"themes/default/admin/forms/edit/tab_edit_view__filters.tpl",
	"themes/default/admin/forms/edit/tab_edit_view__list_page.tpl",
	"themes/default/admin/forms/edit/tab_edit_view__main.tpl",
	"themes/default/admin/forms/edit/tab_edit_view__tabs.tpl",
	"themes/default/admin/forms/edit/tab_email_settings.tpl",
	"themes/default/admin/forms/edit/tab_emails.tpl",
	"themes/default/admin/forms/edit/tab_fields.tpl",
	"themes/default/admin/forms/edit/tab_main.tpl",
	"themes/default/admin/forms/edit/tab_public_form_omit_list.tpl",
	"themes/default/admin/forms/edit/tab_public_view_omit_list.tpl",
	"themes/default/admin/forms/edit/tab_views.tpl",
	"themes/default/admin/forms/edit_submission.tpl",
	"themes/default/admin/forms/form_placeholders.tpl",
	"themes/default/admin/forms/index.tpl",
	"themes/default/admin/forms/option_lists/edit.tpl",
	"themes/default/admin/forms/option_lists/index.tpl",
	"themes/default/admin/forms/option_lists/tab_form_fields.tpl",
	"themes/default/admin/forms/option_lists/tab_main.tpl",
	"themes/default/admin/forms/submissions.tpl",
	"themes/default/admin/index.html",
	"themes/default/admin/modules/about.tpl",
	"themes/default/admin/modules/index.tpl",
	"themes/default/admin/settings/index.tpl",
	"themes/default/admin/settings/tab_accounts.tpl",
	"themes/default/admin/settings/tab_edit_admin_menu.tpl",
	"themes/default/admin/settings/tab_edit_client_menu.tpl",
	"themes/default/admin/settings/tab_files.tpl",
	"themes/default/admin/settings/tab_main.tpl",
	"themes/default/admin/settings/tab_menus.tpl",
	"themes/default/admin/themes/about.tpl",
	"themes/default/admin/themes/index.tpl",
	"themes/default/clients/account/index.tpl",
	"themes/default/clients/account/tab_main.tpl",
	"themes/default/clients/account/tab_settings.tpl",
	"themes/default/clients/forms/edit_submission.tpl",
	"themes/default/clients/forms/index.tpl",
	"themes/default/clients/index.tpl",
	"themes/default/css/index.html",
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
	"themes/default/css/smoothness/jquery-ui.css",
	"themes/default/css/styles.css",
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
	"themes/default/js_pagination.tpl",
	"themes/default/error.tpl",
	"themes/default/footer.tpl",
	"themes/default/forget_password.tpl",
	"themes/default/header.tpl",
	"themes/default/images/account_section_left_aquamarine2x.png",
	"themes/default/images/account_section_left_blue2x.png",
	"themes/default/images/account_section_left_dark_blue2x.png",
	"themes/default/images/account_section_left_green2x.png",
	"themes/default/images/account_section_left_grey2x.png",
	"themes/default/images/account_section_left_light_brown2x.png",
	"themes/default/images/account_section_left_orange2x.png",
	"themes/default/images/account_section_left_purple2x.png",
	"themes/default/images/account_section_left_red2x.png",
	"themes/default/images/account_section_left_yellow2x.png",
	"themes/default/images/account_section_right_aquamarine2x.png",
	"themes/default/images/account_section_right_blue2x.png",
	"themes/default/images/account_section_right_dark_blue2x.png",
	"themes/default/images/account_section_right_green2x.png",
	"themes/default/images/account_section_right_grey2x.png",
	"themes/default/images/account_section_right_light_brown2x.png",
	"themes/default/images/account_section_right_orange2x.png",
	"themes/default/images/account_section_right_purple2x.png",
	"themes/default/images/account_section_right_red2x.png",
	"themes/default/images/account_section_right_yellow2x.png",
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
	"themes/default/images/logo_aquamarine2x.png",
	"themes/default/images/logo_blue2x.png",
	"themes/default/images/logo_dark_blue2x.png",
	"themes/default/images/logo_green2x.png",
	"themes/default/images/logo_grey2x.png",
	"themes/default/images/logo_light_brown2x.png",
	"themes/default/images/logo_orange2x.png",
	"themes/default/images/logo_purple2x.png",
	"themes/default/images/logo_red2x.png",
	"themes/default/images/logo_yellow2x.png",
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
	"themes/default/images/top_row_aquamarine2x.png",
	"themes/default/images/top_row_blue2x.png",
	"themes/default/images/top_row_dark_blue2x.png",
	"themes/default/images/top_row_green2x.png",
	"themes/default/images/top_row_grey2x.png",
	"themes/default/images/top_row_light_brown2x.png",
	"themes/default/images/top_row_orange2x.png",
	"themes/default/images/top_row_purple2x.png",
	"themes/default/images/top_row_red2x.png",
	"themes/default/images/top_row_yellow2x.png",
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
	"themes/default/scripts/jquery-ui-1.8.6.custom.min.js",
	"themes/default/scripts/jquery-ui.js",
	"themes/default/tabset_close.tpl",
	"themes/default/tabset_open.tpl",
	"themes/index.html",
	"upload/index.html",
	"vendor/autoload.php",
	"vendor/composer/ClassLoader.php",
	"vendor/composer/LICENSE",
	"vendor/composer/autoload_classmap.php",
	"vendor/composer/autoload_files.php",
	"vendor/composer/autoload_namespaces.php",
	"vendor/composer/autoload_psr4.php",
	"vendor/composer/autoload_real.php",
	"vendor/composer/autoload_static.php",
	"vendor/composer/installed.json",
	"vendor/smarty/smarty/COMPOSER_RELEASE_NOTES.txt",
	"vendor/smarty/smarty/INHERITANCE_RELEASE_NOTES.txt",
	"vendor/smarty/smarty/LICENSE",
	"vendor/smarty/smarty/NEW_FEATURES.txt",
	"vendor/smarty/smarty/README",
	"vendor/smarty/smarty/README.md",
	"vendor/smarty/smarty/SMARTY_2_BC_NOTES.txt",
	"vendor/smarty/smarty/SMARTY_3.0_BC_NOTES.txt",
	"vendor/smarty/smarty/SMARTY_3.1_NOTES.txt",
	"vendor/smarty/smarty/change_log.txt",
	"vendor/smarty/smarty/demo/configs/test.conf",
	"vendor/smarty/smarty/demo/index.php",
	"vendor/smarty/smarty/demo/plugins/cacheresource.apc.php",
	"vendor/smarty/smarty/demo/plugins/cacheresource.memcache.php",
	"vendor/smarty/smarty/demo/plugins/cacheresource.mysql.php",
	"vendor/smarty/smarty/demo/plugins/cacheresource.pdo.php",
	"vendor/smarty/smarty/demo/plugins/cacheresource.pdo_gzip.php",
	"vendor/smarty/smarty/demo/plugins/resource.extendsall.php",
	"vendor/smarty/smarty/demo/plugins/resource.mysql.php",
	"vendor/smarty/smarty/demo/plugins/resource.mysqls.php",
	"vendor/smarty/smarty/demo/templates/footer.tpl",
	"vendor/smarty/smarty/demo/templates/header.tpl",
	"vendor/smarty/smarty/demo/templates/index.tpl",
	"vendor/smarty/smarty/libs/Autoloader.php",
	"vendor/smarty/smarty/libs/Smarty.class.php",
	"vendor/smarty/smarty/libs/SmartyBC.class.php",
	"vendor/smarty/smarty/libs/bootstrap.php",
	"vendor/smarty/smarty/libs/debug.tpl",
	"vendor/smarty/smarty/libs/plugins/block.textformat.php",
	"vendor/smarty/smarty/libs/plugins/function.counter.php",
	"vendor/smarty/smarty/libs/plugins/function.cycle.php",
	"vendor/smarty/smarty/libs/plugins/function.fetch.php",
	"vendor/smarty/smarty/libs/plugins/function.html_checkboxes.php",
	"vendor/smarty/smarty/libs/plugins/function.html_image.php",
	"vendor/smarty/smarty/libs/plugins/function.html_options.php",
	"vendor/smarty/smarty/libs/plugins/function.html_radios.php",
	"vendor/smarty/smarty/libs/plugins/function.html_select_date.php",
	"vendor/smarty/smarty/libs/plugins/function.html_select_time.php",
	"vendor/smarty/smarty/libs/plugins/function.html_table.php",
	"vendor/smarty/smarty/libs/plugins/function.mailto.php",
	"vendor/smarty/smarty/libs/plugins/function.math.php",
	"vendor/smarty/smarty/libs/plugins/modifier.capitalize.php",
	"vendor/smarty/smarty/libs/plugins/modifier.date_format.php",
	"vendor/smarty/smarty/libs/plugins/modifier.debug_print_var.php",
	"vendor/smarty/smarty/libs/plugins/modifier.escape.php",
	"vendor/smarty/smarty/libs/plugins/modifier.regex_replace.php",
	"vendor/smarty/smarty/libs/plugins/modifier.replace.php",
	"vendor/smarty/smarty/libs/plugins/modifier.spacify.php",
	"vendor/smarty/smarty/libs/plugins/modifier.truncate.php",
	"vendor/smarty/smarty/libs/plugins/modifiercompiler.cat.php",
	"vendor/smarty/smarty/libs/plugins/modifiercompiler.count_characters.php",
	"vendor/smarty/smarty/libs/plugins/modifiercompiler.count_paragraphs.php",
	"vendor/smarty/smarty/libs/plugins/modifiercompiler.count_sentences.php",
	"vendor/smarty/smarty/libs/plugins/modifiercompiler.count_words.php",
	"vendor/smarty/smarty/libs/plugins/modifiercompiler.default.php",
	"vendor/smarty/smarty/libs/plugins/modifiercompiler.escape.php",
	"vendor/smarty/smarty/libs/plugins/modifiercompiler.from_charset.php",
	"vendor/smarty/smarty/libs/plugins/modifiercompiler.indent.php",
	"vendor/smarty/smarty/libs/plugins/modifiercompiler.lower.php",
	"vendor/smarty/smarty/libs/plugins/modifiercompiler.noprint.php",
	"vendor/smarty/smarty/libs/plugins/modifiercompiler.string_format.php",
	"vendor/smarty/smarty/libs/plugins/modifiercompiler.strip.php",
	"vendor/smarty/smarty/libs/plugins/modifiercompiler.strip_tags.php",
	"vendor/smarty/smarty/libs/plugins/modifiercompiler.to_charset.php",
	"vendor/smarty/smarty/libs/plugins/modifiercompiler.unescape.php",
	"vendor/smarty/smarty/libs/plugins/modifiercompiler.upper.php",
	"vendor/smarty/smarty/libs/plugins/modifiercompiler.wordwrap.php",
	"vendor/smarty/smarty/libs/plugins/outputfilter.trimwhitespace.php",
	"vendor/smarty/smarty/libs/plugins/shared.escape_special_chars.php",
	"vendor/smarty/smarty/libs/plugins/shared.literal_compiler_param.php",
	"vendor/smarty/smarty/libs/plugins/shared.make_timestamp.php",
	"vendor/smarty/smarty/libs/plugins/shared.mb_str_replace.php",
	"vendor/smarty/smarty/libs/plugins/shared.mb_unicode.php",
	"vendor/smarty/smarty/libs/plugins/shared.mb_wordwrap.php",
	"vendor/smarty/smarty/libs/plugins/variablefilter.htmlspecialchars.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_cacheresource.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_cacheresource_custom.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_cacheresource_keyvaluestore.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_data.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_block.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_cacheresource_file.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_compile_append.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_compile_assign.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_compile_block.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_compile_block_child.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_compile_block_parent.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_compile_break.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_compile_call.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_compile_capture.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_compile_config_load.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_compile_continue.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_compile_debug.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_compile_eval.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_compile_extends.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_compile_for.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_compile_foreach.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_compile_function.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_compile_if.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_compile_include.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_compile_include_php.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_compile_insert.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_compile_ldelim.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_compile_make_nocache.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_compile_nocache.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_compile_private_block_plugin.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_compile_private_foreachsection.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_compile_private_function_plugin.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_compile_private_modifier.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_compile_private_object_block_function.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_compile_private_object_function.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_compile_private_php.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_compile_private_print_expression.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_compile_private_registered_block.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_compile_private_registered_function.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_compile_private_special_variable.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_compile_rdelim.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_compile_section.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_compile_setfilter.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_compile_shared_inheritance.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_compile_while.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_compilebase.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_config_file_compiler.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_configfilelexer.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_configfileparser.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_data.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_debug.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_extension_handler.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_method_addautoloadfilters.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_method_adddefaultmodifiers.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_method_append.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_method_appendbyref.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_method_assignbyref.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_method_assignglobal.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_method_clearallassign.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_method_clearallcache.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_method_clearassign.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_method_clearcache.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_method_clearcompiledtemplate.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_method_clearconfig.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_method_compileallconfig.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_method_compilealltemplates.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_method_configload.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_method_createdata.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_method_getautoloadfilters.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_method_getconfigvariable.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_method_getconfigvars.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_method_getdebugtemplate.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_method_getdefaultmodifiers.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_method_getglobal.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_method_getregisteredobject.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_method_getstreamvariable.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_method_gettags.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_method_gettemplatevars.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_method_loadfilter.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_method_loadplugin.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_method_mustcompile.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_method_registercacheresource.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_method_registerclass.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_method_registerdefaultconfighandler.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_method_registerdefaultpluginhandler.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_method_registerdefaulttemplatehandler.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_method_registerfilter.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_method_registerobject.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_method_registerplugin.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_method_registerresource.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_method_setautoloadfilters.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_method_setdebugtemplate.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_method_setdefaultmodifiers.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_method_unloadfilter.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_method_unregistercacheresource.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_method_unregisterfilter.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_method_unregisterobject.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_method_unregisterplugin.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_method_unregisterresource.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_nocache_insert.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_parsetree.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_parsetree_code.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_parsetree_dq.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_parsetree_dqcontent.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_parsetree_tag.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_parsetree_template.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_parsetree_text.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_resource_eval.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_resource_extends.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_resource_file.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_resource_php.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_resource_registered.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_resource_stream.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_resource_string.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_runtime_cachemodify.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_runtime_cacheresourcefile.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_runtime_capture.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_runtime_codeframe.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_runtime_filterhandler.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_runtime_foreach.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_runtime_getincludepath.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_runtime_inheritance.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_runtime_make_nocache.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_runtime_tplfunction.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_runtime_updatecache.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_runtime_updatescope.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_runtime_writefile.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_smartytemplatecompiler.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_template.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_templatebase.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_templatecompilerbase.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_templatelexer.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_templateparser.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_testinstall.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_internal_undefined.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_resource.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_resource_custom.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_resource_recompiled.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_resource_uncompiled.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_security.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_template_cached.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_template_compiled.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_template_config.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_template_resource_base.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_template_source.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_undefined_variable.php",
	"vendor/smarty/smarty/libs/sysplugins/smarty_variable.php",
	"vendor/smarty/smarty/libs/sysplugins/smartycompilerexception.php",
	"vendor/smarty/smarty/libs/sysplugins/smartyexception.php"
);
