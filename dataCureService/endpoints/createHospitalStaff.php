<?php
include_once('../inc/portal.php');
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");

$data = json_decode(@file_get_contents("php://input"), true);

$portal = new PortalUtility();

$hospital_id = trim(mysqli_real_escape_string($conn, !empty($data['hospital_id']) ? $data['hospital_id'] : ""));
$staff_name = trim(mysqli_real_escape_string($conn, !empty($data['staff_name']) ? $data['staff_name'] : ""));
$staff_phone = trim(mysqli_real_escape_string($conn, !empty($data['staff_phone']) ? $data['staff_phone'] : ""));
$staff_email = trim(mysqli_real_escape_string($conn, !empty($data['staff_email']) ? $data['staff_email'] : ""));

$user = $portal->create_hospital_staff($conn, $hospital_id,$staff_name,$staff_phone,$staff_email);

echo $user;
