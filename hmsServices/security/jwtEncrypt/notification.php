<?php
include_once("inc/portal.php");
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
//$getHeaders = apache_request_headers();

$portal = new PortalUtility();

//$token = $portal->getBearerToken();
//$validate = $portal->validateToken($token);
//echo $validate;
//if (empty($validate)) {
//    echo json_encode(array("Response_code" => "03", "response_descr" => "invalid token"));
//} else {

    
//}
