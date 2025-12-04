<?php
require_once(dirname(__FILE__) . "/../shell/SyncJobController.php");


$curlController = new SyncJobController();
$curlController->fixQdCertificate();