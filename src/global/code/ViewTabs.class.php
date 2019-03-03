<?php


namespace FormTools;


class ViewTabs
{

    /**
     * Returns all tab information for a particular form view. If the second parameter is
     * set to true.
     *
     * @param integer $view_id the unique view ID
     * @return array the array of tab info, ordered by tab_order
     */
    public static function getViewTabs($view_id, $return_non_empty_tabs_only = false)
    {
        $db = Core::$db;

        $db->query("
            SELECT *
            FROM   {PREFIX}view_tabs
            WHERE  view_id = :view_id
            ORDER BY tab_number
        ");
        $db->bind("view_id", $view_id);
        $db->execute();

        $tab_info = array();
        foreach ($db->fetchAll() as $row) {
            if ($return_non_empty_tabs_only && empty($row["tab_label"])) {
                continue;
            }
            $tab_info[$row["tab_number"]] = array("tab_label" => $row["tab_label"]);
        }

        extract(Hooks::processHookCalls("end", compact("view_id", "tab_info"), array("tab_info")), EXTR_OVERWRITE);

        return $tab_info;
    }


    public static function deleteViewTabs($view_id) {
        $db = Core::$db;

        $db->query("DELETE FROM {PREFIX}view_tabs WHERE view_id = :view_id");
        $db->bind("view_id", $view_id);
        $db->execute();
    }


    /**
     * Verbose name, but this function returns a hash of group_id => tab number for a particular View. In
     * other words, it looks at the View field groups to find out which tab each one belongs to.
     *
     * @return array
     */
    public static function getViewFieldGroupTabs($view_id)
    {
        $db = Core::$db;

        $db->query("
            SELECT group_id, custom_data
            FROM   {PREFIX}list_groups
            WHERE  group_type = :group_type
        ");
        $db->bind("group_type", "view_fields_{$view_id}");
        $db->execute();

        $map = array();
        foreach ($db->fetchAll() as $row) {
            $map[$row["group_id"]] = $row["custom_data"];
        }

        return $map;
    }


    /**
     * Called by the Views::updateView function; updates the tabs available in the View.
     *
     * @param integer $view_id
     * @param array $info
     * @return array [0]: true/false (success / failure)
     *               [1]: message string
     */
    public static function updateViewTabs($view_id, $info)
    {
        $db = Core::$db;

        for ($i=1; $i<=6; $i++) {
            $db->query("UPDATE {PREFIX}view_tabs SET tab_label = :tab_label WHERE view_id = :view_id AND tab_number = :tab_number");
            $db->bindAll(array(
                "tab_label" => $info["tabs"][$i-1],
                "view_id" => $view_id,
                "tab_number" => $i
            ));
            $db->execute();
        }

        return array(true, Core::$L["notify_form_tabs_updated"]);
    }


    public static function addDefaultTabs($view_id)
    {
        $tab_labels = array(Core::$L["phrase_default_tab_label"], "", "", "", "", "");
        ViewTabs::addTabs($view_id, $tab_labels);
    }


    public static function addTabs($view_id, $tab_labels) {
        $db = Core::$db;

        for ($i=0; $i<6; $i++) {
            $db->query("INSERT INTO {PREFIX}view_tabs (view_id, tab_number, tab_label) VALUES (:view_id, :tab_number, :tab_label)");
            $db->bindAll(array(
                "tab_label"  => $tab_labels[$i],
                "view_id"    => $view_id,
                "tab_number" => ($i+1)
            ));
            $db->execute();
        }
    }
}
