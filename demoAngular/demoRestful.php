<?php
$tmpResponse = new \Slim\Http\Response();
foreach (array_chunk($asinList,200) as $asin){
    $query['asinList'] = implode(',',$asin);
    $this->app->subRequest("GET", '/api/keepa/getProductInfosByAsinList', http_build_query($query), [], [], '', $tmpResponse);
    $asinInfoRes = json_decode($tmpResponse->getBody(), true);
    var_dump($asinInfoRes);
}
