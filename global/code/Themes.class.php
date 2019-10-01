<?php


/**
 * This file defines all methods relating to Form Tools themes. Note: the Theme setting tab/page
 * is updated via Settings::updateThemeSettings.
 *
 * @copyright Benjamin Keen 2018
 * @author Benjamin Keen <ben.keen@gmail.com>
 * @package 3-0-x
 * @subpackage Themes
 */

namespace FormTools;

use Exception;


class Themes
{

	public static function getList($enabled_only = false)
	{
		$db = Core::$db;
		$enabled_only_clause = ($enabled_only) ? "WHERE is_enabled = 'yes'" : "";

		$db->query("
            SELECT * 
            FROM {PREFIX}themes
            $enabled_only_clause
            ORDER BY theme_name
        ");

		$db->execute();
		$theme_info = array();
		foreach ($db->fetchAll() as $theme) {
			$theme_info[] = $theme;
		}

		extract(Hooks::processHookCalls("end", compact("theme_info"), array("theme_info")), EXTR_OVERWRITE);
		return $theme_info;
	}

	/**
	 * Returns all info about a particular theme.
	 *
	 * @param integer $theme_id
	 * @return array
	 */
	public static function getTheme($theme_id)
	{
		$db = Core::$db;

		$db->query("SELECT * FROM {PREFIX}themes WHERE theme_id = :theme_id");
		$db->bind("theme_id", $theme_id);
		$db->execute();
		$theme_info = $db->fetch();

		extract(Hooks::processHookCalls("end", compact("theme_id", "theme_info"), array("theme_info")), EXTR_OVERWRITE);

		return $theme_info;
	}


	/**
	 * The counterpart to getTheme. Returns everything about a theme by the theme folder instead of theme ID.
	 *
	 * @param string $theme_folder
	 * @return array
	 */
	public static function getThemeByThemeFolder($theme_folder)
	{
		$db = Core::$db;

		$db->query("SELECT * FROM {PREFIX}themes WHERE theme_folder = :theme_folder");
		$db->bind("theme_folder", $theme_folder);
		$db->execute();
		$theme_info = $db->fetch();

		extract(Hooks::processHookCalls("end", compact("theme_folder", "theme_info"), array("theme_info")), EXTR_OVERWRITE);

		return $theme_info;
	}


	/**
	 * Updates the list of themes in the database by examining the contents of the /themes folder.
	 */
	public static function updateThemeList()
	{
		$db = Core::$db;
		$LANG = Core::$L;

		// empty the themes table
		self::emptyThemesTable();

		$themes = self::getUninstalledThemes();

		try {
			foreach ($themes as $theme_info) {
				$db->query("
					INSERT INTO {PREFIX}themes (theme_folder, theme_name, uses_swatches, swatches, author, theme_link, 
					  description, is_enabled, theme_version)
					VALUES (:folder, :theme_name, :theme_uses_swatches, :swatches, :theme_author, :theme_link, 
					  :theme_description, :is_enabled, :theme_version)
				");
				$db->bindAll(array(
					"folder" => $theme_info["theme_folder"],
					"theme_name" => $theme_info["theme_name"],
					"theme_uses_swatches" => $theme_info["theme_uses_swatches"],
					"swatches" => $theme_info["swatches"],
					"theme_author" => $theme_info["theme_author"],
					"theme_link" => $theme_info["theme_link"],
					"theme_description" => $theme_info["theme_description"],
					"is_enabled" => "yes",
					"theme_version" => $theme_info["theme_version"]
				));
				$db->execute();
			}
		} catch (Exception $e) {
			return array(false, $e->getMessage());
		}

		extract(Hooks::processHookCalls("end", array(), array("success", "message")), EXTR_OVERWRITE);

		return array(true, $LANG["notify_theme_list_updated"]);
	}

	public static function emptyThemesTable()
	{
		Core::$db->query("TRUNCATE {PREFIX}themes");
		Core::$db->execute();
	}

	private static function getUninstalledThemes()
	{
		$root_dir = Core::getRootDir();
		$themes_folder = "$root_dir/themes";
		$dh = opendir($themes_folder);
#
		// if we couldn't open the themes folder, it probably doesn't exist
		if (!$dh) {
			return array(); // TODO
		}

		$theme_data = array();
		while (($folder = readdir($dh)) !== false) {
			if (!is_dir("$themes_folder/$folder") || $folder == "." || $folder == "..") {
				continue;
			}

			$summary_file = "$themes_folder/$folder/about/theme.php";
			$thumbnail = "$themes_folder/$folder/about/thumbnail.gif";

			if (!is_file($summary_file) || !is_file($thumbnail)) {
				continue;
			}

			$info = self::getThemeInfoFileContents($summary_file);

			// if the theme name is not defined, skip it
			if (empty($info["theme_name"])) {
				continue;
			}

			$theme_uses_swatches = $info["theme_uses_swatches"];
			$swatches = "";
			if ($theme_uses_swatches == "yes") {
				$swatch_info = array();

				foreach ($info["theme_swatches"] as $key => $value) {
					$swatch_info[] = "$key,$value";
				}
				$swatches = implode("|", $swatch_info);
			}

			$theme_data[] = array(
				"theme_folder" => $folder,
				"theme_name" => $info["theme_name"],
				"theme_author" => $info["theme_author"],
				"theme_link" => $info["theme_link"],
				"theme_description" => $info["theme_description"],
				"theme_version" => $info["theme_version"],
				"theme_uses_swatches" => $info["theme_uses_swatches"],
				"swatches" => $swatches
			);
		}
		closedir($dh);

		return $theme_data;
	}


	/**
	 * Used by every page in the Form Tools UI to render a page.
	 *
	 * Note: if the page isn't found in the current theme, it defaults to the "default" theme. This is important (and
	 * handy!): it means that only the default theme needs to contain every file. Other themes can just define whatever
	 * pages they want to override and omit the others.
	 * @param $template
	 * @param $page_vars
	 * @param string $theme
	 * @param string $swatch
	 * @return string
	 */
	public static function displayPage($template, $page_vars, $theme = "", $swatch = "")
	{
		echo self::getPage($template, $page_vars, $theme, $swatch);
	}


	public static function getPage($template, $page_vars, $theme = "", $swatch = "")
	{
		$theme = (empty($theme) && Core::isUserInitialized()) ? Core::$user->getTheme() : $theme;
		$swatch = (empty($swatch) && Core::isUserInitialized()) ? Core::$user->getSwatch() : $swatch;

		$smarty = Templates::getPageRenderSmarty($theme, $page_vars);

		$smarty->assign("swatch", $swatch);

		if (isset($page_vars["page_url"])) {
			$parent_page_url = Menus::getParentPageUrl($page_vars["page_url"]);
			$smarty->assign("nav_parent_page_url", $parent_page_url);
		}

		extract(Hooks::processHookCalls("main", compact("smarty", "template", "page_vars"), array("smarty")), EXTR_OVERWRITE);

		return $smarty->fetch(self::getSmartyTemplateWithFallback($theme, $template));
	}

	/**
	 * TODO. Combine as much of this with the prev method.
	 *
	 * Loads a theme opening page for a module. This should be used loaded for every page in a
	 * module. It serves the same function as Themes::displayPage, except that it sets the appropriate root
	 * folder for the module and loads the
	 *
	 * @param array $page_vars a hash of information to display / provide to the template.
	 * @param string $theme
	 */
	public static function displayModulePage($module_folder, $template, $page_vars = array(), $theme = "", $swatch = "")
	{
		$root_dir = Core::getRootDir();

		if (empty($theme) && (Sessions::exists("account.theme"))) {
			$theme = Sessions::get("account.theme");
			$swatch = Sessions::getWithFallback("account.swatch", "");
		} elseif (empty($theme)) {
			$settings = Settings::get(array("default_theme", "default_client_swatch"));
			$theme = $settings["default_theme"];
			$swatch = $settings["default_client_swatch"];
		}

		$smarty = Templates::getPageRenderSmarty($theme, $page_vars);
		Sessions::setIfNotExists("account.is_logged_in", false);
		Sessions::setIfNotExists("account.account_type", "");

		// this contains the custom language content of the module, in the language required. It's populated by
		// Modules::initModulePage(), called on every module page
		$module_lang_strings = Modules::getModuleLangFile($module_folder, Core::$user->getLang());
		$LANG[$module_folder] = $module_lang_strings;
		$smarty->assign("L", $module_lang_strings);

		extract(Hooks::processHookCalls("end", compact("account_type", "module_folder"), array()), EXTR_OVERWRITE);

		$settings = Sessions::getWithFallback("settings", array());
		$smarty->assign("settings", $settings);
		$smarty->assign("account", Sessions::get("account"));
		$smarty->assign("swatch", $swatch);

		$module_id = Modules::getModuleIdFromModuleFolder($module_folder);
		$module_nav = ModuleMenu::getMenuItems($module_id, $module_folder);
		$smarty->assign("module_nav", $module_nav);

		// if there's no module title, display the module name. TODO not compatible with languages...
		if (!isset($page_vars["head_title"])) {
			$module_id = Modules::getModuleIdFromModuleFolder($module_folder);
			$module_info = Modules::getModule($module_id);
			$page_vars["head_title"] = $module_info["module_name"];
		}

		// if we need to include custom JS messages in the page, add it to the generated JS. Note: even if the js_messages
		// key is defined but still empty, the General::generateJsMessages function is called, returning the "base" JS - like
		// the JS version of g_root_url. Only if it is not defined will that info not be included. This feature was hacked
		// in 2.1 to support js_messages from a single module file
		$js_messages = "";
		if (isset($page_vars["js_messages"]) || isset($page_vars["module_js_messages"])) {
			$core_js_messages = isset($page_vars["js_messages"]) ? $page_vars["js_messages"] : "";
			$module_js_messages = isset($page_vars["module_js_messages"]) ? $page_vars["module_js_messages"] : "";
			$js_messages = General::generateJsMessages($core_js_messages, $module_lang_strings, $module_js_messages);
		}

		if ((isset($page_vars["head_js"]) && !empty($page_vars["head_js"])) || !empty($js_messages)) {
			$js = "<script type=\"text/javascript\">";
			if (!empty($page_vars["head_js"])) {
				$js .= $page_vars["head_js"] . "\n";
			}
			if (!empty($js_messages)) {
				$js .= $js_messages . "\n";
			}
			$js .= "\n</script>";
			$smarty->assign("head_js", $js);
		}

		// if there's a Smarty folder, import any of its resources
		if (is_dir("$root_dir/modules/$module_folder/smarty_plugins")) {
			$smarty->addPluginsDir("$root_dir/modules/$module_folder/smarty_plugins");
		}

		if (!isset($page_vars["hide_nav_menu"])) {
			$smarty->assign("hide_nav_menu", false);
		}
		if (!isset($page_vars["hide_header_bar"])) {
			$smarty->assign("hide_header_bar", false);
		}

		$smarty->assign("module_folder", $module_folder);

		extract(Hooks::processHookCalls("main", compact("g_smarty", "template", "page_vars"), array("g_smarty")), EXTR_OVERWRITE);

		$smarty->display("$root_dir/modules/$module_folder/$template");
	}


	/**
	 * This function should be called in every $g_smarty->display() call. Instead of directly loading the
	 * desired template, it provides a simple fallback mechanism in case the template doesn't exist. Namely,
	 * theme developers only need
	 *
	 * @param string $theme
	 * @param string $template
	 */
	public static function getSmartyTemplateWithFallback($theme, $template)
	{
		$default_theme = Core::getDefaultTheme();
		$root_dir = Core::getRootDir();

		if (is_file("$root_dir/themes/$theme/$template")) {
			$file = "$root_dir/themes/$theme/$template";
		} else if (is_file("$root_dir/themes/$default_theme/$template")) {
			$file = "$root_dir/themes/$default_theme/$template";
		} else {
			echo "The \"<b>$template</b>\" template could not be located at the following locations:
                  <b>$root_dir/themes/$theme/$template</b> and <b>$root_dir/themes/$default_theme/$template</b>.";
			exit;
		}

		return $file;
	}


	/**
	 * TODO move to administrator... / user?
	 *
	 * This function is provided for theme developers who find themselves in a position where a theme they
	 * create is wonky, and prevents them seeing anything in the UI. It can only be called after having
	 * logged in as an administrator (which will not be affected by a dud theme - even through they may
	 * see nothing after logging in). To call it, they'll need to construct this URL:
	 *
	 *     http://www.yourdomain.com/formtools/admin/settings/index.php?page=themes&theme_override=deepblue
	 *
	 * @param string $theme the name of the theme folder to reset to (e.g. deepblue)
	 */
	public static function resetAdminTheme($theme)
	{
		$db = Core::$db;
		$LANG = Core::$L;

		$admin_id = Sessions::get("account.account_id");

		$db->query("
            UPDATE {PREFIX}accounts
            SET    theme = :theme
            WHERE  account_id = :admin_id
        ");
		$db->bindAll(array(
			"theme" => $theme,
			"admin_id" => $admin_id
		));
		$db->execute();

		Core::$user->setTheme($theme);

		return array(true, $LANG["notify_admin_theme_overridden"]);
	}


	/**
	 * Returns a human-friendly string version of the available theme swatches.
	 *
	 * @param string $str the serialized swatch string found in the theme tables "swatches" field.
	 */
	public static function getThemeSwatchList($str)
	{
		$swatch_list = array();
		$pairs = explode("|", $str);
		foreach ($pairs as $pair) {
			list($swatch, $swatch_label) = explode(",", $pair);
			$swatch_list[] = General::evalSmartyString($swatch_label);
		}
		$swatch_list_str = implode(", ", $swatch_list);

		return $swatch_list_str;
	}


	// --------------------------------------------------------------------------------------------


	/**
	 * Helper function to read the theme's info file (theme.php) values. The only REQUIRED
	 * field if the theme name. If that isn't defined, the theme won't be added to the database.
	 *
	 * @param string $summary_file the full file path and filename
	 */
	private static function getThemeInfoFileContents($summary_file)
	{
		@include($summary_file);
		$vars = get_defined_vars();

		$info["theme_name"] = isset($vars["theme_name"]) ? $vars["theme_name"] : "";
		$info["theme_author"] = isset($vars["theme_author"]) ? $vars["theme_author"] : "";
		$info["theme_author_email"] = isset($vars["theme_author_email"]) ? $vars["theme_author_email"] : "";
		$info["theme_link"] = isset($vars["theme_link"]) ? $vars["theme_link"] : "";
		$info["theme_description"] = isset($vars["theme_description"]) ? $vars["theme_description"] : "";
		$info["theme_version"] = isset($vars["theme_version"]) ? $vars["theme_version"] : "";
		$info["theme_uses_swatches"] = isset($vars["theme_uses_swatches"]) ? $vars["theme_uses_swatches"] : "no";
		$info["theme_swatches"] = isset($vars["theme_swatches"]) ? $vars["theme_swatches"] : array();

		return $info;
	}

}
