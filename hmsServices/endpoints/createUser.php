<?php
include_once('../inc/portal.php');
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");

$data = json_decode(@file_get_contents("php://input"), true);

$portal = new PortalUtility();

$user_firstname = trim(mysqli_real_escape_string($conn, !empty($data['firstname']) ? $data['firstname'] : ""));
$user_Lastname = trim(mysqli_real_escape_string($conn, !empty($data['lastname']) ? $data['lastname'] : ""));
$user_email = trim(mysqli_real_escape_string($conn, !empty($data['user_email']) ? $data['user_email'] : ""));
$user_phoneNumber = trim(mysqli_real_escape_string($conn, !empty($data['phone_number']) ? $data['phone_number'] : ""));
$user_password = trim(mysqli_real_escape_string($conn, !empty($data['user_password']) ? $data['user_password'] : ""));
$user_country = trim(mysqli_real_escape_string($conn, !empty($data['country']) ? $data['country'] : ""));
$user_address = trim(mysqli_real_escape_string($conn, !empty($data['address']) ? $data['address'] : ""));

echo $portal->CreateUserAccount($conn, $user_firstname, $user_Lastname, $user_email, $user_phoneNumber, $user_password, $user_country, $user_address);