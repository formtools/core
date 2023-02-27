<?php

require_once("../global/library.php");

use FormTools\Core;
use FormTools\General;
use FormTools\Sessions;

Core::init();
Core::$user->checkAuth("admin");

switch ($request["page"])
{
	case "edit_view":
		Sessions::createIfNotExists("inner_tabs", array());
		Sessions::set("inner_tabs.edit_view", $request["edit_view_tab"]);
		General::redirect("./forms/edit/?page=edit_view&view_id={$request["view_id"]}");
		break;
}