<?php
require_once(dirname(__FILE__) ."/../../php/requiredfile/requiredfile.php");

require_once(dirname(__FILE__) ."/../../template/ceMaterial/CeMaterialController.php");
//echo phpinfo();

$ceMaterialController = new CeMaterialController();

$title = $_REQUEST['title'] ?? [];
$list = $ceMaterialController->search($title);
DataUtils::jsonEncode($list);
