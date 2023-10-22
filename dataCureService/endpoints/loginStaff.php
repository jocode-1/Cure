<?php
include_once('../inc/portal.php');
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token , Authorization');
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");

$data = json_decode(@file_get_contents("php://input"), true);

$portal = new PortalUtility();

$hospital_id = trim(mysqli_real_escape_string($conn, !empty($data['hospital_id']) ? $data['hospital_id'] : ""));
$staff_email =  trim(mysqli_real_escape_string($conn, !empty($data['staff_email']) ? $data['staff_email'] : ""));
$staff_password =  trim(mysqli_real_escape_string($conn, !empty($data['staff_password']) ? $data['staff_password'] : ""));



echo $portal->login_hospital_staff($conn, $hospital_id, $staff_email, $staff_password);
