<?php

namespace FormTools;

use Smarty, Smarty_Security;


class SecureSmarty extends Smarty {

    public function __construct() {
        parent::__construct();

        $securityPolicy = new Smarty_Security($this);
        //$securityPolicy->php_handling = Smarty::PHP_ALLOW;
        $securityPolicy->disabled_tags = array("exec", "system");

        $this->enableSecurity($securityPolicy);
    }
}
