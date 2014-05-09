<?php

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.clients_dropdown
 * Type:     function
 * Name:     form_dropdown
 * Purpose:  generates a dropdown of all clients in the database (ordered by client surname). This is
 *           used for both single or multiple (i.e. more than one rows visible) dropdowns. This function
 *           receives a list of client IDs that are associated with this form.
 * -------------------------------------------------------------
 */
function smarty_function_clients_dropdown($params, &$smarty)
{
  global $LANG;

  $default_value = (isset($params["default"])) ? $params["default"] : "";
  $onchange      = (isset($params["onchange"])) ? $params["onchange"] : "";
  $style         = (isset($params["style"])) ? $params["style"] : "";
  $class         = (isset($params["class"])) ? $params["class"] : "";
  $name_id       = (isset($params["name_id"])) ? $params["name_id"] : "";
  $include_blank_option = (isset($params["include_blank_option"])) ? $params["include_blank_option"] : false;
  $blank_option         = (isset($params["blank_option"])) ? $params["blank_option"] : $LANG["phrase_please_select"];
  $blank_option_is_optgroup = (isset($params["blank_option_is_optgroup"])) ? $params["blank_option_is_optgroup"] : false;
  $force_show_blank_option  = (isset($params["force_show_blank_option"])) ? $params["force_show_blank_option"] : false;

  // for MULTIPLE item dropdown lists
  $multiple         = (isset($params["multiple"])) ? $params["multiple"] : "";
  $multiple_action  = (isset($params["multiple_action"])) ? $params["multiple_action"] : ""; // hide / show
  $size             = (isset($params["size"])) ? $params["size"] : "";
  $selected_clients = (isset($params["clients"])) ? $params["clients"] : array();

  // if this option is set, it only shows those clients in the array
  $only_show_clients = (isset($params["only_show_clients"])) ? $params["only_show_clients"] : array();

  // this option tells the function that if there's only a single client, display it as straight text
  // rather than in a dropdown. Only compatible with the non-multiple dropdown list
  $display_single_client_as_text = (isset($params["display_single_client_as_text"])) ? $params["display_single_client_as_text"] : false;

  $attributes = array(
    "id"   => $name_id,
    "name" => $name_id,
    "class" => $class,
    "onchange" => $onchange,
    "style" => $style,
    "multiple" => $multiple,
    "size" => $size
      );

  $attribute_str = "";
  while (list($key, $value) = each($attributes))
  {
    if (!empty($value))
      $attribute_str .= " $key=\"$value\"";
  }

  $all_clients = ft_get_client_list();
  $rows = array();

  foreach ($all_clients as $client)
  {
    $account_id = $client["account_id"];

    // if this is multiple dropdown list, figure out whether we need to show the this particular client
    // based on the list of selecteds client passed in
    if (!empty($multiple_action))
    {
      if ($multiple_action == "hide" && in_array($account_id, $selected_clients))
        continue;

      if ($multiple_action == "show" && !in_array($account_id, $selected_clients))
        continue;
    }

    if (!empty($only_show_clients) && !in_array($account_id, $only_show_clients))
      continue;


    $first_name = $client["first_name"];
    $last_name  = $client["last_name"];
    $name = "$first_name $last_name";

    $rows[] = array("account_id" => $account_id, "name" => $name);
  }

  $html = "";
  if (count($rows) == 1 && $display_single_client_as_text && !$force_show_blank_option)
  {
    $html = $rows[0]["name"];
  }
  else
  {
    $options = array();

    if ($include_blank_option)
    {
      if ($blank_option_is_optgroup)
        $options[] = "<optgroup label=\"$blank_option\">";
      else
        $options[] = "<option value=\"\">$blank_option</option>";
    }

    foreach ($rows as $row_info)
      $options[] = "<option value=\"{$row_info["account_id"]}\" " . (($default_value == $row_info["account_id"]) ? "selected" : "") . ">{$row_info["name"]}</option>";

    if ($include_blank_option && $blank_option_is_optgroup)
      $options[] = "</optgroup>";

    $html = "<select $attribute_str>" . join("\n", $options) . "</select>";
  }

  return $html;
}
