<?php

/**
 * @copyright Benjamin Keen 2017
 * @author Benjamin Keen <ben.keen@gmail.com>
 * @package 3-0-x
 * @subpackage Templates
 */

// -------------------------------------------------------------------------------------------------

namespace FormTools;


class Templates
{

    public static function hasRequiredParams($smarty, $allParams, $desiredParams)
    {
        foreach ($desiredParams as $param) {
            if (!isset($allParams[$param]) || empty($allParams[$param])) {
                $smarty->triggerError("assign: missing '$param' parameter.");

                //throw new Exception("assign: missing '$param' parameter.");

                return false;
            }
        }

        return true;
    }
}
