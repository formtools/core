<?php

use FormTools\Core;
use FormTools\General;
use FormTools\Sessions;
use FormTools\Views;


/**
 * TODO [EDIT]. These two __code files can be dropped. This method is used for both admin + client.
 */


/**
 * Gets the << prev, next >> etc. link HTML for the current submission.
 */
function _ft_code_get_link_html($form_id, $view_id, $submission_id, $results_per_page)
{
	$LANG = Core::$L;

	// defaults! As of 2.1.0, the navigation always appears. This is better for consistencies sake
	$previous_link_html       = "<span class=\"light_grey\">{$LANG['word_previous_leftarrow']}</span>";
	$next_link_html           = "<span class=\"light_grey\">{$LANG['word_next_rightarrow']}</span>";
	$search_results_link_html = "<a href=\"index.php?form_id=$form_id\">{$LANG['phrase_back_to_search_results']}</a>";

	$session_key = "form_{$form_id}_view_{$view_id}_submissions";
	if (Sessions::exists($session_key) && Sessions::get($session_key) != "") {
		$php_self = General::getCleanPhpSelf();
		$submission_ids = Sessions::get($session_key);
		$current_sub_id_index = array_search($submission_id, $submission_ids);

		if (!empty($current_sub_id_index) || $current_sub_id_index === 0) {
			// PREVIOUS link
			if ($submission_ids[0] != $submission_id && $current_sub_id_index != 0) {
				$previous_submission_id = $submission_ids[$current_sub_id_index - 1];
				$previous_link_html = "<a href=\"$php_self?form_id=$form_id&view_id=$view_id&submission_id=$previous_submission_id\">{$LANG['word_previous_leftarrow']}</a>";
			}

			//$submission_ids_per_page = array_chunk($submission_ids, 10);
//			$return_page = 1;
//			for ($i=0; $i<count($submission_ids_per_page); $i++)
//			{
//				if (in_array($submission_id, $submission_ids_per_page[$i]))
//				{
//					$return_page = $i+1;
//					break;
//				}
//			}

			// NEXT link
			if ($submission_ids[count($submission_ids) - 1] != $submission_id) {
				$next_submission_id = $submission_ids[$current_sub_id_index + 1];
				$next_link_html = "<a href=\"$php_self?form_id=$form_id&view_id=$view_id&submission_id=$next_submission_id\">{$LANG['word_next_rightarrow']}</a>";
			}
		}
	}

	return array($previous_link_html, $search_results_link_html, $next_link_html);
}
