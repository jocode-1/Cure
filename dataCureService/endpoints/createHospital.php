<?php
include_once('../inc/portal.php');
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");

$data = json_decode(@file_get_contents("php://input"), true);

$portal = new PortalUtility();

$hospital_id = trim(mysqli_real_escape_string($conn, !empty($data['hospital_id']) ? $data['hospital_id'] : ""));
$hospital_name = trim(mysqli_real_escape_string($conn, !empty($data['hospital_name']) ? $data['hospital_name'] : ""));
$hospital_email = trim(mysqli_real_escape_string($conn, !empty($data['hospital_email']) ? $data['hospital_email'] : ""));
$hospital_password = trim(mysqli_real_escape_string($conn, !empty($data['hospital_password']) ? $data['hospital_password'] : ""));
$hospital_address = trim(mysqli_real_escape_string($conn, !empty($data['hospital_address']) ? $data['hospital_address'] : ""));

$email_exists = $portal->checkHospitalExists($conn, $hospital_email);

if ($email_exists) {
    $error = array('error' => 'Email already exists');
    echo json_encode($error);
} else {
    $user = $portal->create_merchant($conn, $hospital_id, $hospital_name, $hospital_email, $hospital_password, $hospital_address);
    echo $user;
}
