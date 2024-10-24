<?php
require_once(dirname(__FILE__) . "/ProductSkuController.php");


$productSkuController = new ProductSkuController("test");
$productSkuController->updatePaProductAndDetail("UpdatePaProduct.xlsx");