<?php
require_once(dirname(__FILE__) . "/../shell/SyncJobController.php");


$curlController = new SyncJob1Controller();
$curlController->requestPortfolio();