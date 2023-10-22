<?php
include_once('../inc/portal.php');
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");

$data = json_decode(@file_get_contents("php://input"), true);

$portal = new PortalUtility();

$merchant_id = trim(mysqli_real_escape_string($conn, !empty($data['merchant_id']) ? $data['merchant_id'] : ""));
$bvn = trim(mysqli_real_escape_string($conn, !empty($data['bvn']) ? $data['bvn'] : ""));
$account_number = trim(mysqli_real_escape_string($conn, !empty($data['account_number']) ? $data['account_number'] : ""));
$bank_name = trim(mysqli_real_escape_string($conn, !empty($data['bank_name']) ? $data['bank_name'] : ""));
$bank_code = trim(mysqli_real_escape_string($conn, !empty($data['bank_code']) ? $data['bank_code'] : ""));
$phone = trim(mysqli_real_escape_string($conn, !empty($data['phone']) ? $data['phone'] : ""));
$email = trim(mysqli_real_escape_string($conn, !empty($data['email']) ? $data['email'] : ""));
$amount = trim(mysqli_real_escape_string($conn, !empty($data['amount']) ? $data['amount'] : ""));
$period = trim(mysqli_real_escape_string($conn, !empty($data['period']) ? $data['period'] : ""));

$gender = trim(mysqli_real_escape_string($conn, !empty($data['gender']) ? $data['gender'] : ""));
$birthdate = trim(mysqli_real_escape_string($conn, !empty($data['birthdate']) ? $data['birthdate'] : ""));

$employer_name  = trim(mysqli_real_escape_string($conn, !empty($data['employer_name']) ? $data['employer_name'] : ""));
$employer_address = trim(mysqli_real_escape_string($conn, !empty($data['employer_address']) ? $data['employer_address'] : ""));
$grade_level = trim(mysqli_real_escape_string($conn, !empty($data['grade']) ? $data['grade'] : ""));
$step = trim(mysqli_real_escape_string($conn, !empty($data['step']) ? $data['step'] : ""));
$lastname = trim(mysqli_real_escape_string($conn, !empty($data['lastname']) ? $data['lastname'] : ""));
$firstname = trim(mysqli_real_escape_string($conn, !empty($data['firstname']) ? $data['firstname'] : ""));
$middlename = trim(mysqli_real_escape_string($conn, !empty($data['middlename']) ? $data['middlename'] : ""));
$urls = trim(mysqli_real_escape_string($conn, !empty($data['passport']) ? $data['passport'] : ""));
$url_022 = trim(mysqli_real_escape_string($conn, !empty($data['idcard']) ? $data['idcard'] : ""));
$url_033 = trim(mysqli_real_escape_string($conn, !empty($data['util']) ? $data['util'] : ""));
$othernames = $firstname. ' '.$middlename;


$fullname  = $lastname. ' '.$othernames;

$status = $grade_level .' - '.$step;



$image_id = substr(str_shuffle(str_repeat("0123456789", 10)), 0, 10);
    $fileName = $image_id.'.png';
    $targetPath = "documents/passport/$fileName";
    $targetPath_02 = "documents/idcard/$fileName";
    $targetPath_03 = "documents/util/$fileName";
	$url = "https://uat.stack.net.ng/dataStackService/endpoints/$targetPath";
	$url_02 = "https://uat.stack.net.ng/dataStackService/endpoints/$targetPath_02";
	$url_03 =  "https://uat.stack.net.ng/dataStackService/endpoints/$targetPath_03";
	file_put_contents($targetPath, base64_decode($urls));
	file_put_contents($targetPath_02, base64_decode($url_022));
	file_put_contents($targetPath_03, base64_decode($url_033));

    
$user = $portal->customer_application($conn, $merchant_id, $fullname, $bank_code,$bank_name, $account_number, $bvn, $phone, $email, $employer_address, $birthdate, $gender, $employer_name, $employer_address, $grade_level, $step, $amount, $period, $url, $url_02, $url_03);
echo $user;

