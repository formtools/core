<?php

use FormTools\Hooks;


/**
 * Added in 2.1.6. This can be used for conditional logic to determine whether or not a module overrides
 * a particular chunk of Smarty content.
 *
 * @param string $hook
 */
function smarty_modifier_hook_call_defined($hook)
{
    $hook_calls = Hooks::getHookCalls($hook, "template", "");
    return !empty($hook_calls);
}
