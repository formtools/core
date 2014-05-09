<?php

/**
 * This file contains all code relating to upgrading Form Tools.
 *
 * @copyright Encore Web Studios 2010
 * @author Encore Web Studios <formtools@encorewebstudios.com>
 * @package 2-1-0
 * @subpackage Upgrade
 */

// -------------------------------------------------------------------------------------------------


/**
 * This function upgrades the Form Tools Core. As of 2.0.3, it works very simply: this gets called
 * every time a person goes to the login page, the function is called. It contains all the
 * updates made to the script since the original release and based on the release date of the users
 * current build, only upgrades the more recent changes. Since $g_release_date was only added in 2.0.3,
 * there's a helper function that contains the dates of the main releases, to ensure those are updated
 * properly.
 *
 * The changes here are listed in the changelog: http://docs.formtools.org/changelog.php
 *
 * @return boolean is_upgraded a boolean indicating whether or not the program was just upgraded.
 */
function ft_upgrade_form_tools()
{
  global $g_table_prefix, $g_current_version, $g_release_type, $g_release_date;

  $is_upgraded = false;
  $old_version_info = ft_get_core_version_info();


  // ----------------------------------------------------------------------------------------------
  // 2.0.0 beta updates

  if ($old_version_info["release_date"] < 20090113)
  {
    // add the Hooks table
    mysql_query("
      CREATE TABLE {$g_table_prefix}hooks (
        hook_id mediumint(8) unsigned NOT NULL auto_increment,
        action_location enum('start','end') NOT NULL,
        module_folder varchar(255) NOT NULL,
        core_function varchar(255) NOT NULL,
        hook_function varchar(255) NOT NULL,
        priority tinyint(4) NOT NULL default '50',
        PRIMARY KEY (hook_id)
      ) TYPE=MyISAM DEFAULT CHARSET=utf8
      ");
  }

  if ($old_version_info["release_date"] < 20090301)
  {
    mysql_query("
      ALTER TABLE {$g_table_prefix}email_templates
      CHANGE email_reply_to email_reply_to
      ENUM('none', 'admin', 'client', 'user', 'custom')
      CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL
        ");
  }

  if ($old_version_info["release_date"] < 20090317)
  {
    mysql_query("
      ALTER TABLE {$g_table_prefix}views
      ADD may_add_submissions ENUM('yes', 'no') NOT NULL DEFAULT 'no'
        ");
  }

  if ($old_version_info["release_date"] < 20090402)
  {
    mysql_query("
      ALTER TABLE {$g_table_prefix}hooks
      ADD hook_type ENUM('code', 'template') NOT NULL DEFAULT 'code' AFTER hook_id
        ");
    mysql_query("
      ALTER TABLE {$g_table_prefix}hooks
      CHANGE action_location action_location VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL
        ");
    mysql_query("
      ALTER TABLE {$g_table_prefix}account_settings
      CHANGE setting_value setting_value MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL
        ");
  }

  if ($old_version_info["release_date"] < 20090510)
  {
    mysql_query("
      ALTER TABLE {$g_table_prefix}view_fields
      ADD is_searchable ENUM('yes','no') NOT NULL DEFAULT 'yes' AFTER is_editable
        ");
  }

  // bug #117
  if ($old_version_info["release_date"] < 20090627)
  {
    mysql_query("
      ALTER TABLE {$g_table_prefix}view_filters
      CHANGE operator operator ENUM('equals', 'not_equals', 'like', 'not_like', 'before', 'after')
      CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'equals'
        ");
  }

  if ($old_version_info["release_date"] < 20090815)
  {
    mysql_query("
      ALTER TABLE {$g_table_prefix}forms
      ADD edit_submission_page_label TEXT NULL
        ");

    // for upgrades, for maximum language compatibility set all the form Edit Submission Labels to
    // $LANG.phrase_edit_submission. They can always change it to English or whatever language they
    // want. New installations will have that value set to the administrator's language
    $forms = ft_get_forms();
    foreach ($forms as $form_info)
    {
      $form_id = $form_info["form_id"];
      mysql_query("
        UPDATE {$g_table_prefix}forms
        SET    edit_submission_page_label = '{\$LANG.phrase_edit_submission|upper}'
        WHERE  form_id = $form_id
          ");
    }
  }

  if ($old_version_info["release_date"] < 20090826)
  {
    // bug fix for previous version which had a syntax error
    $query = mysql_query("SHOW COLUMNS FROM {$g_table_prefix}forms");
    $has_edit_submission_page_label_field = false;
    while ($row = mysql_fetch_assoc($query))
    {
      if ($row["Field"] == "edit_submission_page_label")
        $has_edit_submission_page_label_field = true;
    }

    if (!$has_edit_submission_page_label_field)
    {
      @mysql_query("ALTER TABLE {$g_table_prefix}forms ADD edit_submission_page_label TEXT NULL");
      $forms = ft_get_forms();
      foreach ($forms as $form_info)
      {
        $form_id = $form_info["form_id"];
        @mysql_query("
          UPDATE {$g_table_prefix}forms
          SET    edit_submission_page_label = '{\$LANG.phrase_edit_submission|upper}'
          WHERE  form_id = $form_id
            ");
      }
    }
  }

  if ($old_version_info["release_date"] < 20091113)
  {
    @mysql_query("ALTER TABLE {$g_table_prefix}view_filters ADD filter_type ENUM('standard', 'client_map') NOT NULL DEFAULT 'standard' AFTER view_id");
    @mysql_query("ALTER TABLE {$g_table_prefix}views ADD has_standard_filter ENUM('yes', 'no') NOT NULL DEFAULT 'no'");
    @mysql_query("ALTER TABLE {$g_table_prefix}views ADD has_client_map_filter ENUM('yes', 'no') NOT NULL DEFAULT 'no'");

    // set the has_standard_filter value to "yes" for any Views that have a filter defined
    $query = @mysql_query("SELECT view_id FROM {$g_table_prefix}view_filters GROUP BY view_id");
    while ($row = mysql_fetch_assoc($query))
    {
      $view_id = $row["view_id"];
      mysql_query("UPDATE {$g_table_prefix}views SET has_standard_filter = 'yes' WHERE view_id = $view_id");
    }
  }

  // this version introduced an improved "form email fields" feature that lets you mark multiple email
  // fields as having significance for the email mechanism. All DB changes relate to this new feature.
  if ($old_version_info["release_date"] < 20100118)
  {
    // [1] misc DB column updates
    @mysql_query("
      ALTER TABLE {$g_table_prefix}email_templates
      ADD email_from_form_email_id MEDIUMINT UNSIGNED NULL AFTER email_from_account_id
        ");
    @mysql_query("
      ALTER TABLE {$g_table_prefix}email_templates
      ADD email_reply_to_form_email_id MEDIUMINT UNSIGNED NULL AFTER email_reply_to_account_id
        ");

    // [2] email_from DB field update
    $email_from_query = mysql_query("
      SELECT email_id
      FROM   {$g_table_prefix}email_templates
      WHERE  email_from = 'user'
        ");
    @mysql_query("
      ALTER TABLE {$g_table_prefix}email_templates
      CHANGE email_from email_from ENUM('admin', 'client', 'form_email_field', 'custom', 'none')
      CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL
        ");
    while ($row = mysql_fetch_assoc($email_from_query))
    {
      $email_id = $row["email_id"];
      mysql_query("
        UPDATE {$g_table_prefix}email_templates
        SET    email_from = 'form_email_field'
        WHERE  email_id = $email_id
          ");
    }

    // [3] email_reply_to DB field update
    $email_reply_to_query = mysql_query("
      SELECT email_id
      FROM   {$g_table_prefix}email_templates
      WHERE  email_reply_to = 'user'
        ");
    @mysql_query("
      ALTER TABLE {$g_table_prefix}email_templates
      CHANGE email_reply_to email_reply_to ENUM('admin', 'client', 'form_email_field', 'custom', 'none')
      CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL
        ");
    while ($row = mysql_fetch_assoc($email_reply_to_query))
    {
      $email_id = $row["email_id"];
      mysql_query("
        UPDATE {$g_table_prefix}email_templates
        SET    email_reply_to = 'form_email_field'
        WHERE  email_id = $email_id
          ");
    }

    // [4] create our new form_email_fields table
    @mysql_query("
      CREATE TABLE {$g_table_prefix}form_email_fields (
        form_email_id MEDIUMINT unsigned NOT NULL auto_increment,
        form_id MEDIUMINT UNSIGNED NOT NULL,
        email_field VARCHAR( 255 ) NOT NULL,
        first_name_field VARCHAR( 255 ) NULL,
        last_name_field VARCHAR( 255 ) NULL,
        PRIMARY KEY (form_email_id)
      ) TYPE=MyISAM DEFAULT CHARSET=utf8
        ");

    // [5] rename the "recipient_user_type" enum options to call the "user" option "form_email_field" instead,
    // but first, store all the recipient_ids so we can update them after the DB change
    $recipients_id_query = mysql_query("
      SELECT recipient_id
      FROM   {$g_table_prefix}email_template_recipients
      WHERE  recipient_user_type = 'user'
        ");
    @mysql_query("
      ALTER TABLE {$g_table_prefix}email_template_recipients
      CHANGE recipient_user_type recipient_user_type ENUM('admin', 'client', 'form_email_field', 'custom')
      CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL
        ");
    @mysql_query("
      ALTER TABLE {$g_table_prefix}email_template_recipients
      ADD form_email_id MEDIUMINT UNSIGNED NULL AFTER account_id
        ");
    while ($row = mysql_fetch_assoc($recipients_id_query))
    {
      // we can safely set the form_email_id to 1 for these because after upgrading they will
      // have one and only one form email ID
      $recipient_id = $row["recipient_id"];
      mysql_query("
        UPDATE {$g_table_prefix}email_template_recipients
        SET    recipient_user_type = 'form_email_field',
               form_email_id = 1
        WHERE  recipient_id = $recipient_id
          ");
    }

    // [6] now update the old "user" email field data to the new "form email field" table
    // and update the corresponding DB tables
    $forms_query = mysql_query("SELECT form_id, user_email_field, user_first_name_field, user_last_name_field FROM {$g_table_prefix}forms");
    while ($form_info = mysql_fetch_assoc($forms_query))
    {
      $form_id = $form_info["form_id"];
      $user_email_field      = $form_info["user_email_field"];
      $user_first_name_field = $form_info["user_first_name_field"];
      $user_last_name_field  = $form_info["user_last_name_field"];

      if (!empty($user_email_field))
      {
        // create the new email field
        @mysql_query("
          INSERT INTO {$g_table_prefix}form_email_fields (form_id, email_field, first_name_field, last_name_field)
          VALUES ($form_id, '$user_email_field', '$user_first_name_field', '$user_last_name_field')
            ");
        $form_email_id = mysql_insert_id();

        // "from"
        @mysql_query("
          UPDATE {$g_table_prefix}email_templates
          SET    email_from_form_email_id = $form_email_id
          WHERE  form_id = $form_id AND
                 email_from = 'form_email_field'
            ");
        // "reply-to"
        @mysql_query("
          UPDATE {$g_table_prefix}email_templates
          SET    email_reply_to_form_email_id = $form_email_id
          WHERE  form_id = $form_id AND
                 email_reply_to = 'form_email_field'
              ");
        // "to"
        @mysql_query("
          UPDATE {$g_table_prefix}email_template_recipients
          SET    form_email_id = $form_email_id
          WHERE  form_id = $form_id AND
                 recipient_user_type = 'form_email_field'
              ");
      }
    }

    // delete the old fields in the forms table. They're not needed any more
    @mysql_query("ALTER TABLE {$g_table_prefix}forms DROP COLUMN user_email_field");
    @mysql_query("ALTER TABLE {$g_table_prefix}forms DROP COLUMN user_first_name_field");
    @mysql_query("ALTER TABLE {$g_table_prefix}forms DROP COLUMN user_last_name_field");
  }

  // ----------------------------------------------------------------------------------------------
  // 2.0.3 beta updates

  if ($old_version_info["release_date"] < 20100731)
  {
    // add the default security setting fields
    $settings = array(
      "default_max_failed_login_attempts" => "",
      "min_password_length"               => "",
      "required_password_chars"           => "",
      "num_password_history"              => "",
      "clients_may_edit_max_failed_login_attempts" => ""
    );
    ft_set_settings($settings);

    // now set the default values for all clients. This sucks, obviously - but eventually the
    // whole inheritance model for client account settings will be overhauled and replaced with a
    // "Client Group" system
    $client_settings = array(
      "min_password_length"       => "",
      "num_failed_login_attempts" => 0,
      "num_password_history"      => "",
      "required_password_chars"   => "",
      "may_edit_max_failed_login_attempts" => ""
    );

    $clients = ft_get_client_list();
    foreach ($clients as $client_info)
    {
      // add the current password to the password history queue
      $client_settings["password_history"] = $client_info["password"];
      ft_set_account_settings($client_info["account_id"], $client_settings);
    }
  }

  if ($old_version_info["release_date"] < 20100908)
  {
    // convert all core tables to MyISAM
    $core_tables = array(
      "accounts", "account_settings", "client_forms", "client_views", "email_templates",
      "email_template_edit_submission_views", "email_template_recipients", "field_options",
      "field_option_groups", "field_settings", "forms", "form_email_fields", "form_fields",
      "hooks", "menus", "menu_items", "modules", "module_menu_items", "multi_page_form_urls",
      "public_form_omit_list", "public_view_omit_list", "settings", "themes", "views",
      "view_fields", "view_filters", "view_tabs"
    );
    foreach ($core_tables as $table)
    {
      @mysql_query("ALTER TABLE {$g_table_prefix}$table TYPE=MyISAM");
    }

    // convert all the custom tables to MyISAM as well
    $forms = ft_get_forms();
    foreach ($forms as $form_info)
    {
      $form_id = $form_info["form_id"];
      @mysql_query("ALTER TABLE {$g_table_prefix}form_{$form_id} TYPE=MyISAM");
    }
  }

  // ----------------------------------------------------------------------------------------------
  // 2.1.0

  if ($old_version_info["release_date"] < 20101117)
  {
    mysql_query("ALTER TABLE {$g_table_prefix}menu_items ADD is_new_sort_group ENUM('yes','no') NOT NULL DEFAULT 'yes' AFTER is_submenu");
    mysql_query("ALTER TABLE {$g_table_prefix}form_fields ADD is_new_sort_group ENUM('yes','no') NOT NULL DEFAULT 'yes' AFTER list_order");
    mysql_query("ALTER TABLE {$g_table_prefix}accounts ADD last_logged_in DATETIME NULL AFTER account_status");
    mysql_query("ALTER TABLE {$g_tble_prefix}view_fields ADD is_new_sort_group ENUM('yes','no') NOT NULL DEFAULT 'yes'");
    mysql_query("ALTER TABLE {$g_table_prefix}views ADD is_new_sort_group ENUM('yes','no') NOT NULL DEFAULT 'yes' AFTER view_order");
    mysql_query("ALTER TABLE {$g_tble_prefix}field_options ADD is_new_sort_group ENUM('yes','no') NOT NULL DEFAULT 'yes'");

    /*
CREATE TABLE IF NOT EXISTS `ft_field_types` (
  `field_type_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `field_type_name` varchar(255) NOT NULL,
  `field_label` varchar(30) NOT NULL,
  `group_id` smallint(6) NOT NULL,
  `list_order` smallint(6) NOT NULL,
  `compatible_field_sizes` set('tiny','small','medium','large','very_large') NOT NULL,
  `view_field_smarty_markup` mediumtext NOT NULL,
  `edit_field_smarty_markup` mediumtext NOT NULL,
  `resources_css` mediumtext,
  `resources_js` mediumtext,
  PRIMARY KEY (`field_type_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=11 ;


INSERT INTO `ft_field_types` (`field_type_id`, `field_type_name`, `field_label`, `group_id`, `list_order`, `compatible_field_sizes`, `view_field_smarty_markup`, `edit_field_smarty_markup`, `resources_css`, `resources_js`) VALUES
(1, '{$LANG.word_textbox}', 'textbox', 1, 1, 'small,medium,large,very_large', 0, '{$VALUE}', '<input type="text" name="{$NAME}" value="{$VALUE|escape}" class="{$SETTING1}" />', 'input.size_tiny {\r\n  width: 50px; \r\n}\r\ninput.size_small {\r\n  width: 100px; \r\n}\r\ninput.size_medium {\r\n  width: 200px; \r\n}\r\ninput.size_full_size {\r\n  width: 100%; \r\n}\r\n', ''),
(2, '{$LANG.word_textarea}', 'textarea', 1, 2, '', 0, '{$VALUE|nl2br}', '<textarea name="{$NAME}">{$VALUE}</textarea>', '', ''),
(3, '{$LANG.word_password}', 'password', 1, 3, '', 0, '{$VALUE}', '<input type="password" name="{$NAME}" value="{$VALUE|escape}" class="password" />\r\n', 'input.password {\r\n  width: 120px;\r\n}\r\n', '\r\n'),
(4, '{$LANG.word_dropdown}', 'select', 2, 2, 'tiny,small,medium,large,very_large', 0, '', '', NULL, NULL),
(5, '{$LANG.phrase_multi_select_dropdown}', 'multi-select', 1, 5, '', 0, '', '', NULL, NULL),
(6, '{$LANG.phrase_radio_buttons}', 'radio-buttons', 1, 6, '', 0, '', '', NULL, NULL),
(7, '{$LANG.word_checkboxes}', 'checkboxes', 1, 7, '', 0, '', '', NULL, NULL),
(8, '{$LANG.word_file}', 'file', 1, 8, '', 0, '', '', NULL, NULL),
(9, '{$LANG.word_wysiwyg}', '', 2, 1, '', 0, '', '', NULL, NULL),
(10, '{$LANG.phrase_date_or_time}', '', 1, 4, '', 0, '', '', NULL, NULL);

CREATE TABLE IF NOT EXISTS `ft_field_type_settings` (
  `setting_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `field_type_id` mediumint(8) unsigned NOT NULL,
  `field_label` varchar(255) NOT NULL,
  `field_type` enum('textbox','textarea','password','radios','checkboxes','select','multi-select') NOT NULL,
  `field_orientation` enum('horizontal','vertical','na') NOT NULL DEFAULT 'na',
  `default_value` varchar(255) DEFAULT NULL,
  `is_required` enum('yes','no') DEFAULT NULL,
  `error_string` mediumtext,
  `list_order` smallint(6) NOT NULL,
  PRIMARY KEY (`setting_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=3 ;

INSERT INTO `ft_field_type_settings` (`setting_id`, `field_type_id`, `field_label`, `field_type`, `field_orientation`, `default_value`, `is_required`, `error_string`, `list_order`) VALUES
(1, 1, 'Size', 'select', 'na', '', '', '', 1),
(2, 1, 'Highlight', 'textarea', 'na', 'full', '', '', 2);

CREATE TABLE IF NOT EXISTS `ft_field_type_setting_options` (
  `setting_id` mediumint(9) NOT NULL,
  `option_text` varchar(255) DEFAULT NULL,
  `field_order` smallint(6) NOT NULL,
  PRIMARY KEY (`setting_id`,`field_order`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

ALTER TABLE `ft_views` ADD `group_id` SMALLINT NULL AFTER `is_new_sort_group`
ALTER TABLE `ft_field_types` CHANGE `compatible_field_sizes` `compatible_field_sizes` VARCHAR( 255 ) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '1char,2chars,tiny,small,medium,large,very_large'

CREATE TABLE `formtools_next`.`ft_new_view_submission_defaults` (
`view_id` MEDIUMINT NOT NULL ,
`field_id` MEDIUMINT NOT NULL ,
`default_value` TEXT NOT NULL ,
PRIMARY KEY ( `view_id` , `field_id` )
) ENGINE = MYISAM

ALTER TABLE `ft_new_view_submission_defaults` ADD `list_order` SMALLINT NOT NULL

ALTER TABLE `ft_accounts`
CHANGE `username` `username` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
CHANGE `password` `password` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL

ALTER TABLE `ft_view_fields` DROP `is_column` ,
DROP `is_sortable` ;

ALTER TABLE `ft_form_email_fields` CHANGE `email_field` `email_field_id` MEDIUMINT( 9 ) NOT NULL
ALTER TABLE `ft_form_email_fields` CHANGE `first_name_field` `first_name_field_id` MEDIUMINT( 9 ) NULL DEFAULT NULL
ALTER TABLE `ft_form_email_fields` CHANGE `last_name_field` `last_name_field_id` MEDIUMINT( 9 ) NULL DEFAULT NULL

ALTER TABLE `ft_forms` DROP `default_view_id`
*/


    mysql_query("
      CREATE TABLE IF NOT EXISTS {$g_table_prefix}field_types (
        field_type_id mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
        field_type_name varchar(255) NOT NULL,
        is_core_field enum('yes','no') NOT NULL DEFAULT 'no',
        group_id smallint(6) NOT NULL,
        list_order smallint(6) NOT NULL,
        compatible_field_sizes SET('tiny','small','medium','large','very_large') NOT NULL DEFAULT 'tiny,small,medium,large,very_large',
        view_field_smarty_markup MEDIUMTEXT,
        edit_field_smarty_markup MEDIUMTEXT,
        PRIMARY KEY (field_type_id)
      ) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=10
        ");

    mysql_query("
      INSERT INTO {$g_table_prefix}field_types (field_type_id, field_type_name, is_base_field_type, group_id, list_order) VALUES
      (1, '{\$LANG.word_textbox}', 'yes', 1, 1),
      (2, '{\$LANG.word_textarea}', 'yes', 1, 2),
      (3, '{\$LANG.word_password}', 'yes', 1, 3),
      (4, '{\$LANG.word_dropdown}', 'yes', 1, 4),
      (5, '{\$LANG.phrase_multi_select_dropdown}', 'yes', 1, 5),
      (6, '{\$LANG.phrase_radio_buttons}', 'yes', 1, 6),
      (7, '{\$LANG.word_checkboxes}', 'yes', 1, 7),
      (8, '{\$LANG.word_file}', 'yes', 1, 8),
      (9, '{\$LANG.word_wysiwyg}', 'yes', 2, 1)
      (10, '{\$LANG.word_date}', 'yes', 2, 2)
        ");



    // ALTER TABLE `ft_field_option_groups` RENAME `ft_option_lists`
    // ALTER TABLE `ft_option_lists` CHANGE `group_id` `list_id` MEDIUMINT( 8 ) UNSIGNED NOT NULL AUTO_INCREMENT
    // ALTER TABLE `ft_option_lists` CHANGE `group_name` `option_list_name` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL

    // need up update menus to change field_option_groups to option_lists

    // ALTER TABLE `ft_option_lists` DROP `field_orientation`

    // UPDATE ft_settings SET setting_name = 'num_option_lists_per_page' WHERE setting_name = 'num_field_option_groups_per_page'
    // ALTER TABLE `ft_forms` ADD `form_type` ENUM( 'internal', 'external' ) NOT NULL DEFAULT 'external' AFTER `form_id`
    // ALTER TABLE `ft_forms` ADD `add_submission_button_label` VARCHAR( 255 ) NULL DEFAULT '{$LANG.word_add}';

    // ALTER TABLE `ft_forms` CHANGE `form_url` `form_url` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL
/*
CREATE TABLE `ft_list_groups` (
`group_id` MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`group_type` ENUM( 'views', 'option_lists' ) NOT NULL ,
`group_name` VARCHAR( 255 ) NOT NULL ,
`list_order` SMALLINT NOT NULL
) TYPE = MYISAM ;
INSERT INTO `ft_list_groups` ( `group_id` , `group_type` , `group_name` , `list_order` )
VALUES (
NULL , 'field_types', '{$LANG.phrase_standard_fields}', '1'
), (
NULL , 'field_types', '{$LANG.phrase_special_fields}', '2'
);

CREATE TABLE `ft_field_type_setting_options` (
  `setting_id` mediumint(9) NOT NULL,
  `option_text` varchar(255) default NULL,
  `field_order` smallint(6) NOT NULL,
  PRIMARY KEY  (`setting_id`,`field_order`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `ft_field_type_settings` (
  `setting_id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `field_type_id` mediumint(8) unsigned NOT NULL,
  `field_label` varchar(255) NOT NULL,
  `field_type` enum('textbox','textarea','password','radios','checkboxes','select','multi-select') NOT NULL,
  `placeholder` varchar(255) NOT NULL,
  `field_orientation` enum('horizontal','vertical','na') NOT NULL DEFAULT 'na',
  `default_value` varchar(255) DEFAULT NULL,
  `list_order` smallint(6) NOT NULL,
  PRIMARY KEY (`setting_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1

ALTER TABLE `ft_form_fields` CHANGE `field_size` `field_size` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT 'medium'

ALTER TABLE `ft_view_fields` CHANGE `tab_number` `tab_group_id` TINYINT( 4 ) NULL DEFAULT NULL
DROP TABLE `ft_view_tabs`

ALTER TABLE `ft_themes` DROP `supports_ft_versions`
ALTER TABLE `ft_modules` DROP `supports_ft_versions`

INSERT INTO `formtools_next`.`ft_settings` (`setting_id`, `setting_name`, `setting_value`, `module`) VALUES
(NULL, 'edit_submission_shared_resources_js', '', 'core'),
(NULL, 'edit_submission_shared_resources_css', '', 'core');

// new setting: forms_page_default_message

*** Important change *** : the following field_name values in the ft_form_fields tables have changed:
Submission ID   -> core__submission_id
Last Modified   -> core__last_modified
Date            -> core__submission_date
IP Address      -> core__ip_address


CREATE TABLE `ft_view_columns` (
`view_id` MEDIUMINT NOT NULL ,
`field_id` MEDIUMINT NOT NULL ,
`list_order` SMALLINT NOT NULL ,
`is_sortable` ENUM( 'yes', 'no' ) NOT NULL ,
`auto_size` ENUM( 'yes', 'no' ) NOT NULL DEFAULT 'yes',
`custom_width` VARCHAR( 10 ) NULL ,
`truncate` ENUM( 'truncate', 'no_truncate' ) NOT NULL DEFAULT 'truncate',
PRIMARY KEY ( `view_id` , `field_id` , `list_order` )
) TYPE = MYISAM ;
*/

    $forms = ft_get_forms();
    $form_changes = array();
    $date_system_field_ids = array();

    foreach ($forms as $form_info)
    {
      $form_id = $form_info["form_id"];
      $fields = ft_get_form_fields($form_id);

      $field_types = array();
      foreach ($fields as $field_info)
      {
        $field_id = $field_info["field_id"];
        if (!array_key_exists($field_info["field_type"], $field_types))
          $field_types[$field_info["field_type"]] = array();

        if ($field_info["field_type"] == "system" && ($field_info["col_name"] == "last_modified_date" || $field_info["col_name"] == "submission_date"))
          $date_system_field_ids[] = $field_id;

        $field_types[$field_info["field_type"]][] = $field_id;
      }
      $form_changes[$form_id] = $field_types;
    }

    // now make the changes to the form table
    mysql_query("ALTER TABLE {$g_table_prefix}form_fields CHANGE field_type field_type_id SMALLINT NOT NULL DEFAULT '1'");
    mysql_query("ALTER TABLE {$g_table_prefix}form_fields ADD is_system_field ENUM('yes','no') NOT NULL DEFAULT 'no' AFTER field_type_id");


    // yeesh!
    $map = array(
      "textbox"       => 1,
      "textarea"      => 2,
      "password"      => 3,
      "select"        => 4,
      "multi-select"  => 5,
      "radio-buttons" => 6,
      "checkboxes"    => 7,
      "file"          => 8,
      "wysiwyg"       => 9,
      "date"          => 10,

      // this is special. All system fields are set to regular text fields at first. Then if they're in $date_system_field_ids
      // they get set to 10 (date)
      "system"        => 1
    );

    while (list($form_id, $changes) = each($form_changes))
    {
      while (list($field_label, $field_ids) = each($changes))
      {
        foreach ($field_ids as $field_id)
        {
          $field_type_id = $map[$field_label];

          if (in_array($field_id, $date_system_field_ids))
            $field_type_id = 10;

          $is_system_field = ($field_label == "system") ? "yes" : "no";

          mysql_query("
            UPDATE {$g_table_prefix}form_fields
            SET    field_type_id = $field_type_id,
                   is_system_field = '$is_system_field'
            WHERE  field_id = $field_id
          ");
        }
      }
    }
  }

  // ----------------------------------------------------------------------------------------------

  // if the full version string (version-type-date) is different, update the database
  if ($old_version_info["full"] != "{$g_current_version}-{$g_release_type}-{$g_release_date}")
  {
    $new_settings = array(
      "program_version" => $g_current_version,
      "release_date"    => $g_release_date,
      "release_type"    => $g_release_type
    );
    ft_set_settings($new_settings);
    $is_upgraded = true;
  }

  return $is_upgraded;
}


/**
 * Returns the current core version, whether it's a Beta or main release. The way Form Tools handles
 * the release versions was changed in 2.0.3-beta-20100919. Prior to that release, the "program_version"
 * setting stored the version information. The format changed depending on the release type:
 *    2.0.0               - example of main build version
 *    2.0.1-beta-20100931 - example of Beta version
 *
 * Now the current version information is stored in 3 fields in the settings table:
 *    "program_version" - JUST the main build info (e.g. 2.0.0)
 *    "release_type"    - "beta" or "main"
 *    "release_date"    - the date in the format YYYYMMDD
 *
 *
 * @return array a hash with the following keys:
 *                    "version"      => e.g. 2.0.0
 *                    "release_type" => "main" or "beta"
 *                    "release_date" => e.g. 20081231
 *                    "full"         => e.g. 2.0.0-beta-20081231 or 2.0.3
 */
function ft_get_core_version_info()
{
  $settings = ft_get_settings();

  $program_version = $settings["program_version"];

  // if there's a hyphen in the program version, we know we're dealing with an old beta version
  if (strpos($program_version, "-") !== false)
  {
    $parts = split("-", $program_version);
    $version_info = array(
      "full"         => $program_version,
      "version"      => $parts[0],
      "release_type" => $parts[1],
      "release_date" => $parts[2]
    );
  }

  // otherwise, it's either an old MAIN release, or 2.0.3-beta-20100919 or later
  else
  {
    if ($program_version == "2.0.0")
    {
      $version_info = array(
        "full"         => "2.0.0-main-20100101",
        "version"      => "2.0.0",
        "release_date" => "20100101",
        "release_type" => "main"
      );
    }
    else if ($program_version == "2.0.1")
    {
      $version_info = array(
        "full"         => "2.0.1-main-20100412",
        "version"      => "2.0.1",
        "release_date" => "20100412",
        "release_type" => "main"
      );
    }
    else if ($program_version == "2.0.2")
    {
      $version_info = array(
        "full"         => "2.0.2-main-20100704",
        "version"      => "2.0.2",
        "release_date" => "20100704",
        "release_type" => "main"
      );
    }
    else
    {
      // here, there will always be release_date and release_type values in $settings, except
      // for ONE scenario: when a user is upgrading to that first version. Here, the DB won't have
      // any values for those fields (release_date, release_type). To get around this, we set the
      // release date to be the day before: this enables the calling function (ft_upgrade_form_tools)
      // to just seamlessly upgrade to the new versioning scheme. From there on out, we can rely
      // on those settings being in the database
      $release_date    = (isset($settings["release_date"])) ? $settings["release_date"] : "20100919";
      $release_type    = (isset($settings["release_type"])) ? $settings["release_type"] : "beta";

      $version_info = array(
        "full"         => "{$program_version}-{$release_type}-{$release_date}",
        "version"      => $program_version,
        "release_date" => $release_date,
        "release_type" => $release_type
      );
    }
  }

  return $version_info;
}


/**
 * Used in the modules. Basically this is the old version of ft_get_core_version_info,
 * left here for regression. Eventually the modules should be updated to use the same version
 * methodology as the core. It's way better.
 *
 * @param $version_string
 */
function ft_get_version_info($version_string)
{
  $parts = split("-", $version_string);

  $version_parts = array();
  $version_parts["full"] = $version_string;
  $version_parts["version"] = $parts[0];
  $version_parts["release_type"] = (count($parts) > 1) ? $parts[1] : "main";
  $version_parts["release_date"] = (count($parts) > 2) ? $parts[2] : "";

  return $version_parts;
}


