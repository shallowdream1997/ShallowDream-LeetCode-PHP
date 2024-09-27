<?php
require_once(dirname(__FILE__) ."/../../php/requiredfile/requiredfile.php");

require_once(dirname(__FILE__) ."/../../template/ceMaterial/CeMaterialController.php");
//echo phpinfo();

$ceMaterialController = new CeMaterialController();

$_id = $_REQUEST['_id'] ?? "";
$res = $ceMaterialController->update($_id);
DataUtils::jsonEncode($res);