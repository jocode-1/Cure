<?php
include_once('../inc/portal.php');
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");

$data = json_decode(@file_get_contents("php://input"), true);

$portal = new PortalUtility();

$merchant_id = trim(mysqli_real_escape_string($conn, !empty($data['merchant_id']) ? $data['merchant_id'] : ""));


echo $portal->fetchMerchantDetails($conn, $merchant_id);
