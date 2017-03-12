<?php

// include all code files
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
require_once(__DIR__ . "/code/sessions.php");
require_once(__DIR__ . "/code/settings.php");
require_once(__DIR__ . "/code/submissions.php");
require_once(__DIR__ . "/code/themes.php");
require_once(__DIR__ . "/code/upgrade.php");
require_once(__DIR__ . "/code/validation.php");
require_once(__DIR__ . "/code/views.php");

// new code in 2.3 (will replace the above)
require_once(__DIR__ . "/code/Accounts.class.php");
require_once(__DIR__ . "/code/Core.class.php");
require_once(__DIR__ . "/code/Database.class.php");
require_once(__DIR__ . "/code/General.class.php");
require_once(__DIR__ . "/code/Installation.class.php");
require_once(__DIR__ . "/code/Settings.class.php");
require_once(__DIR__ . "/code/Translations.class.php");

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
