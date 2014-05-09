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
) TYPE=MyISAM DEFAULT CHARSET=utf8";

$g_sql[] = "CREATE TABLE %PREFIX%accounts (
  account_id mediumint(8) unsigned NOT NULL auto_increment,
  account_type enum('admin','client') NOT NULL default 'client',
  account_status enum('active','disabled','pending') NOT NULL default 'disabled',
  ui_language varchar(50) NOT NULL default 'en_us',
  timezone_offset varchar(4) default NULL,
  sessions_timeout varchar(10) NOT NULL default '30',
  date_format varchar(50) NOT NULL default 'M jS, g:i A',
  login_page varchar(50) NOT NULL default 'client_forms',
  logout_url varchar(255) default NULL,
  theme varchar(50) NOT NULL default 'default',
  menu_id mediumint(8) unsigned NOT NULL,
  first_name varchar(100) default NULL,
  last_name varchar(100) default NULL,
  email varchar(200) default NULL,
  username varchar(50) default NULL,
  password varchar(50) default NULL,
  PRIMARY KEY (account_id)
) TYPE=MyISAM DEFAULT CHARSET=utf8";

$g_sql[] = "INSERT INTO %PREFIX%accounts (account_id, account_type, account_status, timezone_offset, login_page, menu_id) VALUES (1, 'admin', 'active', '0', 'admin_forms', 1)";

$g_sql[] = "CREATE TABLE %PREFIX%client_forms (
  account_id mediumint(8) unsigned NOT NULL,
  form_id mediumint(8) unsigned NOT NULL,
  PRIMARY KEY (account_id,form_id)
) TYPE=MyISAM DEFAULT CHARSET=utf8";

$g_sql[] = "CREATE TABLE %PREFIX%client_views (
  account_id mediumint(8) unsigned NOT NULL,
  view_id mediumint(8) unsigned NOT NULL,
  PRIMARY KEY (account_id,view_id)
) TYPE=MyISAM DEFAULT CHARSET=utf8";

$g_sql[] = "CREATE TABLE %PREFIX%email_template_edit_submission_views (
  email_id mediumint(8) unsigned NOT NULL,
  view_id mediumint(8) unsigned NOT NULL,
  PRIMARY KEY  (email_id,view_id)
) TYPE=MyISAM DEFAULT CHARSET=utf8";

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
) TYPE=MyISAM DEFAULT CHARSET=utf8";

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
  email_from_form_email_id MEDIUMINT UNSIGNED NULL,
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
) TYPE=MyISAM DEFAULT CHARSET=utf8";

$g_sql[] = "CREATE TABLE %PREFIX%field_option_groups (
  group_id mediumint(8) unsigned NOT NULL auto_increment,
  group_name varchar(100) NOT NULL,
  original_form_id mediumint(8) unsigned default NULL,
  field_orientation enum('horizontal','vertical','na') NOT NULL default 'na',
  PRIMARY KEY (group_id)
) TYPE=MyISAM DEFAULT CHARSET=utf8";

$g_sql[] = "CREATE TABLE %PREFIX%field_options (
  field_group_id mediumint(8) unsigned NOT NULL,
  option_order smallint(4) NOT NULL,
  option_value varchar(255) NOT NULL,
  option_name varchar(255) NOT NULL,
  PRIMARY KEY (field_group_id, option_order)
) TYPE=MyISAM DEFAULT CHARSET=utf8";

$g_sql[] = "CREATE TABLE %PREFIX%field_settings (
  field_id mediumint(8) unsigned NOT NULL,
  setting_name varchar(100) NOT NULL,
  setting_value varchar(100) default NULL,
  module varchar(100) NOT NULL default 'core',
  PRIMARY KEY (field_id,setting_name,module)
) TYPE=MyISAM DEFAULT CHARSET=utf8";

$g_sql[] = "CREATE TABLE %PREFIX%form_email_fields (
  form_email_id MEDIUMINT unsigned NOT NULL auto_increment,
  form_id MEDIUMINT UNSIGNED NOT NULL,
  email_field VARCHAR( 255 ) NOT NULL,
  first_name_field VARCHAR( 255 ) NULL,
  last_name_field VARCHAR( 255 ) NULL,
  PRIMARY KEY (form_email_id)
) TYPE=MyISAM DEFAULT CHARSET=utf8";

$g_sql[] = "CREATE TABLE %PREFIX%form_fields (
  field_id mediumint(8) unsigned NOT NULL auto_increment,
  form_id mediumint(8) unsigned NOT NULL default '0',
  field_name varchar(255) NOT NULL default '',
  field_test_value mediumtext,
  field_size enum('tiny','small','medium','large','very_large') default 'medium',
  field_type enum('select','multi-select','radio-buttons','checkboxes','file','textbox','textarea','system','wysiwyg','date','image','password') NOT NULL default 'textbox',
  data_type enum('string','number','date') NOT NULL default 'string',
  field_title varchar(100) default NULL,
  col_name varchar(100) default NULL,
  list_order smallint(5) unsigned default NULL,
  include_on_redirect enum('yes','no') NOT NULL default 'no',
  `field_group_id` mediumint(9) default NULL,
  PRIMARY KEY  (field_id)
) TYPE=MyISAM DEFAULT CHARSET=utf8";

$g_sql[] = "CREATE TABLE %PREFIX%forms (
  form_id mediumint(9) unsigned NOT NULL auto_increment,
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
  default_view_id mediumint(8) unsigned default NULL,
  edit_submission_page_label text,
  PRIMARY KEY  (form_id)
) TYPE=MyISAM DEFAULT CHARSET=utf8";

$g_sql[] = "CREATE TABLE %PREFIX%hooks (
  hook_id mediumint(8) unsigned NOT NULL auto_increment,
  hook_type enum('code','template') NOT NULL default 'code',
  action_location varchar(100) NOT NULL,
  module_folder varchar(255) NOT NULL,
  core_function varchar(255) NOT NULL,
  hook_function varchar(255) NOT NULL,
  priority tinyint(4) NOT NULL default '50',
  PRIMARY KEY  (hook_id)
) TYPE=MyISAM DEFAULT CHARSET=utf8";

$g_sql[] = "CREATE TABLE %PREFIX%menu_items (
  menu_item_id mediumint(8) unsigned NOT NULL auto_increment,
  menu_id mediumint(8) unsigned NOT NULL,
  display_text varchar(100) NOT NULL,
  page_identifier varchar(50) NOT NULL,
  custom_options varchar(255) NOT NULL,
  url varchar(255) default NULL,
  is_submenu enum('yes','no') NOT NULL default 'no',
  list_order smallint(5) unsigned default NULL,
  PRIMARY KEY (menu_item_id)
) TYPE=MyISAM DEFAULT CHARSET=utf8";

$g_sql[] = "INSERT INTO %PREFIX%menu_items VALUES (1, 1, 'Forms', 'admin_forms', '', '/admin/forms/', 'no', 1)";
$g_sql[] = "INSERT INTO %PREFIX%menu_items VALUES (2, 1, 'Add Form', 'add_form1', '', '/admin/forms/add/step1.php?add', 'yes', 2)";
$g_sql[] = "INSERT INTO %PREFIX%menu_items VALUES (3, 1, 'Field Option Groups', 'field_option_groups', '', '/admin/forms/field_option_groups/', 'yes', 3)";
$g_sql[] = "INSERT INTO %PREFIX%menu_items VALUES (4, 1, 'Clients', 'clients', '', '/admin/clients/', 'no', 4)";
$g_sql[] = "INSERT INTO %PREFIX%menu_items VALUES (5, 1, 'Modules', 'modules', '', '/admin/modules/', 'no', 5)";
$g_sql[] = "INSERT INTO %PREFIX%menu_items VALUES (6, 1, 'Themes', 'settings_themes', '', '/admin/themes/', 'no', 6)";
$g_sql[] = "INSERT INTO %PREFIX%menu_items VALUES (7, 1, 'Settings', 'settings', '', '/admin/settings', 'no', 7)";
$g_sql[] = "INSERT INTO %PREFIX%menu_items VALUES (8, 1, 'Main', 'settings_main', '', '/admin/settings/index.php?page=main', 'yes', 8)";
$g_sql[] = "INSERT INTO %PREFIX%menu_items VALUES (9, 1, 'Accounts', 'settings_accounts', '', '/admin/settings/index.php?page=accounts', 'yes', 9)";
$g_sql[] = "INSERT INTO %PREFIX%menu_items VALUES (10, 1, 'Files', 'settings_files', '', '/admin/settings/index.php?page=files', 'yes', 10)";
$g_sql[] = "INSERT INTO %PREFIX%menu_items VALUES (11, 1, 'WYSIWYG', 'settings_wysiwyg', '', '/admin/settings/index.php?page=wysiwyg', 'yes', 11)";
$g_sql[] = "INSERT INTO %PREFIX%menu_items VALUES (12, 1, 'Menus', 'settings_menus', '', '/admin/settings/index.php?page=menus', 'yes', 12)";
$g_sql[] = "INSERT INTO %PREFIX%menu_items VALUES (13, 1, 'Your Account', 'your_account', '', '/admin/account', 'no', 13)";
$g_sql[] = "INSERT INTO %PREFIX%menu_items VALUES (14, 1, 'Logout', 'logout', '', '/index.php?logout', 'no', 14)";
$g_sql[] = "INSERT INTO %PREFIX%menu_items VALUES (15, 2, 'Forms', 'client_forms', '', '/clients/index.php', 'no', 1)";
$g_sql[] = "INSERT INTO %PREFIX%menu_items VALUES (16, 2, 'Account', 'client_account', '', '/clients/account/index.php', 'no', 2)";
$g_sql[] = "INSERT INTO %PREFIX%menu_items VALUES (17, 2, 'Login Info', 'client_account_login', '', '/clients/account/index.php?page=main', 'yes', 3)";
$g_sql[] = "INSERT INTO %PREFIX%menu_items VALUES (18, 2, 'Settings', 'client_account_settings', '', '/clients/account/index.php?page=settings', 'yes', 4)";
$g_sql[] = "INSERT INTO %PREFIX%menu_items VALUES (19, 2, 'Logout', 'logout', '', '/index.php?logout', 'no', 5)";

$g_sql[] = "CREATE TABLE %PREFIX%menus (
  menu_id smallint(5) unsigned NOT NULL auto_increment,
  menu varchar(255) NOT NULL,
  menu_type enum('admin','client') NOT NULL default 'client',
  PRIMARY KEY  (menu_id)
) TYPE=MyISAM DEFAULT CHARSET=utf8";

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
) TYPE=MyISAM DEFAULT CHARSET=utf8";

$g_sql[] = "CREATE TABLE %PREFIX%modules (
  module_id mediumint(8) unsigned NOT NULL auto_increment,
  is_installed enum('yes','no') NOT NULL default 'no',
  is_enabled enum('yes','no') NOT NULL default 'no',
  origin_language varchar(50) NOT NULL,
  module_name varchar(100) NOT NULL,
  module_folder varchar(100) NOT NULL,
  version varchar(50) default NULL,
  author varchar(200) default NULL,
  author_email varchar(200) default NULL,
  author_link varchar(255) default NULL,
  description mediumtext NOT NULL,
  module_date datetime NOT NULL,
  supports_ft_versions varchar(255) default NULL,
  PRIMARY KEY  (module_id)
) TYPE=MyISAM DEFAULT CHARSET=utf8";

$g_sql[] = "CREATE TABLE %PREFIX%multi_page_form_urls (
  `form_id` mediumint(8) unsigned NOT NULL,
  `form_url` varchar(255) NOT NULL,
  `page_num` tinyint(4) NOT NULL default '2',
  PRIMARY KEY  (`form_id`,`page_num`)
) TYPE=MyISAM DEFAULT CHARSET=utf8";

$g_sql[] = "CREATE TABLE %PREFIX%public_form_omit_list (
  form_id mediumint(8) unsigned NOT NULL,
  account_id mediumint(8) unsigned NOT NULL,
  PRIMARY KEY  (form_id,account_id)
) TYPE=MyISAM DEFAULT CHARSET=utf8";

$g_sql[] = "CREATE TABLE %PREFIX%public_view_omit_list (
  view_id mediumint(8) unsigned NOT NULL,
  account_id mediumint(8) unsigned NOT NULL,
  PRIMARY KEY  (view_id,account_id)
) TYPE=MyISAM DEFAULT CHARSET=utf8";

$g_sql[] = "CREATE TABLE %PREFIX%sessions (
  session_id varchar(100) NOT NULL default '',
  session_data text NOT NULL,
  expires int(11) NOT NULL default '0',
  PRIMARY KEY  (`session_id`)
) TYPE=MyISAM DEFAULT CHARSET=latin1";

$g_sql[] = "CREATE TABLE %PREFIX%settings (
  setting_id mediumint(9) NOT NULL auto_increment,
  setting_name varchar(100) NOT NULL,
  setting_value text NOT NULL,
  module varchar(100) NOT NULL default 'core',
  PRIMARY KEY  (setting_id)
) TYPE=MyISAM DEFAULT CHARSET=utf8";

// changes per release
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('program_version', '%FORMTOOLSVERSION%', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('release_date', '%FORMTOOLSRELEASEDATE%', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('release_type', '%FORMTOOLSRELEASETYPE%', 'core')";

$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('api_version', '', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('available_languages', 'en_us,English (US)', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('clients_may_edit_date_format', 'no', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('clients_may_edit_footer_text', 'no', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('clients_may_edit_logout_url', 'yes', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('clients_may_edit_page_titles', 'no', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('clients_may_edit_sessions_timeout', 'no', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('clients_may_edit_theme', 'yes', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('clients_may_edit_timezone_offset', 'yes', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('clients_may_edit_ui_language', 'yes', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('default_client_menu_id', '2', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('default_date_format', 'M jS y, g:i A', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('default_footer_text', '', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('default_language', 'en_us', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('default_login_page', 'client_forms', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('default_logout_url', '', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('default_num_submissions_per_page', '10', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('default_page_titles', 'Form Tools - {\$page}', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('default_sessions_timeout', '30', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('default_theme', 'default', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('default_timezone_offset', '0', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('display_files_with_lightbox', 'no', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('file_upload_dir', '', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('file_upload_filetypes', 'bmp,gif,jpg,jpeg,png,avi,mp3,mp4,doc,txt,pdf,xml,csv,swf,fla,xls,tif', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('file_upload_url', '', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('file_upload_max_size', '200', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('logo_link', 'http://www.formtools.org', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('num_clients_per_page', '10', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('num_emails_per_page', '10', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('num_forms_per_page', '10', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('num_menus_per_page', '10', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('num_modules_per_page', '10', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('num_views_per_page', '10', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('num_field_option_groups_per_page', '10', 'core');";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('program_name', 'Form Tools', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('timezone_offset', '0', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('tinymce_path_info_location', 'bottom', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('tinymce_resize', 'yes', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('tinymce_show_path', 'yes', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('tinymce_toolbar', 'simple', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('tinymce_toolbar_align', 'left', 'core')";
$g_sql[] = "INSERT INTO %PREFIX%settings (setting_name, setting_value, module) VALUES ('tinymce_toolbar_location', 'top', 'core')";

$g_sql[] = "CREATE TABLE %PREFIX%themes (
  theme_id mediumint(8) unsigned NOT NULL auto_increment,
  theme_folder varchar(100) NOT NULL,
  theme_name varchar(50) NOT NULL,
  author varchar(200) default NULL,
  author_email varchar(255) default NULL,
  author_link varchar(255) default NULL,
  theme_link varchar(255) default NULL,
  description mediumtext,
  is_enabled enum('yes','no') NOT NULL default 'yes',
  theme_version varchar(50) default NULL,
  supports_ft_versions mediumtext,
  PRIMARY KEY (theme_id)
) TYPE=MyISAM DEFAULT CHARSET=utf8";

$g_sql[] = "INSERT INTO %PREFIX%themes VALUES (1, 'default', 'Default', 'Encore Web Studios', 'formtools@encorewebstudios.com', 'http://www.encorewebstudios.com', 'http://themes.formtools.org/', 'The default Form Tools theme for all new installations. It''s a green-coloured fixed-width theme requiring 1024 minimum width screens.', 'yes', '1.0.0', '2.0.0')";

$g_sql[] = "CREATE TABLE %PREFIX%view_fields (
  view_id mediumint(8) unsigned NOT NULL,
  field_id mediumint(8) unsigned NOT NULL,
  tab_number tinyint(4) default NULL,
  is_column enum('yes','no') default 'no',
  is_sortable enum('yes','no') NOT NULL default 'yes',
  is_editable enum('yes','no') NOT NULL default 'yes',
  is_searchable enum('yes','no') NOT NULL default 'yes',
  list_order smallint(5) unsigned default NULL,
  PRIMARY KEY  (view_id,field_id)
) TYPE=MyISAM DEFAULT CHARSET=utf8";

$g_sql[] = "CREATE TABLE %PREFIX%view_filters (
  filter_id mediumint(8) unsigned NOT NULL auto_increment,
  view_id mediumint(8) unsigned NOT NULL,
  filter_type enum('standard', 'client_map') NOT NULL default 'standard',
  field_id mediumint(8) unsigned NOT NULL,
  operator enum('equals','not_equals','like','not_like','before','after') NOT NULL default 'equals',
  filter_values mediumtext NOT NULL,
  filter_sql mediumtext NOT NULL,
  PRIMARY KEY  (filter_id)
) TYPE=MyISAM DEFAULT CHARSET=utf8";

$g_sql[] = "CREATE TABLE %PREFIX%view_tabs (
  view_id mediumint(8) unsigned NOT NULL,
  tab_number tinyint(3) unsigned NOT NULL,
  tab_label varchar(50) default NULL,
  PRIMARY KEY  (view_id,tab_number)
) TYPE=MyISAM DEFAULT CHARSET=utf8";

$g_sql[] = "CREATE TABLE %PREFIX%views (
  view_id smallint(6) NOT NULL auto_increment,
  form_id mediumint(8) unsigned NOT NULL,
  access_type enum('admin','public','private','hidden') NOT NULL default 'public',
  view_name varchar(100) NOT NULL,
  view_order smallint(6) NOT NULL default '1',
  num_submissions_per_page smallint(6) NOT NULL default '10',
  default_sort_field varchar(255) NOT NULL default 'submission_date',
  default_sort_field_order enum('asc','desc') NOT NULL default 'desc',
  may_add_submissions enum('yes','no') NOT NULL DEFAULT 'yes',
  may_edit_submissions enum('yes','no') NOT NULL default 'yes',
  may_delete_submissions enum('yes','no') NOT NULL default 'yes',
  has_client_map_filter enum('yes','no') NOT NULL default 'no',
  has_standard_filter enum('yes','no') NOT NULL default 'no',
  PRIMARY KEY  (view_id)
) TYPE=MyISAM DEFAULT CHARSET=utf8";
