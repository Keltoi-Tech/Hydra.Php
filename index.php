<?php
include_once('util.php');
include_once("concept.php");
include_once("data.php");
include_once("model\index.php");
include_once("viewSet\index.php");
include_once("auth.php");
include_once("request.php");
include_once("rest.php");

use net\Request;
use persistence\Provider;
use api\Rest;

$request = Request::getInstance();
$provider = Provider::getInstance("../php/provider.json");
$entity = $request->getEntity();
$auth = $request->getAuth();

$viewSet = ViewSetFactory::getInstance($entity,$provider,$auth);
$rest = Rest::getInstance($request,$viewSet);
$result = $rest->run();

echo json_encode($result,JSON_PRETTY_PRINT);

$provider=null;
$request = null;
$viewSet = null;
$rest = null;
$result=null;
?>
