<?php



/**
 * Added in 2.1.0. This lets modules add an icon to a "quicklink" icon row on the Submission Listing page. To add it,
 * they need to define a hook call and return a $quicklinks hash with the following keys:
 *   icon_url
 *   alt_text
 *
 * @param $context "admin" or "client"
 */
function ft_display_submission_listing_quicklinks($context, $page_data)
{
	global $g_root_url;

	$quicklinks = array();
	extract(Hooks::processHookCalls("main", compact("context"), array("quicklinks"), array("quicklinks")), EXTR_OVERWRITE);

	if (empty($quicklinks))
		return "";

	echo "<ul id=\"ft_quicklinks\">";

	$num_quicklinks = count($quicklinks);
	for ($i=0; $i<$num_quicklinks; $i++)
	{
		$classes = array();
		if ($i == 0)
			$classes[] = "ft_quicklinks_first";
		if ($i == $num_quicklinks - 1)
			$classes[] = "ft_quicklinks_last";

		$class = implode(" ", $classes);

		$quicklink_info = $quicklinks[$i];
		$icon_url       = isset($quicklink_info["icon_url"]) ? $quicklink_info["icon_url"] : "";
		$title_text     = isset($quicklink_info["title_text"]) ? $quicklink_info["title_text"] : "";
		$onclick        = isset($quicklink_info["onclick"]) ? $quicklink_info["onclick"] : "";
		$title_text = htmlspecialchars($title_text);

		if (empty($icon_url))
			continue;

		echo "<li class=\"$class\" onclick=\"$onclick\"><img src=\"$icon_url\" title=\"$title_text\" /></li>\n";
	}

	echo "</ul>";
}
