<?php

// autoload external dependencies
require_once(__DIR__ . "/../vendor/autoload.php");

// Form Tools classes / code
require_once(__DIR__ . "/code/Accounts.class.php");
require_once(__DIR__ . "/code/Administrator.class.php");
require_once(__DIR__ . "/code/Clients.class.php");
require_once(__DIR__ . "/code/Constants.class.php");
require_once(__DIR__ . "/code/Core.class.php");
require_once(__DIR__ . "/code/CoreFieldTypes.class.php");
require_once(__DIR__ . "/code/Database.class.php");
require_once(__DIR__ . "/code/DatabaseSessions.class.php");
require_once(__DIR__ . "/code/Emails.class.php");
require_once(__DIR__ . "/code/Errors.class.php");
require_once(__DIR__ . "/code/Fields.class.php");
require_once(__DIR__ . "/code/FieldOptions.class.php");
require_once(__DIR__ . "/code/FieldSettings.class.php");
require_once(__DIR__ . "/code/FieldSizes.class.php");
require_once(__DIR__ . "/code/FieldTypes.class.php");
require_once(__DIR__ . "/code/FieldValidation.class.php");
require_once(__DIR__ . "/code/Files.class.php");
require_once(__DIR__ . "/code/Forms.class.php");
require_once(__DIR__ . "/code/General.class.php");
require_once(__DIR__ . "/code/Hooks.class.php");
require_once(__DIR__ . "/code/Installation.class.php");
require_once(__DIR__ . "/code/ListGroups.class.php");
require_once(__DIR__ . "/code/Menus.class.php");
require_once(__DIR__ . "/code/Module.abstract.class.php");
require_once(__DIR__ . "/code/Modules.class.php");
require_once(__DIR__ . "/code/ModuleMenu.class.php");
require_once(__DIR__ . "/code/OptionLists.class.php");
require_once(__DIR__ . "/code/OmitLists.class.php");
require_once(__DIR__ . "/code/Pages.class.php");
require_once(__DIR__ . "/code/polyfills.php");
require_once(__DIR__ . "/code/Schemas.class.php");
require_once(__DIR__ . "/code/Sessions.class.php");
require_once(__DIR__ . "/code/Settings.class.php");
require_once(__DIR__ . "/code/Submissions.class.php");
require_once(__DIR__ . "/code/Templates.class.php");
require_once(__DIR__ . "/code/Themes.class.php");
require_once(__DIR__ . "/code/Translations.class.php");
require_once(__DIR__ . "/code/Upgrade.class.php");
require_once(__DIR__ . "/code/User.class.php");
require_once(__DIR__ . "/code/validation.php");
require_once(__DIR__ . "/code/Views.class.php");
require_once(__DIR__ . "/code/ViewColumns.class.php");
require_once(__DIR__ . "/code/ViewFields.class.php");
require_once(__DIR__ . "/code/ViewFilters.class.php");
require_once(__DIR__ . "/code/ViewTabs.class.php");
require_once(__DIR__ . "/code/field_types/Checkbox.class.php");
require_once(__DIR__ . "/code/field_types/Code.class.php");
require_once(__DIR__ . "/code/field_types/Date.class.php");
require_once(__DIR__ . "/code/field_types/Dropdown.class.php");
require_once(__DIR__ . "/code/field_types/MultiSelect.class.php");
require_once(__DIR__ . "/code/field_types/Password.class.php");
require_once(__DIR__ . "/code/field_types/Phone.class.php");
require_once(__DIR__ . "/code/field_types/Radio.class.php");
require_once(__DIR__ . "/code/field_types/Textarea.class.php");
require_once(__DIR__ . "/code/field_types/Textbox.class.php");
require_once(__DIR__ . "/code/field_types/Time.class.php");

// convenience var, used in various places where we don't care if the data arrived via the query string or a form post
$request = array_merge($_POST, $_GET);
