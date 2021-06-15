<?php
//PREFLIGHT CONTROLS
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Authorization, Content-Type,Accept, Origin");
    http_response_code(200);
    exit(0);
}
include_once("main.php");
main();
?>