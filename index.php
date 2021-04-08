<?php
include_once('util.php');
include_once("concept.php");
include_once("data.php");
include_once("model/index.php");
include_once("viewSet/index.php");
include_once("auth.php");
include_once("request.php");
include_once("response.php");
include_once("rest.php");

use net\{Request,Response};
use persistence\Provider;
use api\Rest;

$request = Request::getInstance([
    header("Access-Control-Allow-Origin:*"),
    header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS")
]);
$provider = Provider::getInstance("../php/user_provider.json");
//$provider = Provider::getInstance("../../provider.json");

$entity = $request->getEntity();
$auth = $request->getAuth();

$viewSet = ViewSetFactory::getInstance($entity,$provider,$auth);
$rest = Rest::getInstance($request,$viewSet);
$response = Response::getInstance($rest->run());

echo json_encode($response,JSON_PRETTY_PRINT);

$provider=null;
$response = null;
$viewSet = null;
$rest = null;
$result=null;
?>