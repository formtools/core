<?php

/**
 * Omit Lists are a handy way to fine-tune access to Form and Views by essentially blacklisting particular users.
 *
 * @copyright Benjamin Keen 2018
 * @author Benjamin Keen <ben.keen@gmail.com>
 * @package 3-0-x
 * @subpackage OmitLists
 */

// ---------------------------------------------------------------------------------------------------------------------


namespace FormTools;

use PDO, Exception;


class OmitLists
{

    /**
     * Returns an array of account IDs of those clients in the omit list for this public form.
     *
     * @param integer $form_id
     * @return array
     */
    public static function getPublicFormOmitList($form_id)
    {
        $db = Core::$db;
        $db->query("
            SELECT account_id
            FROM   {PREFIX}public_form_omit_list
            WHERE  form_id = :form_id
        ");
        $db->bind("form_id", $form_id);
        $db->execute();

        $client_ids = array();
        foreach ($db->fetchAll() as $row) {
            $client_ids[] = $row["account_id"];
        }

        extract(Hooks::processHookCalls("end", compact("clients_id", "form_id"), array("client_ids")), EXTR_OVERWRITE);

        return $client_ids;
    }


    public static function getPublicFormOmitListByAccountId($account_id) {
        if (empty($account_id)) {
            return array();
        }

        $db = Core::$db;

        $db->query("
            SELECT form_id
            FROM {PREFIX}public_form_omit_list
            WHERE account_id = :account_id
        ");
        $db->bind("account_id", $account_id);
        $db->execute();

        return $db->fetchAll(PDO::FETCH_COLUMN);
    }


    public static function deleteFormOmitList($form_id)
    {
        $db = Core::$db;
        $db->query("DELETE FROM {PREFIX}public_form_omit_list WHERE form_id = :form_id");
        $db->bind("form_id", $form_id);
        $db->execute();
    }


    public static function deleteFormOmitListByAccountId($account_id)
    {
        $db = Core::$db;
        $db->query("DELETE FROM {PREFIX}public_form_omit_list WHERE account_id = :account_id");
        $db->bind("account_id", $account_id);
        $db->execute();
    }


    public static function deleteViewOmitListByAccountId($account_id)
    {
        $db = Core::$db;
        $db->query("DELETE FROM {PREFIX}public_view_omit_list WHERE account_id = :account_id");
        $db->bind("account_id", $account_id);
        $db->execute();
    }

}
