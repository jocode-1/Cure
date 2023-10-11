<?php

use ReallySimpleJWT\Token;
use function PHPSTORM_META\type;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
// Load Composer's autoloader
require '../vendor/autoload.php';

require 'vendor/autoload.php';
include('dbconnection.php');
$database = new database();
$conn = $database->getConnection();

class PortalUtility
{

    public function validateToken($token)
    {
        $secret = 'sec!ReT423*&';

        $result = Token::validate($token, $secret);
        $converted_res = $result ? 'true' : 'false';
        return '' . $converted_res;
    }

    public function server_logs($log_msg)
    {
        $filename = "server_logs";
        if (!file_exists($filename)) {
            mkdir($filename, 0777, true);
        }
        $log_file_data = $filename . '/log_' . date('d-M-Y') . '.log';
        file_put_contents($log_file_data, $log_msg . "\n", FILE_APPEND);
    }

    public function service_logs($log_msg)
    {

        $filename = "service_logs";
        if (!file_exists($filename)) {
            // create directory/folder uploads.
            mkdir($filename, 0777, true);
        }
        $log_file_data = $filename . '/log_' . date('d-M-Y') . '.log';
        // if you don't add `FILE_APPEND`, the file will be erased each time you add a log
        file_put_contents($log_file_data, $log_msg . "\n", FILE_APPEND);
    }

    public function getIPAddress()
{
    $ip = $_SERVER['REMOTE_ADDR'];

    if (isset($_SERVER['HTTP_CLIENT_IP']) &&!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR']) &&!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }

    return $ip;
}

    public function user_id()
    {
        $uni = substr(str_shuffle(str_repeat("0123456789", 10)), 0, 10);

        return $uni;
    }

    public function fetchUserDetailsById($conn, $user_id)
    {
        $json  = array();
        $sql = "SELECT `user_id`, `user_email`  FROM `users` WHERE user_id = '$user_id'";
        $result = mysqli_query($conn, $sql);
        $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
        return $row;
    }

    public function CreateUserAccount($conn, $user_firstname, $user_Lastname, $user_email, $user_phoneNumber, $user_password, $user_country, $user_address) {

        $status = array();
        $user_id = $this->user_id();
        $userIP = $this->getIPAddress();

        $sql = "INSERT INTO `users`(`user_id`, `user_firstname`, `user_lastname`, `user_email`, `user_phoneNumber`, `user_password`, `user_country`, `user_address`, `user_ipAddress`, `status`) VALUES 
        ('$user_id', '$user_firstname', '$user_Lastname', '$user_email', '$user_phoneNumber', '$user_password', '$user_country', '$user_address', '$userIP', 'N')";

        $result = mysqli_query($conn, $sql);
        if($result) {
            $rows = $this->fetchUserDetailsById($conn, $user_id);
            $status = array("status" => "success", "details" => $rows);
        } else {
            $rows = $this->fetchUserDetailsById($conn, $user_id);
            $status = array("status" => "error", "details" => $rows);
        }

        return json_encode($status, JSON_PRETTY_PRINT);


    }

    public function ValidateUser($conn, $user_id, $user_email, $user_password) {

        $json = array();

        $sql = "SELECT * FROM `users` WHERE `user_id` = '$user_id' AND `user_email` = '$user_email' AND `user_password` = '$user_password'";
        $result = mysqli_query($conn, $sql);
        $user_array = mysqli_fetch_array($result, MYSQLI_ASSOC);

        if($user_array == NULL) {
            $user_array = $json;
        } else {
            $json = $user_array;
        }

        return $json;
    }

    public function LoginUser($conn, $user_id, $user_email, $user_password) {

        $status = "";
        $user_array = $this->ValidateUser($conn, $user_id, $user_email, $user_password);

        if(sizeof($user_array) > 0) {

            $userId = $user_email . $user_password;
            $secret = 'sec!ReT423*&';
            $expiration = time() + 3600;
            $issuer = 'localhost';

            $token = Token::create($userId, $secret, $expiration, $issuer);
            // echo $token;
            $status = json_encode(array("responseCode" => "00", "message" => "success", "user_id" => $user_array['user_id'], "user_email" => $user_array['user_email'], "user_phoneNumber" => $user_array['user_phoneNumber'], "tokenType" => "Bearer", "expiresIn" => "3600", "accessToken" => $token, "timestamp" => date('d-M-Y H:i:s')));
        } else {
            $status =  json_encode(array("responseCode" => "04", "message" => "invalidCredentials", "timestamp" => date('d-M-Y H:i:s')));
        }
        $this->server_logs($status);
        return $status;
    }

    // public function CreateProduct($conn, $agent_id, $product_id, $category_id, $category_name, $product_size, $product_type, $product_description, $product_address, $product_imageUrl, $product_quantity, $product_discount, $) {

    // }

}

$portal = new PortalUtility();

// echo $portal->service_logs("loving");
// echo $portal->CreateUserAccount($conn, "Olalekan", "Akintola", "akintolajohn41@gmail.com", "09167628820", "Cougar@123", "Nigeria", "Futa North Gate");
// echo $portal->ValidateUser($conn, "5910840197", "akintolajohn41@gmail.com", "Cougar@123");
// echo $portal->LoginUser($conn, "5910840197","akintolajohn41@gmail.com", "Cougar@123");
// echo $portal->getIPAddress();
