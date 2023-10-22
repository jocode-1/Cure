<?php
include_once('../inc/portal.php');
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");

$data = json_decode(@file_get_contents("php://input"), true);

$portal = new PortalUtility();

$token = $portal->getBearerToken();
$hospital_id = trim(mysqli_real_escape_string($conn, !empty($data['hospital_id']) ? $data['hospital_id'] : ""));
$staff_code =  trim(mysqli_real_escape_string($conn, !empty($data['staff_code']) ? $data['staff_code'] : ""));

echo $portal->fetchStaffProfile($conn, $hospital_id, $staff_code, $token);
