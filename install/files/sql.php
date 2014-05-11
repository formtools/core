<?php


/**
 * Stores all the SQL for generating the Form Tools Core. Modules and themes are added separately by the
 * installation script.
 */

$g_sql = array();

$g_sql[] = "CREATE TABLE %PREFIX%account_settings (
  account_id mediumint(8) unsigned NOT NULL,
  setting_name varchar(255) NOT NULL,
  setting_value mediumtext NOT NULL,
  PRIMARY KEY  (account_id,setting_name)
) DEFAULT CHARSET=%CHARSET%";

$g_sql[] = "CREATE TABLE %PREFIX%accounts (
  account_id mediumint(8) unsigned NOT NULL auto_increment,
  account_type enum('admin','client') NOT NULL default 'client',
  account_status enum('active','disabled','pending') NOT NULL default 'disabled',
  last_logged_in datetime default NULL,
  ui_language varchar(50) NOT NULL default 'en_us',
  timezone_offset varchar(4) default NULL,
  sessions_timeout varchar(10) NOT NULL default '30',
  date_format varchar(50) NOT NULL default 'M jS, g:i A',
  login_page varchar(50) NOT NULL default 'client_forms',
  logout_url varchar(255) default NULL,
  theme varchar(50) NOT NULL default 'default',
  swatch varchar(255) NOT NULL,
  menu_id mediumint(8) unsigned NOT NULL,
  first_name varchar(100) default NULL,
  last_name varchar(100) default NULL,
  email varchar(200) default NULL,
  username varchar(50) NOT NULL,
  password varchar(50) NOT NULL,
  temp_reset_password varchar(50) NULL,
  PRIMARY KEY (account_id)
) DEFAULT CHARSET=%CHARSET%";

$g_sql[] = "INSERT INTO %PREFIX%accounts (account_id, account_type, account_status, timezone_offset, login_page, swatch, menu_id, username, password) VALUES (1, 'admin', 'active', '0', 'admin_forms', 'green', 1, '', '')";

$g_sql[] = "CREATE TABLE %PREFIX%client_forms (
  account_id mediumint(8) unsigned NOT NULL,
  form_id mediumint(8) unsigned NOT NULL,
  PRIMARY KEY (account_id,form_id)
) DEFAULT CHARSET=%CHARSET%";

$g_sql[] = "CREATE TABLE %PREFIX%client_views (
  account_id mediumint(8) unsigned NOT NULL,
  view_id mediumint(8) unsigned NOT NULL,
  PRIMARY KEY (account_id,view_id)
) DEFAULT CHARSET=%CHARSET%";

$g_sql[] = "CREATE TABLE %PREFIX%email_template_edit_submission_views (
  email_id mediumint(8) unsigned NOT NULL,
  view_id mediumint(8) unsigned NOT NULL,
  PRIMARY KEY  (email_id,view_id)
) DEFAULT CHARSET=%CHARSET%";

$g_sql[] = "CREATE TABLE %PREFIX%email_template_recipients (
  recipient_id mediumint(8) unsigned NOT NULL auto_increment,
  email_template_id mediumint(8) unsigned NOT NULL,
  recipient_user_type enum('admin','client','form_email_field','custom') NOT NULL,
  recipient_type enum('main','cc','bcc') NOT NULL default 'main',
  account_id mediumint(9) default NULL,
  form_email_id MEDIUMINT UNSIGNED NULL,
  custom_recipient_name varchar(200) default NULL,
  custom_recipient_email varchar(200) default NULL,
  PRIMARY KEY  (recipient_id)
) DEFAULT CHARSET=%CHARSET%";

$g_sql[] = "CREATE TABLE %PREFIX%email_template_when_sent_views (
  email_id mediumint(9) NOT NULL,
  view_id mediumint(9) NOT NULL
) DEFAULT CHARSET=%CHARSET%";

$g_sql[] = "CREATE TABLE %PREFIX%email_templates (
  email_id mediumint(8) unsigned NOT NULL auto_increment,
  form_id mediumint(8) unsigned NOT NULL,
  email_template_name varchar(100) default NULL,
  email_status enum('enabled','disabled') NOT NULL default 'enabled',
  view_mapping_type enum('all','specific') NOT NULL default 'all',
  view_mapping_view_id mediumint(9) default NULL,
  limit_email_content_to_fields_in_view mediumint(9) default NULL,
  email_event_trigger set('on_submission','on_edit','on_delete') default NULL,
  include_on_edit_submission_page enum('no','all_views','specific_views') NOT NULL default 'no',
  subject varchar(255) default NULL,
  email_from enum('admin','client','form_email_field','custom','none') default NULL,
  email_from_account_id mediumint(8) unsigned default NULL,
  email_from_form_email_id MEDIUMINT UNSIGNED default NULL,
  custom_from_name varchar(100) default NULL,
  custom_from_email varchar(100) default NULL,
  email_reply_to enum('admin','client','form_email_field','custom','none') default NULL,
  email_reply_to_account_id mediumint(8) unsigned default NULL,
  email_reply_to_form_email_id MEDIUMINT UNSIGNED NULL,
  custom_reply_to_name varchar(100) default NULL,
  custom_reply_to_email varchar(100) default NULL,
  html_template mediumtext,
  text_template mediumtext,
  PRIMARY KEY (email_id)
) DEFAULT CHARSET=%CHARSET%";

$g_sql[] = "CREATE TABLE %PREFIX%field_options (
  list_id mediumint(8) unsigned NOT NULL,
  list_group_id mediumint(9) NOT NULL,
  option_order smallint(4) NOT NULL,
  option_value varchar(255) NOT NULL,
  option_name varchar(255) NOT NULL,
  is_new_sort_group enum('yes', 'no') NOT NULL,
  PRIMARY KEY (list_id, list_group_id, option_order)
) DEFAULT CHARSET=%CHARSET%";

$g_sql[] = "CREATE TABLE %PREFIX%field_settings (
  field_id mediumint(8) unsigned NOT NULL,
  setting_id mediumint(9) NOT NULL,
  setting_value mediumtext,
  PRIMARY KEY (field_id,setting_id)
) DEFAULT CHARSET=%CHARSET%";

$g_sql[] = "CREATE TABLE %PREFIX%field_type_setting_options (
  setting_id mediumint(9) NOT NULL,
  option_text varchar(255) default NULL,
  option_value varchar(255) default NULL,
  option_order smallint(6) NOT NULL,
  is_new_sort_group enum('yes','no') NOT NULL,
  PRIMARY KEY  (setting_id,option_order)
) DEFAULT CHARSET=%CHARSET%";

$g_sql[] = "CREATE TABLE %PREFIX%field_type_settings (
  setting_id mediumint(8) unsigned NOT NULL auto_increment,
  field_type_id mediumint(8) unsigned NOT NULL,
  field_label varchar(255) NOT NULL,
  field_setting_identifier varchar(50) NOT NULL,
  field_type enum('textbox','textarea','radios','checkboxes','select','multi-select','option_list_or_form_field') NOT NULL,
  field_orientation enum('horizontal','vertical','na') NOT NULL default 'na',
  default_value_type enum('static','dynamic') NOT NULL default 'static',
  default_value varchar(255) default NULL,
  list_order smallint(6) NOT NULL,
  PRIMARY KEY (setting_id)
) DEFAULT CHARSET=%CHARSET%";

$g_sql[] = "CREATE TABLE %PREFIX%field_types (
  field_type_id mediumint(8) unsigned NOT NULL auto_increment,
  is_editable enum('yes','no') NOT NULL,
  non_editable_info mediumtext,
  managed_by_module_id mediumint(9) default NULL,
  field_type_name varchar(255) NOT NULL,
  field_type_identifier varchar(50) NOT NULL,
  group_id smallint(6) NOT NULL,
  is_file_field enum('yes','no') NOT NULL default 'no',
  is_date_field enum('yes','no') NOT NULL default 'no',
  raw_field_type_map varchar(50) default NULL,
  raw_field_type_map_multi_select_id mediumint(9) default NULL,
  list_order smallint(6) NOT NULL,
  compatible_field_sizes varchar(255) NOT NULL,
  view_field_rendering_type enum('none','php','smarty') NOT NULL default 'none',
  view_field_php_function_source varchar(255) default NULL,
  view_field_php_function varchar(255) default NULL,
  view_field_smarty_markup mediumtext NOT NULL,
  edit_field_smarty_markup mediumtext NOT NULL,
  php_processing mediumtext NOT NULL,
  resources_css mediumtext,
  resources_js mediumtext,
  PRIMARY KEY  (field_type_id)
) DEFAULT CHARSET=%CHARSET%";

$g_sql[] = "CREATE TABLE %PREFIX%field_type_validation_rules (
  rule_id mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  field_type_id mediumint(9) NOT NULL,
  rsv_rule varchar(50) NOT NULL,
  rule_label varchar(100) NOT NULL,
  rsv_field_name varchar(255) NOT NULL,
  custom_function varchar(100) NOT NULL,
  custom_function_required enum('yes','no','na') NOT NULL DEFAULT 'na',
  default_error_message mediumtext NOT NULL,
  list_order smallint(6) NOT NULL,
  PRIMARY KEY (rule_id)
) DEFAULT CHARSET=%CHARSET%";

$g_sql[] = "CREATE TABLE %PREFIX%field_validation (
  rule_id mediumint(8) unsigned NOT NULL,
  field_id mediumint(9) NOT NULL,
  error_message mediumtext NOT NULL,
  UNIQUE KEY rule_id (rule_id,field_id)
) DEFAULT CHARSET=%CHARSET%";

$g_sql[] = "CREATE TABLE %PREFIX%form_email_fields (
  form_email_id MEDIUMINT unsigned NOT NULL auto_increment,
  form_id MEDIUMINT UNSIGNED NOT NULL,
  email_field_id mediumint(9) NOT NULL,
  first_name_field_id mediumint(9) NULL,
  last_name_field_id mediumint(9) NULL,
  PRIMARY KEY (form_email_id)
) DEFAULT CHARSET=%CHARSET%";

$g_sql[] = "CREATE TABLE %PREFIX%form_fields (
  field_id mediumint(8) unsigned NOT NULL auto_increment,
  form_id mediumint(8) unsigned NOT NULL default '0',
  field_name varchar(255) NOT NULL default '',
  field_test_value mediumtext,
  field_size varchar(255) default 'medium',
  field_type_id smallint(6) NOT NULL default '1',
  is_system_field enum('yes','no') NOT NULL default 'no',
  data_type enum('string','number','date') NOT NULL default 'string',
  field_title varchar(100) default NULL,
  col_name varchar(100) default NULL,
  list_order smallint(5) unsigned default NULL,
  is_new_sort_group enum('yes','no') NOT NULL default 'yes',
  include_on_redirect enum('yes','no') NOT NULL default 'no',
  PRIMARY KEY (field_id)
) DEFAULT CHARSET=%CHARSET%";

$g_sql[] = "CREATE TABLE %PREFIX%forms (
  form_id mediumint(9) unsigned NOT NULL auto_increment,
  form_type enum('internal','external','form_builder') NOT NULL default 'external',
  access_type enum('admin','public','private') NOT NULL default 'public',
  submission_type enum('code','direct') default NULL,
  date_created datetime NOT NULL,
  is_active enum('yes','no') NOT NULL default 'no',
  is_initialized enum('yes','no') NOT NULL default 'no',
  is_complete enum('yes','no') NOT NULL default 'no',
  is_multi_page_form enum('yes','no') NOT NULL default 'no',
  form_name varchar(255) NOT NULL default '',
  form_url varchar(255) NOT NULL default '',
  redirect_url varchar(255) default NULL,
  auto_delete_submission_files enum('yes','no') NOT NULL default 'yes',
  submission_strip_tags enum('yes','no') NOT NULL default 'yes',
  edit_submission_page_label text,
  add_submission_button_label varchar(255) default '{\$LANG.word_add_rightarrow}',
  PRIMARY KEY (form_id)
) DEFAULT CHARSET=%CHARSET%";

$g_sql[] = "CREATE TABLE %PREFIX%hooks (
  id mediumint(8) unsigned NOT NULL auto_increment,
  hook_type enum('code','template') NOT NULL,
  component enum('core','api','module') NOT NULL,
  filepath varchar(255) NOT NULL,
  action_location varchar(255) NOT NULL,
  function_name varchar(255) NOT NULL,
  params mediumtext,
  overridable mediumtext,
  PRIMARY KEY (id)
) DEFAULT CHARSET=%CHARSET%";

$g_sql[] = "CREATE TABLE %PREFIX%hook_calls (
  hook_id mediumint(8) unsigned NOT NULL auto_increment,
  hook_type enum('code','template') NOT NULL default 'code',
  action_location varchar(100) NOT NULL,
  module_folder varchar(255) NOT NULL,
  function_name varchar(255) NOT NULL,
  hook_function varchar(255) NOT NULL,
  priority tinyint(4) NOT NULL default '50',
  PRIMARY KEY  (hook_id)
) DEFAULT CHARSET=%CHARSET%";

$g_sql[] = "CREATE TABLE %PREFIX%list_groups (
  group_id mediumint(8) unsigned NOT NULL auto_increment,
  group_type varchar(50) NOT NULL,
  group_name varchar(255) NOT NULL,
  custom_data text NOT NULL,
  list_order smallint(6) NOT NULL,
  PRIMARY KEY (group_id)
) DEFAULT CHARSET=%CHARSET%";

$g_sql[] = "CREATE TABLE %PREFIX%menu_items (
  menu_item_id mediumint(8) unsigned NOT NULL auto_increment,
  menu_id mediumint(8) unsigned NOT NULL,
  display_text varchar(100) NOT NULL,
  page_identifier varchar(50) NOT NULL,
  custom_options varchar(255) NOT NULL,
  url varchar(255) default NULL,
  is_submenu enum('yes','no') NOT NULL default 'no',
  is_new_sort_group enum('yes','no') NOT NULL default 'yes',
  list_order smallint(5) unsigned default NULL,
  PRIMARY KEY (menu_item_id)
) DEFAULT CHARSET=%CHARSET%";

$g_sql[] = "INSERT INTO %PREFIX%menu_items VALUES (1, 1, 'Forms', 'admin_forms', '', '/admin/forms/', 'no', 'yes', 1)";
$g_sql[] = "INSERT INTO %PREFIX%menu_items VALUES (2, 1, 'Add Form', 'add_form_choose_type', '', '/admin/forms/add/', 'yes', 'no', 2)";
$g_sql[] = "INSERT INTO %PREFIX%menu_items VALUES (3, 1, 'Option Lists', 'option_lists', '', '/admin/forms/option_lists/', 'yes', 'no', 3)";
$g_sql[] = "INSERT INTO %PREFIX%menu_items VALUES (4, 1, 'Clients', 'clients', '', '/admin/clients/', 'no', 'yes', 4)";
$g_sql[] = "INSERT INTO %PREFIX%menu_items VALUES (5, 1, 'Modules', 'modules', '', '/admin/modules/', 'no', 'yes', 5)";
$g_sql[] = "INSERT INTO %PREFIX%menu_items VALUES (6, 1, 'Themes', 'settings_themes', '', '/admin/themes/', 'no', 'yes', 6)";
$g_sql[] = "INSERT INTO %PREFIX%menu_items VALUES (7, 1, 'Settings', 'settings', '', '/admin/settings', 'no', 'yes', 7)";
$g_sql[] = "INSERT INTO %PREFIX%menu_items VALUES (8, 1, 'Main', 'settings_main', '', '/admin/settings/index.php?page=main', 'yes', 'no', 8)";
$g_sql[] = "INSERT INTO %PREFIX%menu_items VALUES (9, 1, 'Accounts', 'settings_accounts', '', '/admin/settings/index.php?page=accounts', 'yes', 'no', 9)";
$g_sql[] = "INSERT INTO %PREFIX%menu_items VALUES (10, 1, 'Files', 'settings_files', '', '/admin/settings/index.php?page=files', 'yes', 'no', 10)";
$g_sql[] = "INSERT INTO %PREFIX%menu_items VALUES (11, 1, 'Menus', 'settings_menus', '', '/admin/settings/index.php?page=menus', 'yes', 'no', 11)";
$g_sql[] = "INSERT INTO %PREFIX%menu_items VALUES (12, 1, 'Your Account', 'your_account', '', '/admin/account', 'no', 'yes', 12)";
$g_sql[] = "INSERT INTO %PREFIX%menu_items VALUES (13, 1, 'Logout', 'logout', '', '/index.php?logout', 'no', 'yes', 13)";
$g_sql[] = "INSERT INTO %PREFIX%menu_items VALUES (14, 2, 'Forms', 'client_forms', '', '/clients/index.php', 'no', 'yes', 1)";
$g_sql[] = "INSERT INTO %PREFIX%menu_items VALUES (15, 2, 'Account', 'client_account', '', '/clients/account/index.php', 'no', 'yes', 2)";
$g_sql[] = "INSERT INTO %PREFIX%menu_items VALUES (16, 2, 'Login Info', 'client_account_login', '', '/clients/account/index.php?page=main', 'yes', 'no', 3)";
$g_sql[] = "INSERT INTO %PREFIX%menu_items VALUES (17, 2, 'Settings', 'client_account_settings', '', '/clients/account/index.php?page=settings', 'yes', 'no', 4)";
$g_sql[] = "INSERT INTO %PREFIX%menu_items VALUES (18, 2, 'Logout', 'logout', '', '/index.php?logout', 'no', 'yes', 5)";

$g_sql[] = "CREATE TABLE %PREFIX%menus (
  menu_id smallint(5) unsigned NOT NULL auto_increment,
  menu varchar(255) NOT NULL,
  menu_type enum('admin','client') NOT NULL default 'client',
  PRIMARY KEY  (menu_id)
) DEFAULT CHARSET=%CHARSET%";

$g_sql[] = "INSERT INTO %PREFIX%menus VALUES (1, 'Administrator', 'admin')";
$g_sql[] = "INSERT INTO %PREFIX%menus VALUES (2, 'Client Menu', 'client')";

$g_sql[] = "CREATE TABLE %PREFIX%module_menu_items (
  menu_id mediumint(8) unsigned NOT NULL auto_increment,
  module_id mediumint(8) unsigned NOT NULL,
  display_text varchar(100) NOT NULL,
  url varchar(255) NOT NULL,
  is_submenu enum('yes','no') NOT NULL default 'no',
  list_order smallint(6) NOT NULL,
  PRIMARY KEY  (menu_id)
) DEFAULT CHARSET=%CHARSET%";

$g_sql[] = "CREATE TABLE %PREFIX%modules (
  module_id mediumint(8) unsigned NOT NULL auto_increment,
  is_installed enum('yes','no') NOT NULL default 'no',
  is_enabled enum('yes','no') NOT NULL default 'no',
  is_premium enum('yes','no') NOT NULL default 'no',
  module_key varchar(15) default NULL,
  origin_language varchar(50) NOT NULL,
  module_name varchar(100) NOT NULL,
  module_folder varchar(100) NOT NULL,
  version varchar(50) default NULL,
  author varchar(200) default NULL,
  author_email varchar(200) default NULL,
  author_link varchar(255) default NULL,
  description mediumtext NOT NULL,
  module_date datetime NOT NULL,
  PRIMARY KEY  (module_id)
) DEFAULT CHARSET=%CHARSET%";

$g_sql[] = "CREATE TABLE %PREFIX%multi_page_form_urls (
  form_id mediumint(8) unsigned NOT NULL,
  form_url varchar(255) NOT NULL,
  page_num tinyint(4) NOT NULL default '2',
  PRIMARY KEY  (form_id, page_num)
) DEFAULT CHARSET=%CHARSET%";

$g_sql[] = "CREATE TABLE %PREFIX%new_view_submission_defaults (
  view_id mediumint(9) NOT NULL,
  field_id mediumint(9) NOT NULL,
  default_value text NOT NULL,
  list_order smallint(6) NOT NULL,
  PRIMARY KEY (view_id,field_id)
) DEFAULT CHARSET=%CHARSET%";

$g_sql[] = "CREATE TABLE %PREFIX%option_lists (
  list_id mediumint(8) unsigned NOT NULL auto_increment,
  option_list_name varchar(100) NOT NULL,
  is_grouped enum('yes','no') NOT NULL,
  original_form_id mediumint(8) unsigned default NULL,
  PRIMARY KEY (list_id)
) DEFAULT CHARSET=%CHARSET%";

$g_sql[] = "CREATE TABLE %PREFIX%public_form_omit_list (
  form_id mediumint(8) unsigned NOT NULL,
  account_id mediumint(8) unsigned NOT NULL,
  PRIMARY KEY  (form_id,account_id)
) DEFAULT CHARSET=%CHARSET%";

$g_sql[] = "CREATE TABLE %PREFIX%public_view_omit_list (
  view_id mediumint(8) unsigned NOT NULL,
  account_id mediumint(8) unsigned NOT NULL,
  PRIMARY KEY  (view_id,account_id)
) DEFAULT CHARSET=%CHARSET%";

$g_sql[] = "CREATE TABLE %PREFIX%sessions (
  session_id varchar(100) NOT NULL default '',
  session_data text NOT NULL,
  expires int(11) NOT NULL default '0',
  PRIMARY KEY (session_id)
) DEFAULT CHARSET=latin1";

$g_sql[] = "CREATE TABLE %PREFIX%settings (
  setting_id mediumint(9) NOT NULL auto_increment,
  setting_name varchar(100) NOT NULL,
  setting_value text NOT NULL,
  module varchar(100) NOT NULL default 'core',
  PRIMARY KEY  (setting_id)
) DEFAULT CHARSET=%CHARSET%";

// changes per release
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('program_version', '%FORMTOOLSVERSION%', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('release_date', '%FORMTOOLSRELEASEDATE%', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('release_type', '%FORMTOOLSRELEASETYPE%', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('api_version', '', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('available_languages', 'en_us,English (US)', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('clients_may_edit_date_format', 'no', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('clients_may_edit_footer_text', 'no', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('clients_may_edit_logout_url', 'yes', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('clients_may_edit_max_failed_login_attempts', 'no', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('clients_may_edit_page_titles', 'no', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('clients_may_edit_sessions_timeout', 'no', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('clients_may_edit_theme', 'yes', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('clients_may_edit_timezone_offset', 'yes', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('clients_may_edit_ui_language', 'yes', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('default_client_menu_id', '2', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('default_client_swatch', 'green', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('default_date_field_search_value', 'none', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('default_date_format', 'M jS y, g:i A', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('default_footer_text', '', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('default_language', 'en_us', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('default_login_page', 'client_forms', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('default_logout_url', '', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('default_max_failed_login_attempts', '', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('default_num_submissions_per_page', '10', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('default_page_titles', 'Form Tools - {\$page}', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('default_sessions_timeout', '30', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('default_theme', 'default', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('default_timezone_offset', '0', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('edit_submission_shared_resources_js', '\$(function() {\r\n  \$(\".fancybox\").fancybox();\r\n});\r\n', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('edit_submission_shared_resources_css', '/* used in the \"Highlight\" setting for most field types */\r\n.cf_colour_red { \r\n  background-color: #990000;\r\n  color: white;\r\n}\r\n.cf_colour_orange {\r\n  background-color: orange; \r\n}\r\n.cf_colour_yellow {\r\n  background-color: yellow; \r\n}\r\n.cf_colour_green {\r\n  background-color: green;\r\n  color: white; \r\n}\r\n.cf_colour_blue {\r\n  background-color: #336699; \r\n  color: white; \r\n}\r\n\r\n/* field comments */\r\n.cf_field_comments {\r\n  font-style: italic;\r\n  color: #999999;\r\n  clear: both;\r\n}\r\n\r\n/* column layouts for radios & checkboxes */\r\n.cf_option_list_group_label {\r\n  font-weight: bold;  \r\n  clear: both;\r\n  margin-left: 4px;\r\n}\r\n.cf_option_list_2cols, .cf_option_list_3cols, .cf_option_list_4cols {\r\n  clear: both; \r\n}\r\n.cf_option_list_2cols .column { \r\n  width: 50%;\r\n  float: left; \r\n}\r\n.cf_option_list_3cols .column { \r\n  width: 33%;\r\n  float: left;\r\n}\r\n.cf_option_list_4cols .column { \r\n  width: 25%;\r\n  float: left;\r\n}\r\n\r\n/* Used for the date and time pickers */\r\n.cf_date_group img {\r\n  margin-bottom: -4px;\r\n  padding: 1px;\r\n}\r\n\r\n', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('edit_submission_onload_resources', '<script src=\"{\$g_root_url}/global/codemirror/js/codemirror.js\"></script>|<script src=\"{\$g_root_url}/global/scripts/jquery-ui-timepicker-addon.js\"></script>|<script src=\"{\$g_root_url}/global/fancybox/jquery.fancybox-1.3.4.pack.js\"></script> |<link rel=\"stylesheet\" href=\"{\$g_root_url}/global/fancybox/jquery.fancybox-1.3.4.css\" type=\"text/css\" media=\"screen\" />', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('field_type_settings_shared_characteristics', 'field_comments:textbox,comments`textarea,comments`password,comments`dropdown,comments`multi_select_dropdown,comments`radio_buttons,comments`checkboxes,comments`date,comments`time,comments`phone,comments`code_markup,comments`file,comments`google_maps_field,comments`tinymce,comments|data_source:dropdown,contents`multi_select_dropdown,contents`radio_buttons,contents`checkboxes,contents|column_formatting:checkboxes,formatting`radio_buttons,formatting|maxlength_attr:textbox,maxlength|colour_highlight:textbox,highlight|folder_path:file,folder_path|folder_url:file,folder_url|permitted_file_types:file,folder_url|max_file_size:file,max_file_size|date_display_format:date,display_format|apply_timezone_offset:date,apply_timezone_offset', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('file_upload_dir', '', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('file_upload_filetypes', 'bmp,gif,jpg,jpeg,png,avi,mp3,mp4,doc,txt,pdf,xml,csv,swf,fla,xls,tif', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('file_upload_url', '', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('file_upload_max_size', '200', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('forms_page_default_message', '{\$LANG.text_client_welcome}', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('logo_link', 'http://www.formtools.org', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('min_password_length', '', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('num_clients_per_page', '10', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('num_emails_per_page', '10', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('num_forms_per_page', '10', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('num_menus_per_page', '10', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('num_modules_per_page', '10', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('num_option_lists_per_page', '10', 'core');";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('num_password_history', '', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('program_name', 'Form Tools', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('required_password_chars', '', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('timezone_offset', '0', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('core_version_upgrade_track', '%FORMTOOLSVERSION%', 'core')";

$g_sql[] = "CREATE TABLE %PREFIX%themes (
  theme_id mediumint(8) unsigned NOT NULL auto_increment,
  theme_folder varchar(100) NOT NULL,
  theme_name varchar(50) NOT NULL,
  uses_swatches enum('yes', 'no') NOT NULL DEFAULT 'no',
  swatches mediumtext NULL,
  author varchar(200) default NULL,
  author_email varchar(255) default NULL,
  author_link varchar(255) default NULL,
  theme_link varchar(255) default NULL,
  description mediumtext,
  is_enabled enum('yes','no') NOT NULL default 'yes',
  theme_version varchar(50) default NULL,
  PRIMARY KEY (theme_id)
) DEFAULT CHARSET=%CHARSET%";

$g_sql[] = "INSERT INTO %PREFIX%themes VALUES (1, 'default', 'Default', 'yes', 'green', 'Encore Web Studios', 'formtools@encorewebstudios.com', 'http://www.encorewebstudios.com', 'http://themes.formtools.org/', 'The default Form Tools theme for all new installations. It''s a green-coloured fixed-width theme requiring 1024 minimum width screens.', 'yes', '1.0.0')";

$g_sql[] = "CREATE TABLE %PREFIX%view_columns (
  view_id mediumint(9) NOT NULL,
  field_id mediumint(9) NOT NULL,
  list_order smallint(6) NOT NULL,
  is_sortable enum('yes','no') NOT NULL,
  auto_size enum('yes','no') NOT NULL default 'yes',
  custom_width varchar(10) default NULL,
  truncate enum('truncate','no_truncate') NOT NULL default 'truncate',
  PRIMARY KEY  (view_id,field_id,list_order)
) DEFAULT CHARSET=%CHARSET%";

$g_sql[] = "CREATE TABLE %PREFIX%view_fields (
  view_id mediumint(8) unsigned NOT NULL,
  field_id mediumint(8) unsigned NOT NULL,
  group_id mediumint(9) default NULL,
  is_editable enum('yes','no') NOT NULL default 'yes',
  is_searchable enum('yes','no') NOT NULL default 'yes',
  list_order smallint(5) unsigned default NULL,
  is_new_sort_group enum('yes','no') NOT NULL,
  PRIMARY KEY  (view_id,field_id)
) DEFAULT CHARSET=%CHARSET%";

$g_sql[] = "CREATE TABLE %PREFIX%view_filters (
  filter_id mediumint(8) unsigned NOT NULL auto_increment,
  view_id mediumint(8) unsigned NOT NULL,
  filter_type enum('standard', 'client_map') NOT NULL default 'standard',
  field_id mediumint(8) unsigned NOT NULL,
  operator enum('equals','not_equals','like','not_like','before','after') NOT NULL default 'equals',
  filter_values mediumtext NOT NULL,
  filter_sql mediumtext NOT NULL,
  PRIMARY KEY  (filter_id)
) DEFAULT CHARSET=%CHARSET%";

$g_sql[] = "CREATE TABLE %PREFIX%view_tabs (
  view_id mediumint(8) unsigned NOT NULL,
  tab_number tinyint(3) unsigned NOT NULL,
  tab_label varchar(50) default NULL,
  PRIMARY KEY  (view_id,tab_number)
) DEFAULT CHARSET=%CHARSET%";

$g_sql[] = "CREATE TABLE %PREFIX%views (
  view_id smallint(6) NOT NULL auto_increment,
  form_id mediumint(8) unsigned NOT NULL,
  access_type enum('admin','public','private','hidden') NOT NULL default 'public',
  view_name varchar(100) NOT NULL,
  view_order smallint(6) NOT NULL default '1',
  is_new_sort_group enum('yes','no') NOT NULL,
  group_id smallint(6) default NULL,
  num_submissions_per_page smallint(6) NOT NULL default '10',
  default_sort_field varchar(255) NOT NULL default 'submission_date',
  default_sort_field_order enum('asc','desc') NOT NULL default 'desc',
  may_add_submissions enum('yes','no') NOT NULL DEFAULT 'yes',
  may_edit_submissions enum('yes','no') NOT NULL default 'yes',
  may_delete_submissions enum('yes','no') NOT NULL default 'yes',
  has_client_map_filter enum('yes','no') NOT NULL default 'no',
  has_standard_filter enum('yes','no') NOT NULL default 'no',
  PRIMARY KEY  (view_id)
) DEFAULT CHARSET=%CHARSET%";
