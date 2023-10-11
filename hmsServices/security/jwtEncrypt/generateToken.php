<?php
include_once('../../inc/portal.php');
use ReallySimpleJWT\Token;
require 'vendor/autoload.php';
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");

 $data = json_decode(@file_get_contents("php://input"), true);

$portal = new PortalUtility();

  $email =  trim(!empty($data['email']) ? $data['email'] : "");
  $password =  trim(!empty($data['password']) ? $data['password'] : "");
  
if($portal->validateUser($conn,$email,$password) > 0){
$userId = $username . $password;
$secret = 'sec!ReT423*&';
$expiration = time() + 3600;
$issuer = 'localhost';

$token = Token::create($userId, $secret, $expiration, $issuer);
//echo $token;
echo json_encode(array("expiresIn"=>"3600", "tokenType" => "Bearer","accessToken" => 'XP-CF-RMT-8-'.$token));
    
}else{
 echo json_encode(array("responseCode"=>"04", "message" => "invalidCredentials"));

}


/*$token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjoxMiwiZXhwIjoxNjQxODk3Nzc4LCJpc3MiOiJsb2NhbGhvc3QiLCJpYXQiOjE2NDE4OTQxNzh9.NvBEaZ63XU9Ljt_CEgT44m8Wv_waFxvRXAsc4NwDab4';
$secret = 'sec!ReT423*&';

$result = Token::validate($token, $secret);
echo $result;*/