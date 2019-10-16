<?php

/**
 * The installation class. Added in 3.0.0.
 *
 * @copyright Benjamin Keen 2018
 * @author Benjamin Keen <ben.keen@gmail.com>
 * @package 3-0-x
 * @subpackage Installation
 */


namespace FormTools;

use Smarty, PDO, Exception;


/**
 * Form Tools Installation class.
 */
class Installation
{

	/**
	 * All installation sessions are scoped to an "fti" key. This clears it.
	 */
	public static function clearSessions () {
		Sessions::clear("fti");
	}

    /**
     * This function attempts to create the config file for the user.
     * @return bool
     */
    public static function generateConfigFile($config_file)
    {
        // try and write to the config.php file directly. This will probably fail, but in the off-chance
        // the permissions are set, it saves the user the hassle of manually creating the file. I changed this
        // to use a relative path and realpath() in 2.1.0...
        $file = realpath("../global") . DIRECTORY_SEPARATOR . "config.php";

        $handle = @fopen($file, "w");
        if ($handle) {
            fwrite($handle, $config_file);
            fclose($handle);
            return true;
        }

        // no such luck! we couldn't create the file on the server. The user will need to do it manually
        return false;
    }


    /**
     * basic test to find out if the database has been set up (namely: includes the user_account,
     * settings, forms and form_fields tables). Returns true/false.
     */
    public static function checkConfigFileExists()
    {
        if (is_file(realpath(__DIR__ . "/../config.php"))) {
            return array(true, "");
        } else {
            return array(
                false,
                "The config.php file does not exist. You need to create it in your /global folder with the content specified in order to continue."
            );
        }
    }


    /**
     * This function is basically a hardcoded rollback mechanism to delete any and all database tables, called in the event
     * of something going wrong during database creation.
     *
     * @param Database $db
     * @param string $table_prefix
     */
    public static function deleteTables(Database $db, array $all_tables)
    {
        try {
            $db->beginTransaction();
            foreach ($all_tables as $table) {
                $db->query("DROP TABLE IF EXISTS {PREFIX}$table");
                $db->execute();
            }
            $db->processTransaction();
        } catch (Exception $e) {
            $db->rollbackTransaction();
        }
    }


    /**
     * Static helper method to checks a database connection.
     *
     * @param string $hostname
     * @param string $db_name
     * @param string $port
     * @param string $username
     * @param string $password
     * @return array
     */
    public static function checkConnection($hostname, $db_name, $port, $username, $password)
    {
        try {
            $dsn = sprintf("mysql:host=%s;port=%s;dbname=%s;charset=utf8", $hostname, $port, $db_name);
            new PDO($dsn, $username, $password, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
        } catch (Exception $e) {
            return array(false, $e->getMessage());
        }

        return array(true, "");
    }


    public static function evalSmartyString($placeholder_str, $placeholders = array(), $theme = "default")
    {
        $LANG = Core::$L;

        $smarty = new Smarty();
        $smarty->setTemplateDir("../global/smarty_plugins/");
        $smarty->setCompileDir(self::getCacheFolder());

        $smarty->assign("eval_str", $placeholder_str);
        if (!empty($placeholders)) {
			foreach ($placeholders as $key => $value) {
                $smarty->assign($key, $value);
            }
        }
        $smarty->assign("LANG", $LANG);
        $output = $smarty->fetch(realpath(__DIR__ . "/../smarty_plugins/eval.tpl"));

        return $output;
    }



	/**
	 * This is sent at the very last step. It emails the administrator a short welcome email containing their
	 * login information, with a few links to resources on our site.
	 *
	 * Note: this is ALWAYS sent with mail(), since the Swift Mailer plugin won't have been configured yet.
	 *
	 * @param $email
	 * @param $username
	 */
    public static function sendWelcomeEmail($email, $username)
    {
        $root_dir = Core::getRootDir();
        $root_url = Core::getRootUrl();

        // 1. build the email content
        $placeholders = array(
            "login_url" => $root_url,
            "username" => $username
        );
        $smarty_template_email_content = file_get_contents("$root_dir/global/emails/installed.tpl");
        $email_content = General::evalSmartyString($smarty_template_email_content, $placeholders);

        // 2. build the email subject line
        $smarty_template_email_subject = file_get_contents("$root_dir/global/emails/installed_subject.tpl");
        $email_subject = trim(General::evalSmartyString($smarty_template_email_subject, array()));

        // send email [note: the double quotes around the email recipient and content are intentional:
        // some systems fail without it]
        @mail("$email", $email_subject, $email_content);
    }


    /**
     * This is called after the database is created and all the various settings (like root URL, etc) are
     * determined. It updates the database to set the various default settings.
     */
    public static function updateDatabaseSettings()
    {
        $rootURL = Core::getRootUrl();
        $rootDir = Core::getRootDir();

        // store the list of languages as a string in settings
        $list = Core::$translations->getList();
        $lang_hash = array();
        foreach ($list as $lang_info) {
            $lang_hash[$lang_info->code] = $lang_info->lang;
        }
        $lang_string = Translations::getLangListAsString($lang_hash);

        // we add slashes since in PC paths like c:\www\whatever the \'s get lost en route
        $core_settings = array(
            "default_logout_url" => $rootURL,
            "file_upload_dir"    => addslashes($rootDir) . "/upload",
            "file_upload_url"    => "$rootURL/upload",
            "available_languages" => $lang_string
        );
        Settings::set($core_settings, "core");
    }


    /**
     * Creates the Form Tools database tables.
     */
    public static function createDatabase(Database $db)
    {
        $charset = Core::getDbTableCharset();
        $LANG = Core::$L;

        try {
            $db->beginTransaction();

            // suppress strict mode
            $db->query("SET SQL_MODE=''");
            $db->execute();

            $db->query("
                CREATE TABLE {PREFIX}account_settings (
                  account_id mediumint(8) unsigned NOT NULL,
                  setting_name varchar(255) NOT NULL,
                  setting_value mediumtext NOT NULL,
                  PRIMARY KEY  (account_id,setting_name)
                ) ENGINE=InnoDB DEFAULT CHARSET=$charset
            ");
            $db->execute();

            $db->query("
                CREATE TABLE {PREFIX}accounts (
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
                ) ENGINE=InnoDB DEFAULT CHARSET=$charset
            ");
            $db->execute();

            $db->query("
                INSERT INTO {PREFIX}accounts (account_id, account_type, account_status, timezone_offset, 
                  login_page, swatch, menu_id, username, password)
                  VALUES (1, 'admin', 'active', '0', 'admin_forms', 'green', 1, '', '')
            ");
            $db->execute();

            $db->query("
                CREATE TABLE {PREFIX}client_forms (
                  account_id mediumint(8) unsigned NOT NULL,
                  form_id mediumint(8) unsigned NOT NULL,
                  PRIMARY KEY (account_id,form_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=$charset
            ");
            $db->execute();

            $db->query("
                CREATE TABLE {PREFIX}client_views (
                  account_id mediumint(8) unsigned NOT NULL,
                  view_id mediumint(8) unsigned NOT NULL,
                  PRIMARY KEY (account_id,view_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=$charset
            ");
            $db->execute();

            $db->query("
                CREATE TABLE {PREFIX}email_template_edit_submission_views (
                  email_id mediumint(8) unsigned NOT NULL,
                  view_id mediumint(8) unsigned NOT NULL,
                  PRIMARY KEY  (email_id,view_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=$charset
            ");
            $db->execute();

            $db->query("
                CREATE TABLE {PREFIX}email_template_recipients (
                  recipient_id mediumint(8) unsigned NOT NULL auto_increment,
                  email_template_id mediumint(8) unsigned NOT NULL,
                  recipient_user_type enum('admin','client','form_email_field','custom') NOT NULL,
                  recipient_type enum('main','cc','bcc') NOT NULL default 'main',
                  account_id mediumint(9) default NULL,
                  form_email_id MEDIUMINT UNSIGNED NULL,
                  custom_recipient_name varchar(200) default NULL,
                  custom_recipient_email varchar(200) default NULL,
                  PRIMARY KEY  (recipient_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=$charset
            ");
            $db->execute();

            $db->query("
                CREATE TABLE {PREFIX}email_template_when_sent_views (
                  email_id mediumint(9) NOT NULL,
                  view_id mediumint(9) NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=$charset
            ");
            $db->execute();

            $db->query("
                CREATE TABLE {PREFIX}email_templates (
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
                ) ENGINE=InnoDB DEFAULT CHARSET=$charset
            ");
            $db->execute();

            $db->query("
                CREATE TABLE {PREFIX}field_options (
                  list_id mediumint(8) unsigned NOT NULL,
                  list_group_id mediumint(9) NOT NULL,
                  option_order smallint(4) NOT NULL,
                  option_value varchar(255) NOT NULL,
                  option_name varchar(255) NOT NULL,
                  is_new_sort_group enum('yes', 'no') NOT NULL,
                  PRIMARY KEY (list_id, list_group_id, option_order)
                ) ENGINE=InnoDB DEFAULT CHARSET=$charset
            ");
            $db->execute();

            $db->query("
                CREATE TABLE {PREFIX}field_settings (
                  field_id mediumint(8) unsigned NOT NULL,
                  setting_id mediumint(9) NOT NULL,
                  setting_value mediumtext,
                  PRIMARY KEY (field_id,setting_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=$charset
            ");
            $db->execute();

            $db->query("
                CREATE TABLE {PREFIX}field_type_setting_options (
                  setting_id mediumint(9) NOT NULL,
                  option_text varchar(255) default NULL,
                  option_value varchar(255) default NULL,
                  option_order smallint(6) NOT NULL,
                  is_new_sort_group enum('yes','no') NOT NULL,
                  PRIMARY KEY  (setting_id,option_order)
                ) ENGINE=InnoDB DEFAULT CHARSET=$charset
            ");
            $db->execute();

            $db->query("
                CREATE TABLE {PREFIX}field_type_settings (
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
                ) ENGINE=InnoDB DEFAULT CHARSET=$charset
            ");
            $db->execute();

            $db->query("
                CREATE TABLE {PREFIX}field_types (
                  field_type_id mediumint(8) unsigned NOT NULL auto_increment,
                  is_editable enum('yes','no') NOT NULL,
                  is_enabled enum('yes','no') NOT NULL default 'yes',
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
                ) ENGINE=InnoDB DEFAULT CHARSET=$charset
            ");
            $db->execute();

            $db->query("
                CREATE TABLE {PREFIX}field_type_validation_rules (
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
                ) ENGINE=InnoDB DEFAULT CHARSET=$charset
            ");
            $db->execute();

            $db->query("
                CREATE TABLE {PREFIX}field_validation (
                  rule_id mediumint(8) unsigned NOT NULL,
                  field_id mediumint(9) NOT NULL,
                  error_message mediumtext NOT NULL,
                  UNIQUE KEY rule_id (rule_id,field_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=$charset
            ");
            $db->execute();

            $db->query("
                CREATE TABLE {PREFIX}form_email_fields (
                  form_email_id MEDIUMINT unsigned NOT NULL auto_increment,
                  form_id MEDIUMINT UNSIGNED NOT NULL,
                  email_field_id mediumint(9) NOT NULL,
                  first_name_field_id mediumint(9) NULL,
                  last_name_field_id mediumint(9) NULL,
                  PRIMARY KEY (form_email_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=$charset
            ");
            $db->execute();

            $db->query("
                CREATE TABLE {PREFIX}form_fields (
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
                ) ENGINE=InnoDB DEFAULT CHARSET=$charset
            ");
            $db->execute();

            $db->query("
                CREATE TABLE {PREFIX}forms (
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
                  add_submission_button_label varchar(255) default '',
                  PRIMARY KEY (form_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=$charset
            ");
            $db->execute();

            $db->query("
                CREATE TABLE {PREFIX}hooks (
                  id mediumint(8) unsigned NOT NULL auto_increment,
                  hook_type enum('code','template') NOT NULL,
                  component enum('core','api','module') NOT NULL,
                  filepath varchar(255) NOT NULL,
                  action_location varchar(255) NOT NULL,
                  function_name varchar(255) NOT NULL,
                  params mediumtext,
                  overridable mediumtext,
                  PRIMARY KEY (id)
                ) ENGINE=InnoDB DEFAULT CHARSET=$charset
            ");
            $db->execute();

            $db->query("
                CREATE TABLE {PREFIX}hook_calls (
                  hook_id mediumint(8) unsigned NOT NULL auto_increment,
                  hook_type enum('code','template') NOT NULL default 'code',
                  action_location varchar(100) NOT NULL,
                  module_folder varchar(255) NOT NULL,
                  function_name varchar(255) NOT NULL,
                  hook_function varchar(255) NOT NULL,
                  priority tinyint(4) NOT NULL default '50',
                  PRIMARY KEY  (hook_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=$charset
            ");
            $db->execute();

            $db->query("
                CREATE TABLE {PREFIX}list_groups (
                  group_id mediumint(8) unsigned NOT NULL auto_increment,
                  group_type varchar(50) NOT NULL,
                  group_name varchar(255) NOT NULL,
                  custom_data text NOT NULL,
                  list_order smallint(6) NOT NULL,
                  PRIMARY KEY (group_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=$charset
            ");
            $db->execute();

            $db->query("
                CREATE TABLE {PREFIX}menu_items (
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
                ) ENGINE=InnoDB DEFAULT CHARSET=$charset
            ");
            $db->execute();

            $db->query("INSERT INTO {PREFIX}menu_items VALUES (1, 1, 'Forms', 'admin_forms', '', '/admin/forms/', 'no', 'yes', 1)");
            $db->execute();
            $db->query("INSERT INTO {PREFIX}menu_items VALUES (2, 1, 'Add Form', 'add_form_choose_type', '', '/admin/forms/add/', 'yes', 'no', 2)");
            $db->execute();
            $db->query("INSERT INTO {PREFIX}menu_items VALUES (3, 1, 'Option Lists', 'option_lists', '', '/admin/forms/option_lists/', 'yes', 'no', 3)");
            $db->execute();
            $db->query("INSERT INTO {PREFIX}menu_items VALUES (4, 1, 'Clients', 'clients', '', '/admin/clients/', 'no', 'yes', 4)");
            $db->execute();
            $db->query("INSERT INTO {PREFIX}menu_items VALUES (5, 1, 'Modules', 'modules', '', '/admin/modules/', 'no', 'yes', 5)");
            $db->execute();
            $db->query("INSERT INTO {PREFIX}menu_items VALUES (6, 1, 'Themes', 'settings_themes', '', '/admin/themes/', 'no', 'yes', 6)");
            $db->execute();
            $db->query("INSERT INTO {PREFIX}menu_items VALUES (7, 1, 'Settings', 'settings', '', '/admin/settings', 'no', 'yes', 7)");
            $db->execute();
            $db->query("INSERT INTO {PREFIX}menu_items VALUES (8, 1, 'Main', 'settings_main', '', '/admin/settings/index.php?page=main', 'yes', 'no', 8)");
            $db->execute();
            $db->query("INSERT INTO {PREFIX}menu_items VALUES (9, 1, 'Accounts', 'settings_accounts', '', '/admin/settings/index.php?page=accounts', 'yes', 'no', 9)");
            $db->execute();
            $db->query("INSERT INTO {PREFIX}menu_items VALUES (10, 1, 'Files', 'settings_files', '', '/admin/settings/index.php?page=files', 'yes', 'no', 10)");
            $db->execute();
            $db->query("INSERT INTO {PREFIX}menu_items VALUES (11, 1, 'Menus', 'settings_menus', '', '/admin/settings/index.php?page=menus', 'yes', 'no', 11)");
            $db->execute();
            $db->query("INSERT INTO {PREFIX}menu_items VALUES (12, 1, 'Your Account', 'your_account', '', '/admin/account', 'no', 'yes', 12)");
            $db->execute();
            $db->query("INSERT INTO {PREFIX}menu_items VALUES (13, 1, 'Logout', 'logout', '', '/index.php?logout', 'no', 'yes', 13)");
            $db->execute();
            $db->query("INSERT INTO {PREFIX}menu_items VALUES (14, 2, 'Forms', 'client_forms', '', '/clients/index.php', 'no', 'yes', 1)");
            $db->execute();
            $db->query("INSERT INTO {PREFIX}menu_items VALUES (15, 2, 'Account', 'client_account', '', '/clients/account/index.php', 'no', 'yes', 2)");
            $db->execute();
            $db->query("INSERT INTO {PREFIX}menu_items VALUES (16, 2, 'Login Info', 'client_account_login', '', '/clients/account/index.php?page=main', 'yes', 'no', 3)");
            $db->execute();
            $db->query("INSERT INTO {PREFIX}menu_items VALUES (17, 2, 'Settings', 'client_account_settings', '', '/clients/account/index.php?page=settings', 'yes', 'no', 4)");
            $db->execute();
            $db->query("INSERT INTO {PREFIX}menu_items VALUES (18, 2, 'Logout', 'logout', '', '/index.php?logout', 'no', 'yes', 5)");
            $db->execute();

            $db->query("
                CREATE TABLE {PREFIX}menus (
                  menu_id smallint(5) unsigned NOT NULL auto_increment,
                  menu varchar(255) NOT NULL,
                  menu_type enum('admin','client') NOT NULL default 'client',
                  PRIMARY KEY  (menu_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=$charset
            ");
            $db->execute();

            $db->query("INSERT INTO {PREFIX}menus VALUES (1, 'Administrator', 'admin')");
            $db->execute();
            $db->query("INSERT INTO {PREFIX}menus VALUES (2, 'Client Menu', 'client')");
            $db->execute();

            $db->query("
                CREATE TABLE {PREFIX}module_menu_items (
                  menu_id mediumint(8) unsigned NOT NULL auto_increment,
                  module_id mediumint(8) unsigned NOT NULL,
                  display_text varchar(100) NOT NULL,
                  url varchar(255) NOT NULL,
                  is_submenu enum('yes','no') NOT NULL default 'no',
                  list_order smallint(6) NOT NULL,
                  PRIMARY KEY  (menu_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=$charset
            ");
            $db->execute();

            $db->query("
                CREATE TABLE {PREFIX}modules (
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
                  module_date datetime NOT NULL,
                  PRIMARY KEY  (module_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=$charset
            ");
            $db->execute();

            $db->query("
                CREATE TABLE {PREFIX}multi_page_form_urls (
                  form_id mediumint(8) unsigned NOT NULL,
                  form_url varchar(255) NOT NULL,
                  page_num tinyint(4) NOT NULL default '2',
                  PRIMARY KEY  (form_id, page_num)
                ) ENGINE=InnoDB DEFAULT CHARSET=$charset
            ");
            $db->execute();

            $db->query("
                CREATE TABLE {PREFIX}new_view_submission_defaults (
                  view_id mediumint(9) NOT NULL,
                  field_id mediumint(9) NOT NULL,
                  default_value text NOT NULL,
                  list_order smallint(6) NOT NULL,
                  PRIMARY KEY (view_id,field_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=$charset
            ");
            $db->execute();

            $db->query("
                CREATE TABLE {PREFIX}option_lists (
                  list_id mediumint(8) unsigned NOT NULL auto_increment,
                  option_list_name varchar(100) NOT NULL,
                  is_grouped enum('yes','no') NOT NULL,
                  original_form_id mediumint(8) unsigned default NULL,
                  PRIMARY KEY (list_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=$charset
            ");
            $db->execute();

            $db->query("
                CREATE TABLE {PREFIX}public_form_omit_list (
                  form_id mediumint(8) unsigned NOT NULL,
                  account_id mediumint(8) unsigned NOT NULL,
                  PRIMARY KEY  (form_id,account_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=$charset
            ");
            $db->execute();

            $db->query("
                CREATE TABLE {PREFIX}public_view_omit_list (
                  view_id mediumint(8) unsigned NOT NULL,
                  account_id mediumint(8) unsigned NOT NULL,
                  PRIMARY KEY  (view_id,account_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=$charset
            ");
            $db->execute();

            $db->query("
                CREATE TABLE {PREFIX}sessions (
                  session_id varchar(100) NOT NULL default '',
                  session_data text NOT NULL,
                  expires int(11) NOT NULL default '0',
                  PRIMARY KEY (session_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1
            ");
            $db->execute();

            $db->query("
                CREATE TABLE {PREFIX}settings (
                  setting_id mediumint(9) NOT NULL auto_increment,
                  setting_name varchar(100) NOT NULL,
                  setting_value text NOT NULL,
                  module varchar(100) NOT NULL default 'core',
                  PRIMARY KEY  (setting_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=$charset
            ");
            $db->execute();

            $edit_submission_onload_resources = self::getEditSubmissionOnloadResources();

            $edit_submission_shared_resources_js =<<< END
$(function() {
    $(".fancybox").fancybox();
});
END;

            $edit_submission_shared_resources_css =<<< END
/* used in the "Highlight" setting for most field types */
.cf_colour_red {
    background-color: #990000;
    color: white;
}
.cf_colour_orange {
    background-color: orange;
}
.cf_colour_yellow {
    background-color: yellow;
}
.cf_colour_green {
    background-color: green;
    color: white;
}
.cf_colour_blue {
    background-color: #336699;
    color: white;
}
/* field comments */
.cf_field_comments {
    font-style: italic;
    color: #999999;
    clear: both;
}

/* column layouts for radios & checkboxes */
.cf_option_list_group_label {
    font-weight: bold;
    clear: both;
    margin-left: 4px;
}
.cf_option_list_2cols,
.cf_option_list_3cols,
.cf_option_list_4cols {
    clear: both;
}
.cf_option_list_2cols .column {
    width: 50%;
    float: left;
}
.cf_option_list_3cols .column {
    width: 33%;
    float: left;
}
.cf_option_list_4cols .column {
    width: 25%;
    float: left;
}

/* Used for the date and time pickers */
.cf_date_group img {
    margin-bottom: -4px;
    padding: 1px;
}
END;

            $core_settings = array(
                array("program_version", Core::getCoreVersion()),
                array("release_date", Core::getReleaseDate()),
                array("release_type", Core::getReleaseType()),
                array("api_version", ""),
                array("available_languages", "en_us,English (US)"),
                array("clients_may_edit_date_format", "no"),
                array("clients_may_edit_footer_text", "no"),
                array("clients_may_edit_logout_url", "yes"),
                array("clients_may_edit_max_failed_login_attempts", "no"),
                array("clients_may_edit_page_titles", "no"),
                array("clients_may_edit_sessions_timeout", "no"),
                array("clients_may_edit_theme", "yes"),
                array("clients_may_edit_timezone_offset", "yes"),
                array("clients_may_edit_ui_language", "yes"),
                array("default_client_menu_id", 2),
                array("default_client_swatch", "green"),
                array("default_date_field_search_value", "none"),
                array("default_date_format", "M jS y, g:i A"),
                array("default_footer_text", ""),
                array("default_language", "en_us"),
                array("default_login_page", "client_forms"),
                array("default_logout_url", ""),
                array("default_max_failed_login_attempts", ""),
                array("default_num_submissions_per_page", 10),
                array("default_page_titles", "Form Tools - {\$page}"),
                array("default_sessions_timeout", 30),
                array("default_theme", "default"),
                array("default_timezone_offset", 0),
                array("edit_submission_shared_resources_js", $edit_submission_shared_resources_js),
                array("edit_submission_shared_resources_css", $edit_submission_shared_resources_css),
                array("edit_submission_onload_resources", $edit_submission_onload_resources),
                array("field_type_settings_shared_characteristics", "field_comments:textbox,comments`textarea,comments`password,comments`dropdown,comments`multi_select_dropdown,comments`radio_buttons,comments`checkboxes,comments`date,comments`time,comments`phone,comments`code_markup,comments`file,comments`google_maps_field,comments`tinymce,comments|data_source:dropdown,contents`multi_select_dropdown,contents`radio_buttons,contents`checkboxes,contents|column_formatting:checkboxes,formatting`radio_buttons,formatting|maxlength_attr:textbox,maxlength|colour_highlight:textbox,highlight|folder_path:file,folder_path|folder_url:file,folder_url|permitted_file_types:file,folder_url|max_file_size:file,max_file_size|date_display_format:date,display_format|apply_timezone_offset:date,apply_timezone_offset"),
                array("file_upload_dir", ""),
                array("file_upload_filetypes", "bmp,gif,jpg,jpeg,png,avi,mp3,mp4,doc,txt,pdf,xml,csv,swf,fla,xls,tif"),
                array("file_upload_url", ""),
                array("file_upload_max_size", 2000),
                array("forms_page_default_message", $LANG["text_client_welcome"]),
                array("logo_link", "https://formtools.org"),
                array("min_password_length", ""),
                array("num_clients_per_page", 10),
                array("num_emails_per_page", 10),
                array("num_forms_per_page", 10),
                array("num_menus_per_page", 10),
                array("num_modules_per_page", 10),
                array("num_option_lists_per_page", 10),
                array("num_password_history", ""),
                array("program_name", "Form Tools"),
                array("required_password_chars", ""),
                array("timezone_offset", 0),
                array("core_version_upgrade_track", Core::getVersionString()),
            );

            $query = "INSERT INTO {PREFIX}settings (setting_name, setting_value, module) VALUES (:setting_name, :setting_value, 'core')";
            foreach ($core_settings as $setting) {
                $db->query($query);
                $db->bind("setting_name", $setting[0]);
                $db->bind("setting_value", $setting[1]);
                $db->execute();
            }

            $db->query("
                CREATE TABLE {PREFIX}themes (
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
                ) ENGINE=InnoDB DEFAULT CHARSET=$charset
            ");
            $db->execute();

            $db->query("INSERT INTO {PREFIX}themes VALUES (1, 'default', 'Default', 'yes', 'green', 'Form Tools', 'ben.keen@gmail.com', 'https://formtools.org', 'https://themes.formtools.org/', 'The default Form Tools theme for all new installations. It''s a green-coloured fixed-width theme requiring 1024 minimum width screens.', 'yes', '1.0.0')");
            $db->execute();

            $db->query("
                CREATE TABLE {PREFIX}view_columns (
                  view_id mediumint(9) NOT NULL,
                  field_id mediumint(9) NOT NULL,
                  list_order smallint(6) NOT NULL,
                  is_sortable enum('yes','no') NOT NULL,
                  auto_size enum('yes','no') NOT NULL default 'yes',
                  custom_width varchar(10) default NULL,
                  truncate enum('truncate','no_truncate') NOT NULL default 'truncate',
                  PRIMARY KEY  (view_id,field_id,list_order)
                ) ENGINE=InnoDB DEFAULT CHARSET=$charset
            ");
            $db->execute();

            $db->query("
                CREATE TABLE {PREFIX}view_fields (
                  view_id mediumint(8) unsigned NOT NULL,
                  field_id mediumint(8) unsigned NOT NULL,
                  group_id mediumint(9) default NULL,
                  is_editable enum('yes','no') NOT NULL default 'yes',
                  is_searchable enum('yes','no') NOT NULL default 'yes',
                  list_order smallint(5) unsigned default NULL,
                  is_new_sort_group enum('yes','no') NOT NULL,
                  PRIMARY KEY  (view_id,field_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=$charset
            ");
            $db->execute();

            $db->query("
                CREATE TABLE {PREFIX}view_filters (
                  filter_id mediumint(8) unsigned NOT NULL auto_increment,
                  view_id mediumint(8) unsigned NOT NULL,
                  filter_type enum('standard', 'client_map') NOT NULL default 'standard',
                  field_id mediumint(8) unsigned NOT NULL,
                  operator enum('equals','not_equals','like','not_like','before','after') NOT NULL default 'equals',
                  filter_values mediumtext NOT NULL,
                  filter_sql mediumtext NOT NULL,
                  PRIMARY KEY  (filter_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=$charset
            ");
            $db->execute();

            $db->query("
                CREATE TABLE {PREFIX}view_tabs (
                  view_id mediumint(8) unsigned NOT NULL,
                  tab_number tinyint(3) unsigned NOT NULL,
                  tab_label varchar(50) default NULL,
                  PRIMARY KEY  (view_id,tab_number)
                ) ENGINE=InnoDB DEFAULT CHARSET=$charset
            ");
            $db->execute();

            $db->query("
                CREATE TABLE {PREFIX}views (
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
                  may_copy_submissions enum('yes','no') NOT NULL DEFAULT 'no',
                  may_edit_submissions enum('yes','no') NOT NULL default 'yes',
                  may_delete_submissions enum('yes','no') NOT NULL default 'yes',
                  has_client_map_filter enum('yes','no') NOT NULL default 'no',
                  has_standard_filter enum('yes','no') NOT NULL default 'no',
                  PRIMARY KEY  (view_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=$charset
            ");
            $db->execute();

            $db->processTransaction();
        } catch (Exception $e) {
            $db->rollbackTransaction();
            return array(false, $e->getMessage());
        }

        return array(true, "");
    }


    /**
     * Creates the administrator account (well, updates it). Used in the installation process only.
     * @param array $info
     * @return array
     */
    public static function setAdminAccount(array $info, $lang)
    {
        $db = Core::$db;

		try {
			$db->query("
				UPDATE {PREFIX}accounts
				SET   first_name = :first_name,
					  last_name = :last_name,
					  email = :email,
					  username = :username,
					  password = :password,
					  logout_url = :logout_url,
					  ui_language = :ui_language
				WHERE account_id = :account_id
			");
			$db->bindAll(array(
				"first_name" => $info["firstName"],
				"last_name" => $info["lastName"],
				"email" => $info["email"],
				"username" => $info["username"],
				"password" => General::encode($info["password"]),
				"logout_url" => Core::getRootUrl(),
				"ui_language" => $lang,
				"account_id" => 1 // the admin account is always ID 1
			));
            $db->execute();
        } catch (Exception $e) {
            return array(false, $e->getMessage());
        }

        return array(true, "");
    }

    /**
     * Installs the core field types. From 2.1.5 up until 3.0.0, the core fields were in a separate module. As of
     * 3.0.0 they were moved to the Core.
     */
    public static function installCoreFieldTypes()
    {
        $db = Core::$db;

        // this ensures that the module contents only get installed once
        $field_types = FieldTypes::get();
        if (count($field_types) > 0) {
            return array(true, "");
        }

        $group_query = "
            INSERT INTO {PREFIX}list_groups (group_type, group_name, custom_data, list_order)
            VALUES (:group_type, :group_name, :custom_data, :list_order)
        ";

        // first, insert the groups for the upcoming field types
        $db->query($group_query);
        $db->bindAll(array(
            "group_type" => "field_types",
            "group_name" => "{\$LANG.phrase_standard_fields}",
            "custom_data" => "",
            "list_order" => 1
        ));
        try {
            $db->execute();
        } catch (Exception $e) {
            return array(false, "Problem inserting list group item #1: " . $e->getMessage());
        }
        $group1_id = $db->getInsertId();


        $db->query($group_query);
        $db->bindAll(array(
            "group_type" => "field_types",
            "group_name" => "{\$LANG.phrase_special_fields}",
            "custom_data" => "",
            "list_order" => 2
        ));

        try {
            $db->execute();
        } catch (Exception $e) {
            CoreFieldTypes::rollbackNewInstallation();
            return array(false, "Problem inserting list group item #2: " . $e->getMessage());
        }
        $group2_id = $db->getInsertId();


        // install each field type one-by-one. If anything fails, return immediately and inform the user. This should
        // NEVER occur, because the only time this code is ever executed is when first installing the module
        list($success, $error) = CoreFieldTypes::installFieldType("textbox", $group1_id);
        if (!$success) {
            CoreFieldTypes::rollbackNewInstallation();
            return array($success, $error);
        }
        list($success, $error) = CoreFieldTypes::installFieldType("textarea", $group1_id);
        if (!$success) {
            CoreFieldTypes::rollbackNewInstallation();
            return array($success, $error);
        }
        list($success, $error) = CoreFieldTypes::installFieldType("password", $group1_id);
        if (!$success) {
            CoreFieldTypes::rollbackNewInstallation();
            return array($success, $error);
        }
        list($success, $error) = CoreFieldTypes::installFieldType("dropdown", $group1_id);
        if (!$success) {
            CoreFieldTypes::rollbackNewInstallation();
            return array($success, $error);
        }
        list($success, $error) = CoreFieldTypes::installFieldType("multi_select_dropdown", $group1_id);
        if (!$success) {
            CoreFieldTypes::rollbackNewInstallation();
            return array($success, $error);
        }
        list($success, $error) = CoreFieldTypes::installFieldType("radio_buttons", $group1_id);
        if (!$success) {
            CoreFieldTypes::rollbackNewInstallation();
            return array($success, $error);
        }
        list($success, $error) = CoreFieldTypes::installFieldType("checkboxes", $group1_id);
        if (!$success) {
            CoreFieldTypes::rollbackNewInstallation();
            return array($success, $error);
        }
        list($success, $error) = CoreFieldTypes::installFieldType("date", $group2_id);
        if (!$success) {
            CoreFieldTypes::rollbackNewInstallation();
            return array($success, $error);
        }
        list($success, $error) = CoreFieldTypes::installFieldType("time", $group2_id);
        if (!$success) {
            CoreFieldTypes::rollbackNewInstallation();
            return array($success, $error);
        }
        list($success, $error) = CoreFieldTypes::installFieldType("phone", $group2_id);
        if (!$success) {
            CoreFieldTypes::rollbackNewInstallation();
            return array($success, $error);
        }
        list($success, $error) = CoreFieldTypes::installFieldType("code_markup", $group2_id);
        if (!$success) {
            CoreFieldTypes::rollbackNewInstallation();
            return array($success, $error);
        }

        return array(true, "");
    }


    // changed in 3.0.0. Moved to separate method so the upgrade script can call it
    public static function getEditSubmissionOnloadResources()
    {
        $resources = <<< END
<script src="{\$g_root_url}/global/codemirror/lib/codemirror.js"></script>
<script src="{\$g_root_url}/global/codemirror/mode/xml/xml.js"></script>
<script src="{\$g_root_url}/global/codemirror/mode/css/css.js"></script>
<script src="{\$g_root_url}/global/codemirror/mode/javascript/javascript.js"></script>
<script src="{\$g_root_url}/global/scripts/jquery-ui-timepicker-addon.js"></script>
<script src="{\$g_root_url}/global/fancybox/jquery.fancybox-1.3.4.pack.js"></script>
<link rel="stylesheet" href="{\$g_root_url}/global/codemirror/lib/codemirror.css" type="text/css" media="screen" />
<link rel="stylesheet" href="{\$g_root_url}/global/fancybox/jquery.fancybox-1.3.4.css" type="text/css" media="screen" />
END;
        return $resources;
    }


	/**
	 * Helper method to determine if the installation has already been completed and (optionall) redirect to the login
	 * page if so.
	 * @param bool $redirect
	 * @return bool
	 */
    public static function checkInstallationComplete($redirect = true)
	{
		$config_file_exists = Core::checkConfigFileExists();
		$installation_complete = false;

		if ($config_file_exists) {
			try {
				include(Core::getConfigFilePath());
				$port = isset($g_db_port) ? $g_db_port : null;
				$db = new Database($g_db_hostname, $g_db_name, $port, $g_db_username, $g_db_password, $g_table_prefix);
				$db->query("SELECT setting_value FROM {PREFIX}settings WHERE setting_name = 'installation_complete'");
				$db->execute();
				$installation_complete = $db->fetch(PDO::FETCH_COLUMN) == "yes";
			} catch (Exception $e) {}
		}

		$complete = $config_file_exists && $installation_complete;

		if ($complete && $redirect) {
			header("location: ../");
			exit;
		}

		return $complete;
	}


	// 3.0.15 moved to a single cache folder for all themes/usages. You can now set it via the installation UI. This
	// method is used on each page of the installation script to return the selected location. For people first arriving
	// on the installation script, they'll see an error thrown if the default location (../../cache) isn't writable
	public static function getCacheFolder()
	{
    	$custom_cache_folder = Sessions::get("g_custom_cache_folder");
    	if (isset($custom_cache_folder) && !empty($custom_cache_folder)) {
    		return $custom_cache_folder;
		}

		return realpath(__DIR__ . "/../../cache");
	}


	public static function verifyCustomCacheFolder($customCacheFolder)
	{
    	$data = array();
    	$statusCode = 200;

		$customCacheFolderExists = is_dir($customCacheFolder);
		if ($customCacheFolderExists) {
			$customCacheFolderWritable = is_writable($customCacheFolder);

			// if the custom cache folder is writable, great - create a blank index.html file in it just to prevent
			// servers configured to list the contents
			if ($customCacheFolderWritable) {
				$indexFile = "$customCacheFolder/index.html";
				if (!file_exists($indexFile)) {
					fopen($indexFile, "w");
				}
				$data["cacheFolderWritable"] = true;
				Sessions::set("fti.systemCheckPassed", true);
				Sessions::set("fti.folderSettings.customCacheFolder", $customCacheFolder);
			} else {
				$data["error"] = "invalid_custom_cache_folder_permissions";
				$data["cacheFolderWritable"] = false;
				$statusCode = 400;
			}
		} else {
			$data["cacheFolderWritable"] = false;
			$data["error"] = "invalid_folder";
			$statusCode = 400;
		}

		return array($data, $statusCode);
	}

}

