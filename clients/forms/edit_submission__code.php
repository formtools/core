<?php

/**
 * New idea added in 2.1.0 which we'll try out. A number of the page code pages are getting very overbloated and
 * hard to read, but the functionality can't really be extracted into the code library files because they're
 * only useful to that one page. Instead, we'll try adding a corresponding *__code.php page here to contain all
 * encapsulatable functionality.
 *
 * Maybe once it's been abstracted here, we can see common usage for it and move to the library files.
 */


/**
 * Gets the << prev, next >> etc. link HTML for the current submission.
 */
function _ft_code_get_link_html($form_id, $view_id, $submission_id, $results_per_page)
{
  global $LANG;

  // defaults! As of 2.1.0, the navigation always appears. This is better for consistencies sake
  $previous_link_html       = "<span class=\"light_grey\">{$LANG['word_previous_leftarrow']}</span>";
  $next_link_html           = "<span class=\"light_grey\">{$LANG['word_next_rightarrow']}</span>";
  $search_results_link_html = "<a href=\"index.php?form_id=$form_id\">{$LANG['phrase_back_to_search_results']}</a>";

  if (isset($_SESSION["ft"]["form_{$form_id}_view_{$view_id}_submissions"]) && !empty($_SESSION["ft"]["form_{$form_id}_view_{$view_id}_submissions"]))
  {
    $php_self = ft_get_clean_php_self();
    $submission_ids = $_SESSION["ft"]["form_{$form_id}_view_{$view_id}_submissions"];
    $current_sub_id_index = array_search($submission_id, $submission_ids);

    if (!empty($current_sub_id_index) || $current_sub_id_index === 0)
    {
      // PREVIOUS link
      if ($submission_ids[0] != $submission_id && $current_sub_id_index != 0)
      {
        $previous_submission_id = $submission_ids[$current_sub_id_index - 1];
        $previous_link_html = "<a href=\"$php_self?form_id=$form_id&view_id=$view_id&submission_id=$previous_submission_id\">{$LANG['word_previous_leftarrow']}</a>";
      }

      $submission_ids_per_page = array_chunk($submission_ids, 10);

      $return_page = 1;
      for ($i=0; $i<count($submission_ids_per_page); $i++)
      {
        if (in_array($submission_id, $submission_ids_per_page[$i]))
        {
          $return_page = $i+1;
          break;
        }
      }

      // NEXT link
      if ($submission_ids[count($submission_ids) - 1] != $submission_id)
      {
        $next_submission_id = $submission_ids[$current_sub_id_index + 1];
        $next_link_html = "<a href=\"$php_self?form_id=$form_id&view_id=$view_id&submission_id=$next_submission_id\">{$LANG['word_next_rightarrow']}</a>";
      }
    }
  }

  return array($previous_link_html, $search_results_link_html, $next_link_html);
}


/**
 * This figures out what View is currently being used.
 *
 * @param array $request the POST/GET merge
 * @param integer $form_id
 */
function _ft_code_get_view($request, $form_id)
{
  if (isset($request["view_id"]))
  {
    $view_id = $request["view_id"];
    $_SESSION["ft"]["form_{$form_id}_view_id"] = $view_id;
  }
  else
  {
    $view_id = isset($_SESSION["ft"]["form_{$form_id}_view_id"]) ? $_SESSION["ft"]["form_{$form_id}_view_id"] : "";
  }

  // if the View ID isn't set, here - they probably just linked to the page directly from an email, module
  // or elsewhere in the script. For this case, find and use the default View
  if (empty($view_id))
  {
    $view_id = ft_get_default_view($form_id);
  }

  return $view_id;
}
