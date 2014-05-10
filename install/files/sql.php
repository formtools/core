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
) DEFAULT CHARSET=utf8";

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
  menu_id mediumint(8) unsigned NOT NULL,
  first_name varchar(100) default NULL,
  last_name varchar(100) default NULL,
  email varchar(200) default NULL,
  username varchar(50) NOT NULL,
  password varchar(50) NOT NULL,
  temp_reset_password varchar(50) NULL,
  PRIMARY KEY (account_id)
) DEFAULT CHARSET=utf8";

$g_sql[] = "INSERT INTO %PREFIX%accounts (account_id, account_type, account_status, timezone_offset, login_page, menu_id, username, password) VALUES (1, 'admin', 'active', '0', 'admin_forms', 1, '', '')";

$g_sql[] = "CREATE TABLE %PREFIX%client_forms (
  account_id mediumint(8) unsigned NOT NULL,
  form_id mediumint(8) unsigned NOT NULL,
  PRIMARY KEY (account_id,form_id)
) DEFAULT CHARSET=utf8";

$g_sql[] = "CREATE TABLE %PREFIX%client_views (
  account_id mediumint(8) unsigned NOT NULL,
  view_id mediumint(8) unsigned NOT NULL,
  PRIMARY KEY (account_id,view_id)
) DEFAULT CHARSET=utf8";

$g_sql[] = "CREATE TABLE %PREFIX%email_template_edit_submission_views (
  email_id mediumint(8) unsigned NOT NULL,
  view_id mediumint(8) unsigned NOT NULL,
  PRIMARY KEY  (email_id,view_id)
) DEFAULT CHARSET=utf8";

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
) DEFAULT CHARSET=utf8";

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
) DEFAULT CHARSET=utf8";

$g_sql[] = "CREATE TABLE %PREFIX%field_options (
  list_id mediumint(8) unsigned NOT NULL,
  list_group_id mediumint(9) NOT NULL,
  option_order smallint(4) NOT NULL,
  option_value varchar(255) NOT NULL,
  option_name varchar(255) NOT NULL,
  is_new_sort_group enum('yes', 'no') NOT NULL,
  PRIMARY KEY (list_id, list_group_id, option_order)
) DEFAULT CHARSET=utf8";

$g_sql[] = "CREATE TABLE %PREFIX%field_settings (
  field_id mediumint(8) unsigned NOT NULL,
  setting_id mediumint(9) NOT NULL,
  setting_value mediumtext,
  PRIMARY KEY (field_id,setting_id)
) DEFAULT CHARSET=utf8";

$g_sql[] = "CREATE TABLE %PREFIX%field_type_setting_options (
  setting_id mediumint(9) NOT NULL,
  option_text varchar(255) default NULL,
  option_value varchar(255) default NULL,
  option_order smallint(6) NOT NULL,
  is_new_sort_group enum('yes','no') NOT NULL,
  PRIMARY KEY  (setting_id,option_order)
) DEFAULT CHARSET=utf8";

// textbox - size
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (1, 'Tiny', 'cf_size_tiny', 1, 'yes')";
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (1, 'Small', 'cf_size_small', 2, 'yes')";
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (1, 'Medium', 'cf_size_medium', 3, 'yes')";
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (1, 'Large', 'cf_size_large', 4, 'yes')";
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (1, 'Full Width', 'cf_size_full_width', 5, 'yes')";

// textbox - highlight
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (3, 'Orange', 'cf_colour_orange', 3, 'yes')";
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (3, 'Yellow', 'cf_colour_yellow', 4, 'yes')";
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (3, 'Red', 'cf_colour_red', 2, 'yes')";
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (3, 'None', '', 1, 'yes')";
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (3, 'Green', 'cf_colour_green', 5, 'yes')";
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (3, 'Blue', 'cf_colour_blue', 6, 'yes')";

// textarea - height
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (5, 'Tiny (30px)', 'cf_size_tiny', 1, 'yes')";
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (5, 'Small (80px)', 'cf_size_small', 2, 'yes')";
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (5, 'Medium (150px)', 'cf_size_medium', 3, 'yes')";
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (5, 'Large (300px)', 'cf_size_large', 4, 'yes')";

// textarea - highlight
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (6, 'None', '', 1, 'yes')";
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (6, 'Red', 'cf_colour_red', 2, 'yes')";
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (6, 'Orange', 'cf_colour_orange', 3, 'yes')";
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (6, 'Yellow', 'cf_colour_yellow', 4, 'yes')";
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (6, 'Green', 'cf_colour_green', 5, 'yes')";
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (6, 'Blue', 'cf_colour_blue', 6, 'yes')";

// textarea - input length
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (7, 'No Limit', '', 1, 'yes')";
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (7, 'Words', 'words', 2, 'yes')";
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (7, 'Characters', 'chars', 3, 'yes')";

// radios
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (17, 'Horizontal', 'horizontal', 1, 'yes')";
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (17, 'Vertical', 'vertical', 2, 'yes')";
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (17, '2 Columns', 'cf_option_list_2cols', 3, 'yes')";
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (17, '3 Columns', 'cf_option_list_3cols', 4, 'yes')";
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (17, '4 Columns', 'cf_option_list_4cols', 5, 'yes')";

// checkboxes
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (20, 'Horizontal', 'horizontal', 1, 'yes')";
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (20, 'Vertical', 'vertical', 2, 'yes')";
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (20, '2 Columns', 'cf_option_list_2cols', 3, 'yes')";
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (20, '3 Columns', 'cf_option_list_3cols', 4, 'yes')";
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (20, '4 Columns', 'cf_option_list_4cols', 5, 'yes')";

// date
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (22, '2011-11-30', 'yy-mm-dd', 1, 'yes')";
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (22, '30/11/2011 (dd/mm/yyyy)', 'dd/mm/yy', 2, 'yes')";
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (22, '11/30/2011 (mm/dd/yyyy)', 'mm/dd/yy', 3, 'yes')";
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (22, 'Nov 30, 2011', 'M d, yy', 4, 'yes')";
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (22, 'November 30, 2011', 'MM d, yy', 5, 'yes')";
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (22, 'Wed Nov 30, 2011 ', 'D M d, yy', 6, 'yes')";
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (22, 'Wednesday, November 30, 2011', 'DD, MM d, yy', 7, 'yes')";
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (22, '30/11/2011 8:00 PM', 'datetime:dd/mm/yy|h:mm TT|ampm`true', 8, 'yes')";
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (22, '11/30/2011 8:00 PM', 'datetime:mm/dd/yy|h:mm TT|ampm`true', 9, 'yes')";
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (22, '2011-11-30 8:00 PM', 'datetime:yy-mm-dd|h:mm TT|ampm`true', 10, 'yes')";
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (22, '2011-11-30 20:00', 'datetime:yy-mm-dd|hh:mm', 11, 'yes')";
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (22, '2011-11-30 20:00:00', 'datetime:yy-mm-dd|hh:mm:ss|showSecond`true', 12, 'yes')";
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (23, 'Yes', 'yes', 1, 'yes')";
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (23, 'No', 'no', 2, 'yes')";

// time
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (25, '8:00 AM', 'h:mm TT|ampm`true', 1, 'yes')";
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (25, '16:00', 'hh:mm|ampm`false', 2, 'yes')";
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (25, '16:00:00', 'hh:mm:ss|showSecond`true|ampm`false', 3, 'yes')";

// code / markup
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (29, 'CSS', 'CSS', 1, 'yes')";
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (29, 'HTML', 'HTML', 2, 'yes')";
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (29, 'JavaScript', 'JavaScript', 3, 'yes')";
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (29, 'XML', 'XML', 4, 'yes')";
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (30, 'Tiny (50px)', '50', 1, 'yes')";
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (30, 'Small (100px)', '100', 2, 'yes')";
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (30, 'Medium (200px)', '200', 3, 'yes')";
$g_sql[] = "INSERT INTO %PREFIX%field_type_setting_options VALUES (30, 'Large (400px)', '400', 4, 'yes')";

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
) DEFAULT CHARSET=utf8";

// textbox
$g_sql[] = "INSERT INTO %PREFIX%field_type_settings VALUES (1, 1, 'Size', 'size', 'select', 'na', 'static', 'cf_size_medium', 1)";
$g_sql[] = "INSERT INTO %PREFIX%field_type_settings VALUES (2, 1, 'Max Length', 'maxlength', 'textbox', 'na', 'static', '', 2)";
$g_sql[] = "INSERT INTO %PREFIX%field_type_settings VALUES (3, 1, 'Highlight', 'highlight', 'select', 'na', 'static', '', 4)";
$g_sql[] = "INSERT INTO %PREFIX%field_type_settings VALUES (4, 1, 'Field Comments', 'comments', 'textarea', 'na', 'static', '', 3)";

// textarea
$g_sql[] = "INSERT INTO %PREFIX%field_type_settings VALUES (5, 2, 'Height', 'height', 'select', 'na', 'static', 'cf_size_small', 1)";
$g_sql[] = "INSERT INTO %PREFIX%field_type_settings VALUES (6, 2, 'Highlight Colour', 'highlight_colour', 'select', 'na', 'static', '', 3)";
$g_sql[] = "INSERT INTO %PREFIX%field_type_settings VALUES (7, 2, 'Input length', 'input_length', 'radios', 'horizontal', 'static', '', 4)";
$g_sql[] = "INSERT INTO %PREFIX%field_type_settings VALUES (8, 2, '- Max length (words/chars)', 'maxlength', 'textbox', 'na', 'static', '', 5)";
$g_sql[] = "INSERT INTO %PREFIX%field_type_settings VALUES (9, 2, 'Field Comments', 'comments', 'textarea', 'na', 'static', '', 2)";

// password
$g_sql[] = "INSERT INTO %PREFIX%field_type_settings VALUES (10, 3, 'Field Comments', 'comments', 'textarea', 'na', 'static', '', 1)";

// dropdown
$g_sql[] = "INSERT INTO %PREFIX%field_type_settings VALUES (11, 4, 'Option List / Contents', 'contents', 'option_list_or_form_field', 'na', 'static', '', 1)";
$g_sql[] = "INSERT INTO %PREFIX%field_type_settings VALUES (12, 4, 'Field Comments', 'comments', 'textarea', 'na', 'static', '', 2)";

// multi-select dropdown
$g_sql[] = "INSERT INTO %PREFIX%field_type_settings VALUES (13, 5, 'Option List / Contents', 'contents', 'option_list_or_form_field', 'na', 'static', '', 1)";
$g_sql[] = "INSERT INTO %PREFIX%field_type_settings VALUES (14, 5, 'Num Rows', 'num_rows', 'textbox', 'na', 'static', '5', 2)";
$g_sql[] = "INSERT INTO %PREFIX%field_type_settings VALUES (15, 5, 'Field Comments', 'comments', 'textarea', 'na', 'static', '', 3)";

// radios
$g_sql[] = "INSERT INTO %PREFIX%field_type_settings VALUES (16, 6, 'Option List / Contents', 'contents', 'option_list_or_form_field', 'na', 'static', '', 1)";
$g_sql[] = "INSERT INTO %PREFIX%field_type_settings VALUES (17, 6, 'Formatting', 'formatting', 'select', 'na', 'static', 'horizontal', 2)";
$g_sql[] = "INSERT INTO %PREFIX%field_type_settings VALUES (18, 6, 'Field Comments', 'comments', 'textarea', 'na', 'static', '', 3)";

// checkboxes
$g_sql[] = "INSERT INTO %PREFIX%field_type_settings VALUES (19, 7, 'Option List / Contents', 'contents', 'option_list_or_form_field', 'na', 'static', '', 1)";
$g_sql[] = "INSERT INTO %PREFIX%field_type_settings VALUES (20, 7, 'Formatting', 'formatting', 'select', 'na', 'static', 'horizontal', 2)";
$g_sql[] = "INSERT INTO %PREFIX%field_type_settings VALUES (21, 7, 'Field Comments', 'comments', 'textarea', 'na', 'static', '', 3)";

// date
$g_sql[] = "INSERT INTO %PREFIX%field_type_settings VALUES (22, 8, 'Custom Display Format', 'display_format', 'select', 'na', 'static', 'yy-mm-dd', 1)";
$g_sql[] = "INSERT INTO %PREFIX%field_type_settings VALUES (23, 8, 'Apply Timezone Offset', 'apply_timezone_offset', 'radios', 'horizontal', 'static', 'no', 2)";
$g_sql[] = "INSERT INTO %PREFIX%field_type_settings VALUES (24, 8, 'Field Comments', 'comments', 'textarea', 'na', 'static', '', 3)";

// time
$g_sql[] = "INSERT INTO %PREFIX%field_type_settings VALUES (25, 9, 'Custom Display Format', 'display_format', 'select', 'na', 'static', 'h:mm TT|ampm`true', 1)";
$g_sql[] = "INSERT INTO %PREFIX%field_type_settings VALUES (26, 9, 'Field Comments', 'comments', 'textarea', 'na', 'static', '', 2)";

// phone number
$g_sql[] = "INSERT INTO %PREFIX%field_type_settings VALUES (27, 10, 'Phone Number Format', 'phone_number_format', 'textbox', 'na', 'static', '(xxx) xxx-xxxx', 1)";
$g_sql[] = "INSERT INTO %PREFIX%field_type_settings VALUES (28, 10, 'Field Comments', 'comments', 'textarea', 'na', 'static', '', 2)";

// code / markup
$g_sql[] = "INSERT INTO %PREFIX%field_type_settings VALUES (29, 11, 'Code / Markup Type', 'code_markup', 'select', 'na', 'static', 'HTML', 1)";
$g_sql[] = "INSERT INTO %PREFIX%field_type_settings VALUES (30, 11, 'Height', 'height', 'select', 'na', 'static', '200', 2)";
$g_sql[] = "INSERT INTO %PREFIX%field_type_settings VALUES (31, 11, 'Field Comments', 'comments', 'textarea', 'na', 'static', '', 3)";

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
) DEFAULT CHARSET=utf8";

$g_sql[] = "INSERT INTO %PREFIX%field_types VALUES (1, 'no', '{\$LANG.text_non_deletable_fields}', NULL, '{\$LANG.word_textbox}', 'textbox', 1, 'no', 'no', 'textbox', NULL, 1, '1char,2chars,tiny,small,medium,large,very_large', 'smarty', 'core', '', '{\$VALUE|htmlspecialchars}', '<input type=\"text\" name=\"{\$NAME}\" value=\"{\$VALUE|escape}\" \r\n  class=\"{\$size}{if \$highlight} {\$highlight}{/if}\" \r\n  {if \$maxlength}maxlength=\"{\$maxlength}\"{/if} />\r\n \r\n{if \$comments}\r\n  <div class=\"cf_field_comments\">{\$comments}</div>\r\n{/if}\r\n', '\r\n', 'input.cf_size_tiny {\r\n  width: 30px; \r\n}\r\ninput.cf_size_small {\r\n  width: 80px; \r\n}\r\ninput.cf_size_medium {\r\n  width: 150px; \r\n}\r\ninput.cf_size_large {\r\n  width: 250px;\r\n}\r\ninput.cf_size_full_width {\r\n  width: 99%; \r\n}\r\n\r\n', '')";
$g_sql[] = "INSERT INTO %PREFIX%field_types VALUES (2, 'yes', NULL, NULL, '{\$LANG.word_textarea}', 'textarea', 1, 'no', 'no', 'textarea', NULL, 2, 'medium,large,very_large', 'smarty', 'core', '', '{if \$CONTEXTPAGE == \"edit_submission\"}  \r\n  {\$VALUE|nl2br}\r\n{else}\r\n  {\$VALUE}\r\n{/if}', '{* figure out all the classes *}\r\n{assign var=classes value=\$height}\r\n{if \$highlight_colour}\r\n  {assign var=classes value=\"`\$classes` `\$highlight_colour`\"}\r\n{/if}\r\n{if \$input_length == \"words\" && \$maxlength != \"\"}\r\n  {assign var=classes value=\"`\$classes` cf_wordcounter max`\$maxlength`\"}\r\n{elseif \$input_length == \"chars\" && \$maxlength != \"\"}\r\n  {assign var=classes value=\"`\$classes` cf_textcounter max`\$maxlength`\"}\r\n{/if}\r\n\r\n<textarea name=\"{\$NAME}\" id=\"{\$NAME}_id\" class=\"{\$classes}\">{\$VALUE}</textarea>\r\n\r\n{if \$input_length == \"words\" && \$maxlength != \"\"}\r\n  <div class=\"cf_counter\" id=\"{\$NAME}_counter\">\r\n    {\$maxlength} {\$LANG.phrase_word_limit_p} <span></span> {\$LANG.phrase_remaining_words}\r\n  </div>\r\n{elseif \$input_length == \"chars\" && \$maxlength != \"\"}\r\n  <div class=\"cf_counter\" id=\"{\$NAME}_counter\">\r\n    {\$maxlength} {\$LANG.phrase_characters_limit_p} <span></span> {\$LANG.phrase_remaining_characters}\r\n  </div>\r\n{/if}\r\n\r\n{if \$comments}\r\n  <div class=\"cf_field_comments\">{\$comments|nl2br}</div>\r\n{/if}', '', '.cf_counter span {\r\n  font-weight: bold; \r\n}\r\ntextarea {\r\n  width: 99%;\r\n}\r\ntextarea.cf_size_tiny {\r\n  height: 30px;\r\n}\r\ntextarea.cf_size_small {\r\n  height: 80px;  \r\n}\r\ntextarea.cf_size_medium {\r\n  height: 150px;  \r\n}\r\ntextarea.cf_size_large {\r\n  height: 300px;\r\n}\r\n', '/**\r\n * The following code provides a simple text/word counter option for any  \r\n * textarea. It either just keeps counting up, or limits the results to a\r\n * certain number - all depending on what the user has selected via the\r\n * field type settings.\r\n */\r\nvar cf_counter = {};\r\ncf_counter.get_max_count = function(el) {\r\n  var classes = \$(el).attr(''class'').split(\" \").slice(-1);\r\n  var max = null;\r\n  for (var i=0; i<classes.length; i++) {\r\n    var result = classes[i].match(/max(\\\\d+)/);\r\n    if (result != null) {\r\n      max = result[1];\r\n      break;\r\n    }\r\n  }\r\n  return max;\r\n}\r\n\r\n\$(function() {\r\n  \$(\"textarea[class~=''cf_wordcounter'']\").each(function() {\r\n    var max = cf_counter.get_max_count(this);\r\n    if (max == null) {\r\n      return;\r\n    }\r\n    \$(this).bind(\"keydown\", function() {\r\n      var val = \$(this).val();\r\n      var len        = val.split(/[\\\\s]+/);\r\n      var field_name = \$(this).attr(\"name\");\r\n      var num_words  = len.length - 1;\r\n      if (num_words > max) {\r\n        var allowed_words = val.split(/[\\\\s]+/, max);\r\n        truncated_str = allowed_words.join(\" \");\r\n        \$(this).val(truncated_str);\r\n      } else {\r\n        \$(\"#\" + field_name + \"_counter\").find(\"span\").html(parseInt(max) - parseInt(num_words));\r\n      }\r\n    });     \r\n    \$(this).trigger(\"keydown\");\r\n  });\r\n\r\n  \$(\"textarea[class~=''cf_textcounter'']\").each(function() {\r\n    var max = cf_counter.get_max_count(this);\r\n    if (max == null) {\r\n      return;\r\n    }\r\n    \$(this).bind(\"keydown\", function() {    \r\n      var field_name = \$(this).attr(\"name\");      \r\n      if (this.value.length > max) {\r\n        this.value = this.value.substring(0, max);\r\n      } else {\r\n        \$(\"#\" + field_name + \"_counter\").find(\"span\").html(max - this.value.length);\r\n      }\r\n    });\r\n    \$(this).trigger(\"keydown\");\r\n  }); \r\n});')";
$g_sql[] = "INSERT INTO %PREFIX%field_types VALUES (3, 'yes', NULL, NULL, '{\$LANG.word_password}', 'password', 1, 'no', 'no', 'password', NULL, 3, '1char,2chars,tiny,small,medium', 'none', 'core', '', '\r\n', '<input type=\"password\" name=\"{\$NAME}\" value=\"{\$VALUE|escape}\" \r\n  class=\"cf_password\" />\r\n \r\n{if \$comments}\r\n  <div class=\"cf_field_comments\">{\$comments}</div>\r\n{/if}\r\n', '', 'input.cf_password {\r\n  width: 120px;\r\n}\r\n', '')";
$g_sql[] = "INSERT INTO %PREFIX%field_types VALUES (4, 'yes', NULL, NULL, '{\$LANG.word_dropdown}', 'dropdown', 1, 'no', 'no', 'select', 11, 6, '1char,2chars,tiny,small,medium,large', 'php', 'core', 'ft_display_field_type_dropdown', '{strip}{if \$contents != \"\"}\r\n  {foreach from=\$contents.options item=curr_group_info name=group}\r\n    {assign var=options value=\$curr_group_info.options}\r\n    {foreach from=\$options item=option name=row}\r\n      {if \$VALUE == \$option.option_value}{\$option.option_name}{/if}\r\n    {/foreach}\r\n  {/foreach}\r\n{/if}{/strip}', '{if \$contents == \"\"}\r\n  <div class=\"cf_field_comments\">\r\n    {\$LANG.phrase_not_assigned_to_option_list} \r\n  </div>\r\n{else}\r\n  <select name=\"{\$NAME}\">\r\n  {foreach from=\$contents.options item=curr_group_info name=group}\r\n    {assign var=group_info value=\$curr_group_info.group_info}\r\n    {assign var=options value=\$curr_group_info.options}\r\n    {if \$group_info.group_name}\r\n      <optgroup label=\"{\$group_info.group_name|escape}\">\r\n    {/if}\r\n    {foreach from=\$options item=option name=row}\r\n      <option value=\"{\$option.option_value}\"\r\n        {if \$VALUE == \$option.option_value}selected{/if}>{\$option.option_name}</option>\r\n    {/foreach}\r\n    {if \$group_info.group_name}\r\n      </optgroup>\r\n    {/if}\r\n  {/foreach}\r\n  </select>\r\n{/if}\r\n\r\n{if \$comments}\r\n  <div class=\"cf_field_comments\">{\$comments}</div>\r\n{/if}\r\n\r\n', '', '', '')";
$g_sql[] = "INSERT INTO %PREFIX%field_types VALUES (5, 'yes', NULL, NULL, '{\$LANG.phrase_multi_select_dropdown}', 'multi_select_dropdown', 1, 'no', 'no', 'multi-select', 13, 7, '1char,2chars,tiny,small,medium,large', 'php', 'core', 'ft_display_field_type_multi_select_dropdown', '{if \$contents != \"\"}\r\n  {assign var=vals value=\"`\$g_multi_val_delimiter`\"|explode:\$VALUE}\r\n  {assign var=is_first value=true}\r\n  {strip}\r\n  {foreach from=\$contents.options item=curr_group_info name=group}\r\n    {assign var=options value=\$curr_group_info.options}\r\n    {foreach from=\$options item=option name=row}\r\n      {if \$option.option_value|in_array:\$vals}\r\n        {if \$is_first == false}, {/if}\r\n        {\$option.option_name}\r\n        {assign var=is_first value=false}\r\n      {/if}\r\n    {/foreach}\r\n  {/foreach}\r\n  {/strip}\r\n{/if}', '{if \$contents == \"\"}\r\n  <div class=\"cf_field_comments\">{\$LANG.phrase_not_assigned_to_option_list}</div>\r\n{else}\r\n  {assign var=vals value=\"`\$g_multi_val_delimiter`\"|explode:\$VALUE}\r\n  <select name=\"{\$NAME}[]\" multiple size=\"{if \$num_rows}{\$num_rows}{else}5{/if}\">\r\n  {foreach from=\$contents.options item=curr_group_info name=group}\r\n    {assign var=group_info value=\$curr_group_info.group_info}\r\n    {assign var=options value=\$curr_group_info.options}\r\n    {if \$group_info.group_name}\r\n      <optgroup label=\"{\$group_info.group_name|escape}\">\r\n    {/if}\r\n    {foreach from=\$options item=option name=row}\r\n      <option value=\"{\$option.option_value}\"\r\n        {if \$option.option_value|in_array:\$vals}selected{/if}>{\$option.option_name}</option>\r\n    {/foreach}\r\n    {if \$group_info.group_name}\r\n      </optgroup>\r\n    {/if}\r\n  {/foreach}\r\n  </select>\r\n{/if}\r\n\r\n{if \$comments}\r\n  <div class=\"cf_field_comments\">{\$comments}</div>\r\n{/if}\r\n', '', '', '')";
$g_sql[] = "INSERT INTO %PREFIX%field_types VALUES (6, 'yes', NULL, NULL, '{\$LANG.phrase_radio_buttons}', 'radio_buttons', 1, 'no', 'no', 'radio-buttons', 16, 4, '1char,2chars,tiny,small,medium,large', 'php', 'core', 'ft_display_field_type_radios', '{strip}{if \$contents != \"\"}\r\n  {foreach from=\$contents.options item=curr_group_info name=group}\r\n    {assign var=options value=\$curr_group_info.options}\r\n    {foreach from=\$options item=option name=row}\r\n      {if \$VALUE == \$option.option_value}{\$option.option_name}{/if}\r\n    {/foreach}\r\n  {/foreach}\r\n{/if}{/strip}', '{if \$contents == \"\"}\r\n  <div class=\"cf_field_comments\">{\$LANG.phrase_not_assigned_to_option_list}</div>\r\n{else}\r\n  {assign var=is_in_columns value=false}\r\n  {if \$formatting == \"cf_option_list_2cols\" || \r\n      \$formatting == \"cf_option_list_3cols\" || \r\n      \$formatting == \"cf_option_list_4cols\"}\r\n    {assign var=is_in_columns value=true}\r\n  {/if}\r\n\r\n  {assign var=counter value=\"1\"}\r\n  {foreach from=\$contents.options item=curr_group_info name=group}\r\n    {assign var=group_info value=\$curr_group_info.group_info}\r\n    {assign var=options value=\$curr_group_info.options}\r\n\r\n    {if \$group_info.group_name}\r\n      <div class=\"cf_option_list_group_label\">{\$group_info.group_name}</div>\r\n    {/if}\r\n\r\n    {if \$is_in_columns}<div class=\"{\$formatting}\">{/if}\r\n\r\n    {foreach from=\$options item=option name=row}\r\n      {if \$is_in_columns}<div class=\"column\">{/if}\r\n        <input type=\"radio\" name=\"{\$NAME}\" id=\"{\$NAME}_{\$counter}\" \r\n          value=\"{\$option.option_value}\"\r\n          {if \$VALUE == \$option.option_value}checked{/if} />\r\n          <label for=\"{\$NAME}_{\$counter}\">{\$option.option_name}</label>\r\n      {if \$is_in_columns}</div>{/if}\r\n      {if \$formatting == \"vertical\"}<br />{/if}\r\n      {assign var=counter value=\$counter+1}\r\n    {/foreach}\r\n\r\n    {if \$is_in_columns}</div>{/if}\r\n  {/foreach}\r\n\r\n  {if \$comments}<div class=\"cf_field_comments\">{\$comments}</div>{/if}\r\n{/if}', '', '/* All CSS styles for this field type are found in Shared Resources */\r\n', '')";
$g_sql[] = "INSERT INTO %PREFIX%field_types VALUES (7, 'yes', NULL, NULL, '{\$LANG.word_checkboxes}', 'checkboxes', 1, 'no', 'no', 'checkboxes', 19, 5, '1char,2chars,tiny,small,medium,large', 'php', 'core', 'ft_display_field_type_checkboxes', '{strip}{if \$contents != \"\"}\r\n  {assign var=vals value=\"`\$g_multi_val_delimiter`\"|explode:\$VALUE}\r\n  {assign var=is_first value=true}\r\n  {strip}\r\n  {foreach from=\$contents.options item=curr_group_info name=group}\r\n    {assign var=options value=\$curr_group_info.options}\r\n    {foreach from=\$options item=option name=row}\r\n      {if \$option.option_value|in_array:\$vals}\r\n        {if \$is_first == false}, {/if}\r\n        {\$option.option_name}\r\n        {assign var=is_first value=false}\r\n      {/if}\r\n    {/foreach}\r\n  {/foreach}\r\n  {/strip}\r\n{/if}{/strip}', '{if \$contents == \"\"}\r\n  <div class=\"cf_field_comments\">{\$LANG.phrase_not_assigned_to_option_list}</div>\r\n{else}\r\n  {assign var=vals value=\"`\$g_multi_val_delimiter`\"|explode:\$VALUE}\r\n  {assign var=is_in_columns value=false}\r\n  {if \$formatting == \"cf_option_list_2cols\" || \r\n      \$formatting == \"cf_option_list_3cols\" || \r\n      \$formatting == \"cf_option_list_4cols\"}\r\n    {assign var=is_in_columns value=true}\r\n  {/if}\r\n\r\n  {assign var=counter value=\"1\"}\r\n  {foreach from=\$contents.options item=curr_group_info name=group}\r\n    {assign var=group_info value=\$curr_group_info.group_info}\r\n    {assign var=options value=\$curr_group_info.options}\r\n\r\n    {if \$group_info.group_name}\r\n      <div class=\"cf_option_list_group_label\">{\$group_info.group_name}</div>\r\n    {/if}\r\n\r\n    {if \$is_in_columns}<div class=\"{\$formatting}\">{/if}\r\n\r\n    {foreach from=\$options item=option name=row}\r\n      {if \$is_in_columns}<div class=\"column\">{/if}\r\n        <input type=\"checkbox\" name=\"{\$NAME}[]\" id=\"{\$NAME}_{\$counter}\" \r\n          value=\"{\$option.option_value|escape}\" \r\n          {if \$option.option_value|in_array:\$vals}checked{/if} />\r\n          <label for=\"{\$NAME}_{\$counter}\">{\$option.option_name}</label>\r\n      {if \$is_in_columns}</div>{/if}\r\n      {if \$formatting == \"vertical\"}<br />{/if}\r\n      {assign var=counter value=\$counter+1}\r\n    {/foreach}\r\n\r\n    {if \$is_in_columns}</div>{/if}\r\n  {/foreach}\r\n\r\n  {if {\$comments}\r\n    <div class=\"cf_field_comments\">{\$comments}</div> \r\n  {/if}\r\n{/if}', '', '/* all CSS is found in Shared Resources */\r\n', '')";
$g_sql[] = "INSERT INTO %PREFIX%field_types VALUES (8, 'no', '{\$LANG.text_non_deletable_fields}', NULL, '{\$LANG.word_date}', 'date', 2, 'no', 'yes', '', NULL, 1, 'small', 'php', 'core', 'ft_display_field_type_date', '{strip}\r\n  {if \$VALUE}\r\n    {assign var=tzo value=\"\"}\r\n    {if \$apply_timezone_offset == \"yes\"}\r\n      {assign var=tzo value=\$ACCOUNT_INFO.timezone_offset}\r\n    {/if}\r\n    {if \$display_format == \"yy-mm-dd\" || !\$display_format}\r\n      {\$VALUE|custom_format_date:\$tzo:\"Y-m-d\"}\r\n    {elseif \$display_format == \"dd/mm/yy\"}\r\n      {\$VALUE|custom_format_date:\$tzo:\"d/m/Y\"}\r\n    {elseif \$display_format == \"mm/dd/yy\"}\r\n      {\$VALUE|custom_format_date:\$tzo:\"m/d/Y\"}\r\n    {elseif \$display_format == \"M d, yy\"}\r\n      {\$VALUE|custom_format_date:\$tzo:\"M j, Y\"}\r\n    {elseif \$display_format == \"MM d, yy\"}\r\n      {\$VALUE|custom_format_date:\$tzo:\"F j, Y\"}\r\n    {elseif \$display_format == \"D M d, yy\"}\r\n      {\$VALUE|custom_format_date:\$tzo:\"D M j, Y\"}\r\n    {elseif \$display_format == \"DD, MM d, yy\"}\r\n      {\$VALUE|custom_format_date:\$tzo:\"l M j, Y\"}\r\n    {elseif \$display_format == \"datetime:dd/mm/yy|h:mm TT|ampm`true\"}\r\n      {\$VALUE|custom_format_date:\$tzo:\"d/m/Y g:i A\"}\r\n    {elseif \$display_format == \"datetime:mm/dd/yy|h:mm TT|ampm`true\"}\r\n      {\$VALUE|custom_format_date:\$tzo:\"m/d/Y g:i A\"}\r\n    {elseif \$display_format == \"datetime:yy-mm-dd|h:mm TT|ampm`true\"}\r\n      {\$VALUE|custom_format_date:\$tzo:\"Y-m-d g:i A\"}\r\n    {elseif \$display_format == \"datetime:yy-mm-dd|hh:mm\"}\r\n      {\$VALUE|custom_format_date:\$tzo:\"Y-m-d H:i\"}\r\n    {elseif \$display_format == \"datetime:yy-mm-dd|hh:mm:ss|showSecond`true\"}\r\n      {\$VALUE|custom_format_date:\$tzo:\"Y-m-d H:i:s\"}\r\n    {/if}\r\n{/if}{/strip}', '{assign var=class value=\"cf_datepicker\"}\r\n{if \$display_format|strpos:\"datetime\" === 0}\r\n  {assign var=class value=\"cf_datetimepicker\"}\r\n{/if}\r\n\r\n{assign var=\"val\" value=\"\"}\r\n{if \$VALUE}\r\n  {assign var=tzo value=\"\"}\r\n  {if \$apply_timezone_offset == \"yes\"}\r\n    {assign var=tzo value=\$ACCOUNT_INFO.timezone_offset}\r\n  {/if}\r\n  {if \$display_format == \"yy-mm-dd\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"Y-m-d\"}\r\n  {elseif \$display_format == \"dd/mm/yy\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"d/m/Y\"}\r\n  {elseif \$display_format == \"mm/dd/yy\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"m/d/Y\"}\r\n  {elseif \$display_format == \"M d, yy\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"M j, Y\"}\r\n  {elseif \$display_format == \"MM d, yy\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"F j, Y\"}\r\n  {elseif \$display_format == \"D M d, yy\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"D M j, Y\"}\r\n  {elseif \$display_format == \"DD, MM d, yy\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"l M j, Y\"}\r\n  {elseif \$display_format == \"datetime:dd/mm/yy|h:mm TT|ampm`true\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"d/m/Y g:i A\"}\r\n  {elseif \$display_format == \"datetime:mm/dd/yy|h:mm TT|ampm`true\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"m/d/Y g:i A\"}\r\n  {elseif \$display_format == \"datetime:yy-mm-dd|h:mm TT|ampm`true\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"Y-m-d g:i A\"}\r\n  {elseif \$display_format == \"datetime:yy-mm-dd|hh:mm\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"Y-m-d H:i\"}\r\n  {elseif \$display_format == \"datetime:yy-mm-dd|hh:mm:ss|showSecond`true\"}\r\n    {assign var=val value=\$VALUE|custom_format_date:\$tzo:\"Y-m-d H:i:s\"}\r\n  {/if}\r\n{/if}\r\n\r\n<div class=\"cf_date_group\">\r\n  <input type=\"input\" name=\"{\$NAME}\" id=\"{\$NAME}_id\" \r\n    class=\"cf_datefield {\$class}\" value=\"{\$val}\" /><img class=\"ui-datepicker-trigger\" src=\"{\$g_root_url}/global/images/calendar.png\" id=\"{\$NAME}_icon_id\" />\r\n  <input type=\"hidden\" id=\"{\$NAME}_format\" value=\"{\$display_format}\" />\r\n  {if \$comments}\r\n    <div class=\"cf_field_comments\">{\$comments}</div>\r\n  {/if}\r\n</div>', '\$field_name     = \$vars[\"field_info\"][\"field_name\"];\r\n\$date           = \$vars[\"data\"][\$field_name];\r\n\$display_format = \$vars[\"settings\"][\"display_format\"];\r\n\$atzo           = \$vars[\"settings\"][\"apply_timezone_offset\"];\r\n\$account_info   = isset(\$vars[\"account_info\"]) ? \$vars[\"account_info\"] : array();\r\n\r\nif (empty(\$date))\r\n{\r\n  \$value = \"\";\r\n}\r\nelse\r\n{\r\n  if (strpos(\$display_format, \"datetime:\") === 0)\r\n  {\r\n    \$parts = explode(\" \", \$date);\r\n    switch (\$display_format)\r\n    {\r\n      case \"datetime:dd/mm/yy|h:mm TT|ampm`true\":\r\n        \$date = substr(\$date, 3, 2) . \"/\" . substr(\$date, 0, 2) . \"/\" . \r\n          substr(\$date, 6);        \r\n        break;\r\n    }\r\n  }\r\n  else\r\n  {\r\n    if (\$display_format == \"dd/mm/yy\")\r\n    {\r\n      \$date = substr(\$date, 3, 2) . \"/\" . substr(\$date, 0, 2) . \"/\" . \r\n        substr(\$date, 6);\r\n    }\r\n  }\r\n  \$time = strtotime(\$date);\r\n  \r\n  // lastly, if this field has a timezone offset being applied to it, do the\r\n  // appropriate math on the date\r\n  if (\$atzo == \"yes\" && !isset(\$account_info[\"timezone_offset\"]))\r\n  {\r\n    \$seconds_offset = \$account_info[\"timezone_offset\"] * 60 * 60;\r\n    \$time += \$seconds_offset;\r\n  }\r\n\r\n  \$value = date(\"Y-m-d H:i:s\", \$time);\r\n}\r\n\r\n\r\n', '.cf_datepicker {\r\n  width: 160px; \r\n}\r\n.cf_datetimepicker {\r\n  width: 160px; \r\n}\r\n.ui-datepicker-trigger {\r\n  cursor: pointer; \r\n}\r\n', '\$(function() {\r\n  // the datetimepicker has a bug that prevents the icon from appearing. So\r\n  // instead, we add the image manually into the page and assign the open event\r\n  // handler to the image\r\n  var default_settings = {\r\n    changeYear: true,\r\n    changeMonth: true   \r\n  }\r\n\r\n  \$(\".cf_datepicker\").each(function() {\r\n    var field_name = \$(this).attr(\"name\");\r\n    var settings = default_settings;\r\n    if (\$(\"#\" + field_name + \"_id\").length) {\r\n      settings.dateFormat = \$(\"#\" + field_name + \"_format\").val();\r\n    }\r\n    \$(this).datepicker(settings);\r\n    \$(\"#\" + field_name + \"_icon_id\").bind(\"click\",\r\n      { field_id: \"#\" + field_name + \"_id\" }, function(e) {      \r\n      \$.datepicker._showDatepicker(\$(e.data.field_id)[0]);\r\n    });\r\n  });\r\n    \r\n  \$(\".cf_datetimepicker\").each(function() {\r\n    var field_name = \$(this).attr(\"name\");\r\n    var settings = default_settings;\r\n    if (\$(\"#\" + field_name + \"_id\").length) {\r\n      var settings_str = \$(\"#\" + field_name + \"_format\").val();\r\n      settings_str = settings_str.replace(/datetime:/, \"\");\r\n      var settings_list = settings_str.split(\"|\");\r\n      var settings = {};\r\n      settings.dateFormat = settings_list[0];\r\n      settings.timeFormat = settings_list[1];      \r\n      for (var i=2; i<settings_list.length; i++) {\r\n        var parts = settings_list[i].split(\"`\");\r\n        if (parts[1] === \"true\") {\r\n          parts[1] = true;\r\n        }\r\n        settings[parts[0]] = parts[1];\r\n      }\r\n    }\r\n    \$(this).datetimepicker(settings);\r\n    \$(\"#\" + field_name + \"_icon_id\").bind(\"click\",\r\n      { field_id: \"#\" + field_name + \"_id\" }, function(e) {      \r\n      \$.datepicker._showDatepicker(\$(e.data.field_id)[0]);\r\n    });\r\n  });  \r\n});\r\n\r\n\r\n')";
$g_sql[] = "INSERT INTO %PREFIX%field_types VALUES (9, 'yes', NULL, NULL, '{\$LANG.word_time}', 'time', 2, 'no', 'no', '', NULL, 2, 'small', 'none', 'core', '', '\r\n', '<div class=\"cf_date_group\">\r\n  <input type=\"input\" name=\"{\$NAME}\" value=\"{\$VALUE}\" class=\"cf_datefield cf_timepicker\" />\r\n  <input type=\"hidden\" id=\"{\$NAME}_id\" value=\"{\$display_format}\" />\r\n  \r\n  {if \$comments}\r\n    <div class=\"cf_field_comments\">{\$comments}</div>\r\n  {/if}\r\n</div>\r\n', '\r\n', '.cf_timepicker {\r\n  width: 60px; \r\n}\r\n.ui-timepicker-div .ui-widget-header{ margin-bottom: 8px; }\r\n.ui-timepicker-div dl{ text-align: left; }\r\n.ui-timepicker-div dl dt{ height: 25px; }\r\n.ui-timepicker-div dl dd{ margin: -25px 0 10px 65px; }\r\n.ui-timepicker-div td { font-size: 90%; }\r\n\r\n', '\$(function() {  \r\n  var default_settings = {\r\n    buttonImage:     g.root_url + \"/global/images/clock.png\",      \r\n    showOn:          \"both\",\r\n    buttonImageOnly: true\r\n  }\r\n  \$(\".cf_timepicker\").each(function() {\r\n    var field_name = \$(this).attr(\"name\");\r\n    var settings = default_settings;\r\n    if (\$(\"#\" + field_name + \"_id\").length) {\r\n      var settings_list = \$(\"#\" + field_name + \"_id\").val().split(\"|\");      \r\n      if (settings_list.length > 0) {\r\n        settings.timeFormat = settings_list[0];\r\n        for (var i=1; i<settings_list.length; i++) {\r\n          var parts = settings_list[i].split(\"`\");\r\n          if (parts[1] === \"true\") {\r\n            parts[1] = true;\r\n          } else if (parts[1] === \"false\") {\r\n            parts[1] = false;\r\n          }\r\n          settings[parts[0]] = parts[1];\r\n        }\r\n      }\r\n    }\r\n    \$(this).timepicker(settings);\r\n  });\r\n});\r\n\r\n')";
$g_sql[] = "INSERT INTO %PREFIX%field_types VALUES (10, 'yes', NULL, NULL, '{\$LANG.phrase_phone_number}', 'phone', 2, 'no', 'no', '', NULL, 3, 'small,medium', 'php', 'core', 'ft_display_field_type_phone_number', '{php}\r\n\$format = \$this->get_template_vars(\"phone_number_format\");\r\n\$values = explode(\"|\", \$this->get_template_vars(\"VALUE\"));\r\n\$pieces = preg_split(\"/(x+)/\", \$format, 0, PREG_SPLIT_DELIM_CAPTURE);\r\n\$counter = 1;\r\n\$output = \"\";\r\n\$has_content = false;\r\nforeach (\$pieces as \$piece)\r\n{\r\n  if (empty(\$piece))\r\n    continue;\r\n\r\n  if (\$piece[0] == \"x\") {    \r\n    \$value = (isset(\$values[\$counter-1])) ? \$values[\$counter-1] : \"\";\r\n    \$output .= \$value;\r\n    if (!empty(\$value))\r\n    {\r\n      \$has_content = true;\r\n    }\r\n    \$counter++;\r\n  } else {\r\n    \$output .= \$piece;\r\n  }\r\n}\r\n\r\nif (!empty(\$output) && \$has_content)\r\n  echo \$output;\r\n{/php}',  '{php}\r\n\$format = \$this->get_template_vars(\"phone_number_format\");\r\n\$values = explode(\"|\", \$this->get_template_vars(\"VALUE\"));\r\n\$name   = \$this->get_template_vars(\"NAME\");\r\n\r\n\$pieces = preg_split(\"/(x+)/\", \$format, 0, PREG_SPLIT_DELIM_CAPTURE);\r\n\$counter = 1;\r\nforeach (\$pieces as \$piece)\r\n{\r\n  if (strlen(\$piece) == 0)\r\n    continue;\r\n\r\n  if (\$piece[0] == \"x\") {\r\n    \$size = strlen(\$piece); \r\n    \$value = (isset(\$values[\$counter-1])) ? \$values[\$counter-1] : \"\";\r\n    \$value = htmlspecialchars(\$value);\r\n    echo \"<input type=\\\\\"text\\\\\" name=\\\\\"{\$name}_\$counter\\\\\" value=\\\\\"\$value\\\\\"\r\n            size=\\\\\"\$size\\\\\" maxlength=\\\\\"\$size\\\\\" />\";\r\n    \$counter++;\r\n  } else {\r\n    echo \$piece;\r\n  }\r\n}\r\n{/php}\r\n{if \$comments}\r\n  <div class=\"cf_field_comments\">{\$comments}</div>\r\n{/if}', '\$field_name = \$vars[\"field_info\"][\"field_name\"];\r\n\$joiner = \"|\";\r\n\r\n\$count = 1;\r\n\$parts = array();\r\nwhile (isset(\$vars[\"data\"][\"{\$field_name}_\$count\"]))\r\n{\r\n  \$parts[] = \$vars[\"data\"][\"{\$field_name}_\$count\"];\r\n  \$count++;\r\n}\r\n\$value = implode(\"|\", \$parts);\r\n\r\n\r\n', '', '')";
$g_sql[] = "INSERT INTO %PREFIX%field_types VALUES (11, 'yes', NULL, NULL, '{\$LANG.phrase_code_markup_field}', 'code_markup', 2, 'no', 'no', 'textarea', NULL, 4, 'large,very_large', 'php', 'core', 'ft_display_field_type_code_markup', '{if \$CONTEXTPAGE == \"edit_submission\"}\r\n  <textarea id=\"{\$NAME}_id\" name=\"{\$NAME}\">{\$VALUE}</textarea>\r\n  <script>\r\n  var code_mirror_{\$NAME} = new CodeMirror.fromTextArea(\"{\$NAME}_id\", \r\n  {literal}{{/literal}\r\n    height: \"{\$SIZE_PX}px\",\r\n    path:   \"{\$g_root_url}/global/codemirror/js/\",\r\n    readOnly: true,\r\n    {if \$code_markup == \"HTML\" || \$code_markup == \"XML\"}\r\n      parserfile: [\"parsexml.js\"],\r\n      stylesheet: \"{\$g_root_url}/global/codemirror/css/xmlcolors.css\"\r\n    {elseif \$code_markup == \"CSS\"}\r\n      parserfile: [\"parsecss.js\"],\r\n      stylesheet: \"{\$g_root_url}/global/codemirror/css/csscolors.css\"\r\n    {elseif \$code_markup == \"JavaScript\"}  \r\n      parserfile: [\"tokenizejavascript.js\", \"parsejavascript.js\"],\r\n      stylesheet: \"{\$g_root_url}/global/codemirror/css/jscolors.css\"\r\n    {/if}\r\n  {literal}});{/literal}\r\n  </script>\r\n{else}\r\n  {\$VALUE|strip_tags}\r\n{/if}\r\n', '<div class=\"editor\">\r\n  <textarea id=\"{\$NAME}_id\" name=\"{\$NAME}\">{\$VALUE}</textarea>\r\n</div>\r\n<script>\r\n  var code_mirror_{\$NAME} = new CodeMirror.fromTextArea(\"{\$NAME}_id\", \r\n  {literal}{{/literal}\r\n    height: \"{\$height}px\",\r\n    path:   \"{\$g_root_url}/global/codemirror/js/\",\r\n    {if \$code_markup == \"HTML\" || \$code_markup == \"XML\"}\r\n      parserfile: [\"parsexml.js\"],\r\n      stylesheet: \"{\$g_root_url}/global/codemirror/css/xmlcolors.css\"\r\n    {elseif \$code_markup == \"CSS\"}\r\n      parserfile: [\"parsecss.js\"],\r\n      stylesheet: \"{\$g_root_url}/global/codemirror/css/csscolors.css\"\r\n    {elseif \$code_markup == \"JavaScript\"}  \r\n      parserfile: [\"tokenizejavascript.js\", \"parsejavascript.js\"],\r\n      stylesheet: \"{\$g_root_url}/global/codemirror/css/jscolors.css\"\r\n    {/if}\r\n  {literal}});{/literal}\r\n</script>\r\n\r\n{if \$comments}\r\n  <div class=\"cf_field_comments\">{\$comments}</div>\r\n{/if}', '', '.cf_view_markup_field {\r\n  margin: 0px; \r\n}\r\n', '')";

$g_sql[] = "CREATE TABLE %PREFIX%form_email_fields (
  form_email_id MEDIUMINT unsigned NOT NULL auto_increment,
  form_id MEDIUMINT UNSIGNED NOT NULL,
  email_field_id mediumint(9) NOT NULL,
  first_name_field_id mediumint(9) NULL,
  last_name_field_id mediumint(9) NULL,
  PRIMARY KEY (form_email_id)
) DEFAULT CHARSET=utf8";

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
  option_list_id mediumint(9) default NULL,
  PRIMARY KEY  (field_id)
) DEFAULT CHARSET=utf8";

$g_sql[] = "CREATE TABLE %PREFIX%forms (
  form_id mediumint(9) unsigned NOT NULL auto_increment,
  form_type enum('internal','external') NOT NULL default 'external',
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
) DEFAULT CHARSET=utf8";

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
)";

$g_sql[] = "CREATE TABLE %PREFIX%hook_calls (
  hook_id mediumint(8) unsigned NOT NULL auto_increment,
  hook_type enum('code','template') NOT NULL default 'code',
  action_location varchar(100) NOT NULL,
  module_folder varchar(255) NOT NULL,
  function_name varchar(255) NOT NULL,
  hook_function varchar(255) NOT NULL,
  priority tinyint(4) NOT NULL default '50',
  PRIMARY KEY  (hook_id)
) DEFAULT CHARSET=utf8";

$g_sql[] = "CREATE TABLE %PREFIX%list_groups (
  group_id mediumint(8) unsigned NOT NULL auto_increment,
  group_type varchar(50) NOT NULL,
  group_name varchar(255) NOT NULL,
  custom_data text NOT NULL,
  list_order smallint(6) NOT NULL,
  PRIMARY KEY (group_id)
) DEFAULT CHARSET=utf8";

$g_sql[] = "INSERT INTO %PREFIX%list_groups VALUES (1, 'field_types', 'Standard Fields', '', 1)";
$g_sql[] = "INSERT INTO %PREFIX%list_groups VALUES (2, 'field_types', 'Special Fields', '', 2)";

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
) DEFAULT CHARSET=utf8";

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
) DEFAULT CHARSET=utf8";

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
) DEFAULT CHARSET=utf8";

$g_sql[] = "CREATE TABLE %PREFIX%modules (
  module_id mediumint(8) unsigned NOT NULL auto_increment,
  is_installed enum('yes','no') NOT NULL default 'no',
  is_enabled enum('yes','no') NOT NULL default 'no',
  is_premium enum('yes','no') NOT NULL default 'no',
  module_key varchar(12) default NULL,
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
) DEFAULT CHARSET=utf8";

$g_sql[] = "CREATE TABLE %PREFIX%multi_page_form_urls (
  form_id mediumint(8) unsigned NOT NULL,
  form_url varchar(255) NOT NULL,
  page_num tinyint(4) NOT NULL default '2',
  PRIMARY KEY  (form_id, page_num)
) DEFAULT CHARSET=utf8";

$g_sql[] = "CREATE TABLE %PREFIX%new_view_submission_defaults (
  view_id mediumint(9) NOT NULL,
  field_id mediumint(9) NOT NULL,
  default_value text NOT NULL,
  list_order smallint(6) NOT NULL,
  PRIMARY KEY (view_id,field_id)
) DEFAULT CHARSET=utf8";

$g_sql[] = "CREATE TABLE %PREFIX%option_lists (
  list_id mediumint(8) unsigned NOT NULL auto_increment,
  option_list_name varchar(100) NOT NULL,
  is_grouped enum('yes','no') NOT NULL,
  original_form_id mediumint(8) unsigned default NULL,
  PRIMARY KEY (list_id)
) DEFAULT CHARSET=utf8";

$g_sql[] = "CREATE TABLE %PREFIX%public_form_omit_list (
  form_id mediumint(8) unsigned NOT NULL,
  account_id mediumint(8) unsigned NOT NULL,
  PRIMARY KEY  (form_id,account_id)
) DEFAULT CHARSET=utf8";

$g_sql[] = "CREATE TABLE %PREFIX%public_view_omit_list (
  view_id mediumint(8) unsigned NOT NULL,
  account_id mediumint(8) unsigned NOT NULL,
  PRIMARY KEY  (view_id,account_id)
) DEFAULT CHARSET=utf8";

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
) DEFAULT CHARSET=utf8";

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
  PRIMARY KEY (theme_id)
) DEFAULT CHARSET=utf8";

$g_sql[] = "INSERT INTO %PREFIX%themes VALUES (1, 'default', 'Default', 'Encore Web Studios', 'formtools@encorewebstudios.com', 'http://www.encorewebstudios.com', 'http://themes.formtools.org/', 'The default Form Tools theme for all new installations. It''s a green-coloured fixed-width theme requiring 1024 minimum width screens.', 'yes', '1.0.0')";

$g_sql[] = "CREATE TABLE %PREFIX%view_columns (
  view_id mediumint(9) NOT NULL,
  field_id mediumint(9) NOT NULL,
  list_order smallint(6) NOT NULL,
  is_sortable enum('yes','no') NOT NULL,
  auto_size enum('yes','no') NOT NULL default 'yes',
  custom_width varchar(10) default NULL,
  truncate enum('truncate','no_truncate') NOT NULL default 'truncate',
  PRIMARY KEY  (view_id,field_id,list_order)
) DEFAULT CHARSET=utf8";

$g_sql[] = "CREATE TABLE %PREFIX%view_fields (
  view_id mediumint(8) unsigned NOT NULL,
  field_id mediumint(8) unsigned NOT NULL,
  group_id mediumint(9) default NULL,
  is_editable enum('yes','no') NOT NULL default 'yes',
  is_searchable enum('yes','no') NOT NULL default 'yes',
  list_order smallint(5) unsigned default NULL,
  is_new_sort_group enum('yes','no') NOT NULL,
  PRIMARY KEY  (view_id,field_id)
) DEFAULT CHARSET=utf8";

$g_sql[] = "CREATE TABLE %PREFIX%view_filters (
  filter_id mediumint(8) unsigned NOT NULL auto_increment,
  view_id mediumint(8) unsigned NOT NULL,
  filter_type enum('standard', 'client_map') NOT NULL default 'standard',
  field_id mediumint(8) unsigned NOT NULL,
  operator enum('equals','not_equals','like','not_like','before','after') NOT NULL default 'equals',
  filter_values mediumtext NOT NULL,
  filter_sql mediumtext NOT NULL,
  PRIMARY KEY  (filter_id)
) DEFAULT CHARSET=utf8";

$g_sql[] = "CREATE TABLE %PREFIX%view_tabs (
  view_id mediumint(8) unsigned NOT NULL,
  tab_number tinyint(3) unsigned NOT NULL,
  tab_label varchar(50) default NULL,
  PRIMARY KEY  (view_id,tab_number)
) DEFAULT CHARSET=utf8";

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
) DEFAULT CHARSET=utf8";
