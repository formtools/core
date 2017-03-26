<?php

// old code files
require_once(__DIR__ . "/code/administrator.php");
require_once(__DIR__ . "/code/accounts.php");
require_once(__DIR__ . "/code/clients.php");
require_once(__DIR__ . "/code/emails.php");
require_once(__DIR__ . "/code/fields.php");
require_once(__DIR__ . "/code/field_sizes.php");
require_once(__DIR__ . "/code/field_types.php");
require_once(__DIR__ . "/code/field_validation.php");
require_once(__DIR__ . "/code/files.php");
require_once(__DIR__ . "/code/forms.php");
require_once(__DIR__ . "/code/general.php");
require_once(__DIR__ . "/code/hooks.php");
require_once(__DIR__ . "/code/languages.php");
require_once(__DIR__ . "/code/list_groups.php");
require_once(__DIR__ . "/code/menus.php");
require_once(__DIR__ . "/code/modules.php");
require_once(__DIR__ . "/code/option_lists.php");
require_once(__DIR__ . "/code/settings.php");
require_once(__DIR__ . "/code/submissions.php");
require_once(__DIR__ . "/code/themes.php");
require_once(__DIR__ . "/code/upgrade.php");
require_once(__DIR__ . "/code/validation.php");
require_once(__DIR__ . "/code/views.php");

// these will replace the above
require_once(__DIR__ . "/code/Accounts.class.php");
require_once(__DIR__ . "/code/Administrator.class.php");
require_once(__DIR__ . "/code/Clients.class.php");
require_once(__DIR__ . "/code/Core.class.php");
require_once(__DIR__ . "/code/Database.class.php");
require_once(__DIR__ . "/code/Emails.class.php");
require_once(__DIR__ . "/code/Fields.class.php");
require_once(__DIR__ . "/code/FieldSizes.class.php");
require_once(__DIR__ . "/code/FieldTypes.class.php");
require_once(__DIR__ . "/code/FieldValidation.class.php");
require_once(__DIR__ . "/code/Files.class.php");
require_once(__DIR__ . "/code/Forms.class.php");
require_once(__DIR__ . "/code/General.class.php");
require_once(__DIR__ . "/code/Hooks.class.php");
require_once(__DIR__ . "/code/Installation.class.php");
require_once(__DIR__ . "/code/ListGroups.class.php");
require_once(__DIR__ . "/code/Modules.class.php");
require_once(__DIR__ . "/code/OptionLists.class.php");
require_once(__DIR__ . "/code/Sessions.class.php");
require_once(__DIR__ . "/code/Settings.class.php");
require_once(__DIR__ . "/code/Submissions.class.php");
require_once(__DIR__ . "/code/Templates.class.php");
require_once(__DIR__ . "/code/Themes.class.php");
require_once(__DIR__ . "/code/Translations.class.php");
require_once(__DIR__ . "/code/Upgrade.class.php");
require_once(__DIR__ . "/code/User.class.php");
require_once(__DIR__ . "/code/Views.class.php");

// autoload dependencies
require_once(__DIR__ . "/../vendor/autoload.php");

//if ($config_file_exists && (!isset($g_defer_init_page) || !$g_defer_init_page)) {
//
//    // backward compatibility
//    $port = (!isset($g_db_port)) ? 3306 : $g_db_port;
//
//    $db = new FormTools\Database($g_db_hostname, $g_db_name, $port, $g_db_username, $g_db_password);
//
//    // our Smarty instance, used for rendering the webpages
//    $g_smarty = new \Smarty();
//
//    // load the appropriate language file
//    $g_language = ft_get_ui_language();
//    require_once(__DIR__ . "/lang/{$g_language}.php");
//
//    if (isset($_GET["logout"])) {
//        ft_logout_user();
//    }
//}
