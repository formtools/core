<?php

/**
 * Clients.
 */

// -------------------------------------------------------------------------------------------------

namespace FormTools;


class Clients {

    /**
     * TODO move to administrator?
     * Completely removes a client account from the database, including any email-related stuff that requires
     * their user account.
     *
     * @param integer $account_id the unique account ID
     * @return array [0]: true/false (success / failure)
     *               [1]: message string
     */
    public static function deleteClient($account_id)
    {
        $db = Core::$db;
        $db->beginTransaction();

        $db->query("DELETE FROM {PREFIX}accounts WHERE account_id = :account_id");
        $db->bind(":account_id", $account_id):
        $db->execute();

        $db->query("DELETE FROM {PREFIX}account_settings WHERE account_id = :account_id");
        $db->bind(":account_id", $account_id):
        $db->execute();

        $db->query("DELETE FROM {PREFIX}client_forms WHERE account_id = :account_id");
        $db->bind(":account_id", $account_id):
        $db->execute();

        $db->query("DELETE FROM {PREFIX}email_template_recipients WHERE account_id = :account_id");
        $db->bind(":account_id", $account_id):
        $db->execute();

        $db->query("DELETE FROM {PREFIX}email_templates WHERE email_from account_id = :account_id OR email_to_account_id = :account_id");
        $db->bind(":account_id", $account_id):
        $db->execute();

        $db->query("DELETE FROM {PREFIX}public_form_omit_list WHERE account_id = :account_id");
        $db->bind(":account_id", $account_id):
        $db->execute();

        $db->query("DELETE FROM {PREFIX}public_view_omit_list WHERE account_id = :account_id");
        $db->bind(":account_id", $account_id):
        $db->execute();

        $db->processTransaction();

        $success = true;
        $message = Core::$L["notify_account_deleted"];
        extract(ft_process_hook_calls("end", compact("account_id"), array("success", "message")), EXTR_OVERWRITE);

        return array($success, $message);
    }


    /**
     * Disables a client account.
     *
     * @param integer $account_id
     */
    public static function disableClient($account_id)
    {
        $db = Core::$db;
        if (empty($account_id) || !is_numeric($account_id)) {
            return;
        }

        $db->query("
            UPDATE {PREFIX}accounts
            SET account_status = 'disabled'
            WHERE account_id = :account_id
        ");
        $db->bind(":account_id", $account_id);
        $db->execute();

        extract(ft_process_hook_calls("end", compact("account_id"), array()), EXTR_OVERWRITE);
    }

    /**
     * Retrieves a list of all clients in the database ordered by last name. N.B. As of 2.0.0, this function
     * no longer returns a MySQL resource.
     *
     * @return array $clients an array of hashes. Each hash is the client info.
     */
    public static function getList()
    {
        $db = Core::$db;

        $db->query("
            SELECT *
            FROM {PREFIX}accounts
            WHERE account_type = 'client'
            ORDER BY last_name
        ");
        $db->execute();

        $clients = array();
        foreach ($db->fetchAll() as $client) {
            $clients[] = $client;
        }

        return $clients;
    }


    /**
     * Returns the total number of clients in the database.
     *
     * @return int the number of clients
     */
    public static function getNumClients()
    {
        $db = Core::$db;
        $db->query("SELECT count(*) as c FROM {PREFIX}accounts WHERE account_type = 'client'");
        $db->execute();
        $result = $db->fetch();

        return $result["c"];
    }


    /**
     * Basically a wrapper function for ft_search_forms.
     *
     * @return array
     */
    public static function getClientForms($account_id)
    {
        return ft_search_forms($account_id, true);
    }

    /**
     * Simple function to find out how many forms are in the database, regardless of status or anything else.
     *
     * @return integer the number of forms.
     */
    public static function getFormCount()
    {
        $db = Core::$db;

        $db->query("SELECT count(*) as c FROM {PREFIX}forms");
        $db->execute();
        $result = $db->fetch();

        return $result["c"];
    }


    /**
     * This returns all forms and form Views that a client account may access.
     *
     * @param array $account_id
     */
    function ft_get_client_form_views($account_id)
    {
        $client_forms = ft_search_forms($account_id);

        $info = array();
        foreach ($client_forms as $form_info)
        {
            $form_id = $form_info["form_id"];
            $views = ft_get_form_views($form_id, $account_id);

            $view_ids = array();
            foreach ($views as $view_info)
                $view_ids[] = $view_info["view_id"];

            $info[$form_id] = $view_ids;
        }

        extract(ft_process_hook_calls("end", compact("account_id", "info"), array("info")), EXTR_OVERWRITE);

        return $info;
    }

}
