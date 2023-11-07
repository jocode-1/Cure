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

    public function create_hospital($conn, $hospital_id, $hospital_name, $hospital_email, $hospital_password, $hospital_address)
    {

        $status = array();
        $hospital_id = $this->create_hospital_id();
        $sql = "INSERT INTO `hospitals`(`hospital_id`, `hospital_name`, `hospital_email`, `hospital_password`, `hospital_address`,`status`) VALUES ('$hospital_id','$hospital_name','$hospital_email', '$hospital_id', '$hospital_address','Y')";
        $result = mysqli_query($conn, $sql);
        if ($result) {
            $rows = $this->fetch_hospital_details_by_id($conn, $hospital_id);
            $status = array("status" => "success", "data" => $rows);
        } else {
            $rows = $this->fetch_hospital_details_by_id($conn, $hospital_id);
            $status = array("status" => "error", "details" => $rows);
        }

        return json_encode($status, JSON_PRETTY_PRINT);
    }

    public function create_hospital_id()
    {
        $uni = substr(str_shuffle(str_repeat("0123456789", 10)), 0, 10);

        return $uni;
    }

    public function fetch_hospital_details_by_id($conn, $hospital_id)
    {
        $json  = array();
        $sql = "SELECT `hospital_id`, `hospital_name`, `hospital_email`, `hospital_address`  FROM `hospitals` WHERE hospital_id = '$hospital_id'";
        $result = mysqli_query($conn, $sql);
        $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
        return $row;
    }

    public function checkHospitalExists($conn, $hospital_email)
    {
        $query = "SELECT * FROM hospitals WHERE hospital_email = '$hospital_email'";
        $result = mysqli_query($conn, $query);
        $count = mysqli_num_rows($result);
        return $count > 0;
    }

    public function create_hospital_staff($conn, $hospital_id, $staff_name, $staff_phone, $staff_email)
    {
        $status = array();
        $staff_code = $hospital_id . '-' . $this->create_admin_id();
        $hospital_name = $this->fetch_hospital_name_by_id($conn, $hospital_id);
        $staff_private_key = $this->create_admin_unique_key();
        $staff_default_password = $this->create_admin_default_password();
        $sql = "INSERT INTO `hospit_staff_account`(`hospital_id`, `hospital_name`, `staff_code`, `staff_name`, `staff_phone`, `staff_email`, `staff_private_key`, `staff_password`, `role`, `status`, `password_change`) 
        VALUES ('$hospital_id','$hospital_name','$staff_code','$staff_name','$staff_phone','$staff_email','$staff_private_key','$staff_default_password', 'Admin', 'Y','N')";
        $result = mysqli_query($conn, $sql);
        if ($result) {
            $rows = $this->fetch_staff_details_by_id($conn, $staff_code);
            $status = array("status" => "success", "data" => $rows);
        } else {
            $rows = $this->fetch_staff_details_by_id($conn, $staff_code);
            $status = array("status" => "error", "data" => $rows);
        }

        return json_encode($status, JSON_PRETTY_PRINT);
    }

    public function create_admin_id()
    {
        $uni = substr(str_shuffle(str_repeat("0123456789", 6)), 0, 6);

        return $uni;
    }

    public function fetch_hospital_name_by_id($conn, $hospital_id)
    {
        $json  = array();
        $sql = "SELECT *  FROM `hospitals` WHERE hospital_id = '$hospital_id'";
        $result = mysqli_query($conn, $sql);
        $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
        return $row['hospital_name'];
    }

    public function create_admin_unique_key()
    {
        $uni = substr(str_shuffle(str_repeat("0123456789ABCDEFHJKLMNPRSZTUW", 10)), 0, 10);

        return $uni;
    }

    public function create_admin_default_password()
    {
        $uni = substr(str_shuffle(str_repeat("0123456789ABCDEFHJKLMNPRSZTUW", 8)), 0, 8);

        return $uni;
    }

    public function fetch_staff_details_by_id($conn, $staff_code)
    {
        $json  = array();
        $sql = "SELECT `hospital_id`, `hospital_name`, `staff_code`, `staff_name`, `staff_phone`, `staff_email`, `staff_private_key`, `staff_password` FROM `hospit_staff_account`  WHERE staff_code = '$staff_code'";
        $result = mysqli_query($conn, $sql);
        $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
        return $row;
    }

    public function login_hospital_staff($conn, $hospital_id, $staff_email, $staff_password)
    {
        $status = "";
        $staff_array = $this->validateStaff($conn, $hospital_id, $staff_email, $staff_password);
        //  var_dump($staff_array);
        if (sizeof($staff_array) > 0) {
            $userId = $staff_email . $staff_password;
            $secret = 'sec!ReT423*&';
            $expiration = time() + 3600;
            $issuer = 'localhost';

            $token = Token::create($userId, $secret, $expiration, $issuer);
            //echo $token;

            // Update the database with the current login time
            $this->getLoginTime($conn, $staff_array['staff_code']);
            $status =  json_encode(array("responseCode" => "00", "message" => "success", "hospital_id" => $staff_array['hospital_id'], "hospital_name" => $staff_array['hospital_name'], "staff_name" => $staff_array['staff_name'], "staff_code" => $staff_array['staff_code'], "staff_email" => $staff_array['staff_email'], "password_status" => $staff_array['password_change'], "tokenType" => "Bearer", "expiresIn" => "3600", "accessToken" => $token, "timestamp" => date('d-M-Y H:i:s')));
        } else {
            $status =  json_encode(array("responseCode" => "04", "message" => "invalidCredentials", "timestamp" => date('d-M-Y H:i:s')));
        }
        $this->server_logs($status);
        return $status;
    }

    public function validateStaff($conn, $hospital_id, $staff_email, $staff_password)
    {
        $json = array();

        $sql = "SELECT * FROM `hospit_staff_account` WHERE `hospital_id` = '$hospital_id' AND `staff_email` = '$staff_email' AND `staff_password` = '$staff_password'";
        $result = mysqli_query($conn, $sql);
        $staff_array = mysqli_fetch_array($result, MYSQLI_ASSOC);
        if ($staff_array == NULL) {
            $staff_array = $json;
        } else {
            $json = $staff_array;
        }
        return $json;
    }

    public function getLoginTime($conn, $staff_email)
    {

        $currentDateTime = date('Y-m-d H:i:s');
        $sql = "UPDATE `hospit_staff_account` SET `login_time`='$currentDateTime' WHERE `staff_code`= '$staff_email'";
        $result = mysqli_query($conn, $sql);
    }
    public function server_logs($log_msg)
    {
        $log_dir = "server_logs";
        $log_file = "log_" . date('d-M-Y') . ".log";
        $log_path = "$log_dir/$log_file";

        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0777, true);
        }

        error_log($log_msg, 3, $log_path);
    }

    public function fetchStaffProfile($conn, $hospital_id, $staff_code, $token)
    {
        $status = "";
        $json = array();
        if (empty($token)) {
            $status = json_encode(array("responseCode" => "08", "message" => "invalid_token", "staff_code" => $staff_code, "token" => $token, "timestamp" => date('d-M-Y H:i:s')));
        } else if ($this->validateToken($token) === "true") {
            $sqlSelect = "SELECT * FROM `hospit_staff_account` WHERE `hospital_id` = '$hospital_id' AND `staff_code` = '$staff_code'";
            $result = mysqli_query($conn, $sqlSelect);
            while ($r = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                $json[] = $r;
            }
            $status = json_encode(array("responseCode" => "00", "message" => "success", "staff_code" => $staff_code, "token" => $token, "data" => $json, "timestamp" => date('d-M-Y H:i:s')));
        } else {
            $status = json_encode(array("responseCode" => "08", "message" => "expired_token", "staff_code" => $staff_code, "token" => $token, "timestamp" => date('d-M-Y H:i:s')));
        }

        $this->server_logs($status);
        return $status;
    }

    function getBearerToken()
    {
        $headers = $this->getAuthorizationHeader();
        // HEADER: Get the access token from the header
        if (!empty($headers)) {
            if (preg_match('/Bearer\s((.*)\.(.*)\.(.*))/', $headers, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }


    function getAuthorizationHeader()
    {
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            //print_r($requestHeaders);
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        return $headers;
    }

    public function validateToken($token)
    {
        $secret = 'sec!ReT423*&';

        $result = Token::validate($token, $secret);
        $converted_res = $result ? 'true' : 'false';
        return '' . $converted_res;
    }

    public function update_password($conn, $staff_email, $staff_password, $token)
    {
        $status = "";
        if (empty($token)) {
            $status = json_encode(array("responseCode" => "08", "message" => "invalid_token", "staff_code" => $staff_email, "token" => $token, "timestamp" => date('d-M-Y H:i:s')));
        } else if ($this->validateToken($token) === "true") {
            $sql = "UPDATE `hospit_staff_account` SET `staff_password` = '$staff_password', `password_change` = 'Y' WHERE `staff_email` = '$staff_email'";
            $result = mysqli_query($conn, $sql);
            if ($result) {
                $status = json_encode(array("responseCode" => "00", "message" => "success", "staff_email" => $staff_email, "token" => $token, "timestamp" => date('d-M-Y H:i:s')));
            } else {
                $status = json_encode(array("responseCode" => "04", "message" => "fail", "staff_email" => $staff_email, "token" => $token, "timestamp" => date('d-M-Y H:i:s')));
            }
        } else {
            $status = json_encode(array("responseCode" => "08", "message" => "expired_token", "staff_email" => $staff_email, "token" => $token, "timestamp" => date('d-M-Y H:i:s')));
        }

        $this->server_logs($status);
        return $status;
    }

    public function createToken()
    {
        $uni = substr(str_shuffle(str_repeat("0123456789", 6)), 0, 6);

        return $uni;
    }

public function sendSms($conn, $staff_phone, $token)
    {
        $curl = curl_init();
        $data = array(
            "api_key" => "TLWapUvKcsmxofaNTsKGgDyarC9wanWmNZr9WqfL1oRe50x9bVrVbupoQuaySw",
            "message_type" => "NUMERIC",
            "to" => "'.$staff_phone.'",
            "from" => "Cure",
            "channel" => "dnd",
            "pin_attempts" => 3,
            "pin_time_to_live" =>  1,
            "pin_length" => 6,
            "pin_placeholder" => "< 1234 >",
            "message_text" => 'Your one time pass is "'.$token.'". Please, don\'t disclose this to anyone.',
            "pin_type" => "NUMERIC"
        );

        $post_data = json_encode($data);

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.ng.termii.com/api/sms/otp/send",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $post_data,
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                "Content-Type: application/json"
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        echo $response;
    }

    public function verify_token($conn, $phone, $token)
    {
        $status = "";
        $staff_array = $this->validateTokens($conn, $phone, $token);
      //  var_dump($staff_array);
        if (sizeof($staff_array) > 0) {
            $status =  json_encode(array("responseCode" => "00", "message" => "success", "timestamp" => date('d-M-Y H:i:s')));
        } else {
            $status =  json_encode(array("responseCode" => "04", "message" => "invalidToken", "timestamp" => date('d-M-Y H:i:s')));
        }
        $this->server_logs($status);
        return $status;
    }

    public function validateTokens($conn, $phone, $token)
    {
        $json = array();
        $sql = "SELECT * FROM `tenants` WHERE `phone` = '$phone' AND `token` = '$token'";
        $result = mysqli_query($conn, $sql);
        $staff_array = mysqli_fetch_array($result, MYSQLI_ASSOC);
    
        return $staff_array;
    }
}
$portal = new PortalUtility();

// echo $portal->sendSms();
