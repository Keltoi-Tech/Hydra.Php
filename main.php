<?php
include_once('util.php');
include_once("hydra/index.php");
include_once("persistence/index.php");
include_once("model/index.php");
include_once("viewSet/index.php");
include_once("auth.php");
include_once("request.php");
include_once("response.php");
include_once("rest.php");

use hydra\Config;
use persistence\Provider;
use viewSet\ViewSetFactory;
use net\{Request,Response};
use api\Rest;


function main(){
    $request = Request::getInstance([
        "Access-Control-Allow-Origin: *",
        "Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS",
        "Access-Control-Allow-Headers: Authorization, Content-Type,Accept, Origin"
    ]);
    
    $provider = Provider::getInstance("provider.json");
    $config = Config::getInstance("config.json",$request->getEntity());
    $auth = $request->getAuth();
    
    $viewSet = ViewSetFactory::getInstance($config,$provider,$auth);
    $rest = Rest::getInstance($request,$viewSet);
    $response = Response::getInstance($rest->run());
    
    echo json_encode($response,JSON_PRETTY_PRINT);
    
    $provider=null;
    $response = null;
    $viewSet = null;
    $rest = null;
    $result=null;
}
?>