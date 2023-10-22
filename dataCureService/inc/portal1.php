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

  //ticketSupport
    public function create_support_ticket($conn, $merchant_id, $staff_code, $title, $message, $attachment, $token,$filename)
    {
        $status = "";
        //echo $merchant_id;
        if (empty($token)) {
            $status = json_encode(array("responseCode" => "08", "responseMsg" => "invalid_token", "staff_code" => $staff_code, "token" => $token, "timestamp" => date('d-M-Y H:i:s')));
        } else if ($this->validateToken($token) === "true") {
            $ticket_id = $this->create_ticket_id();
            $merchant_array = $this->fetch_details_by_id($conn, $merchant_id);
            $staff_array = $this->fetch_staff_details_by_id($conn,$staff_code);
            $merchant_name = $merchant_array['merchant_name'];
            $merchant_email = $merchant_array['merchant_email'];
            $staff_email = $staff_array['staff_email'];
           // echo $staff_email;
            $sql = "INSERT INTO `tickets`(`merchant_id`, `merchant_name`, `ticket_id`, `title`, `message`, `attachments`, `status`,`staff_code`) 
            VALUES ('$merchant_id','$merchant_name','$ticket_id','$title','$message','$attachment','N','$staff_code')";
            $result = mysqli_query($conn, $sql);
            if ($result) {
                $status = json_encode(array("responseCode" => "00", "responseMsg" => "success", "staff_code" => $staff_code, "token" => $token, "timestamp" => date('d-M-Y H:i:s')));
                $this->support_tickets_message_initial($conn, $merchant_id,$ticket_id,$message,$staff_code);
                $this->support($merchant_email,$merchant_name,$title,$message,$staff_code,$staff_email,$attachment,$filename);
            } else {
                $status = json_encode(array("responseCode" => "04", "responseMsg" => "error", "staff_code" => $staff_code, "token" => $token, "timestamp" => date('d-M-Y H:i:s')));
            }
        }else{
            $status = json_encode(array("responseCode" => "08", "responseMsg" => "expired_token", "staff_code" => $staff_code, "token" => $token, "timestamp" => date('d-M-Y H:i:s')));
        }

        $this->server_logs($status);
        return $status;
    }


    public function support_tickets_message_initial($conn, $merchant_id,$ticket_id,$message,$staff_code){
        $message_id =  $ticket_id.$this->createUnique();
        $sql = "INSERT INTO `support_tickets_messages`(`merchant_id`, `ticket_id`, `message_id`, `message`,`staff_code`, `status`) VALUES
         ('$merchant_id','$ticket_id','$message_id','$message','$staff_code','N')";
          $result = mysqli_query($conn, $sql);
    }


    public function support_tickets_message($conn, $merchant_id,$ticket_id,$message,$staff_code){
        $message_id =  $ticket_id.$this->createUnique();
        $sql = "INSERT INTO `support_tickets_messages`(`merchant_id`, `ticket_id`, `message_id`, `message`,`staff_code`, `status`) VALUES
         ('$merchant_id','$ticket_id','$message_id','$message','$staff_code','N')";
          $result = mysqli_query($conn, $sql);
         if ($result) {
            $status = json_encode(array("responseCode" => "00", "responseMsg" => "success", "staff_code" => $staff_code, "token" => $token, "timestamp" => date('d-M-Y H:i:s')));
        } else {
            $status = json_encode(array("responseCode" => "04", "responseMsg" => "error", "staff_code" => $staff_code, "token" => $token, "timestamp" => date('d-M-Y H:i:s')));
        } 
    }

	 public function fetchSupportTickets($conn, $merchant_id, $staff_code, $token)
    {
        $status = "";
        $json = array();
        if (empty($token)) {
            $status = json_encode(array("responseCode" => "08", "message" => "invalid_token", "staff_code" => $staff_code, "token" => $token, "timestamp" => date('d-M-Y H:i:s')));
        } else if ($this->validateToken($token) === "true") {
            $sqlSelect = "SELECT * FROM `tickets` WHERE `merchant_id` = '$merchant_id'  AND `staff_code` = '$staff_code' order by timestamp DESC";
            $result = mysqli_query($conn, $sqlSelect);
            while ($r = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                $json[] = $r;
            }
            $status = json_encode(array("responseCode" => "00", "message" => "success", "staff_code" => $staff_code, "token" => $token, "data" => $json, "timestamp" => date('d-M-Y H:i:s')));
        } else {
            $status = json_encode(array("responseCode" => "08", "message" => "expired_token", "email" => $staff_code, "token" => $token, "timestamp" => date('d-M-Y H:i:s')));
        }

        $this->server_logs($status);
        return $status;
    }


    public function create_merchant(
        $conn,
        $merchant_name,
        $merchant_email,
        $merchant_phone,
        $merchant_address,
        $merchant_rate,
        $processing_rate,
        $insurrance,
        $commitment,
        $merchant_remita_key,
        $merchant_remita_id,
        $smtp_host,
        $smtp_email,
        $smtp_password
    ) {
        $status = array();
        $merchant_id = $this->create_merchant_id();
        $merchant_public_id = $this->create_public_id();
        $sql = "INSERT INTO `merchant_accounts`(`merchant_id`, `merchant_name`, `merchant_email`, `merchant_phone`, `merchant_address`, `merchant_rate`, `processing_rate`, `insurrance`, `commitment`,
         `merchant_remita_key`, `merchant_remita_id`, `smtp_host`, `smtp_email`, `smtp_password`, `status`,`merchant_public_id`) VALUES 
        ('$merchant_id','$merchant_name','$merchant_email','$merchant_phone','$merchant_address','$merchant_rate','$processing_rate','$insurrance','$commitment','$merchant_remita_key','$merchant_remita_id','$smtp_host','$smtp_email','$smtp_password','Y','$merchant_public_id')";
        $result = mysqli_query($conn, $sql);
        if ($result) {
            $rows = $this->fetch_merchant_details_by_id($conn, $merchant_id);
            $status = array("status" => "success", "details" => $rows);
        } else {
            $rows = $this->fetch_merchant_details_by_id($conn, $merchant_id);
            $status = array("status" => "error", "details" => $rows);
        }

        return json_encode($status, JSON_PRETTY_PRINT);
    }


    public function create_merchant_staff($conn, $merchant_id, $staff_name, $staff_phone, $staff_email)
    {
        $status = array();
        $staff_code = $merchant_id . '-' . $this->create_staff_id();
        $merchant_name = $this->fetch_merchant_name_by_id($conn, $merchant_id);
        $staff_unique_key = $this->create_staff_unique_key();
        $staff_default_password = $this->create_staff_default_password();
        $sql = "INSERT INTO `merchant_staff_accounts`(`merchant_id`, `merchant_name`, `staff_code`, `staff_name`, `staff_phone`, `staff_email`, `staff_unique_key`, `staff_password`, `status`,`password_change`) 
        VALUES ('$merchant_id','$merchant_name','$staff_code','$staff_name','$staff_phone','$staff_email','$staff_unique_key','$staff_default_password','Y','N')";
        $result = mysqli_query($conn, $sql);
        if ($result) {
            $rows = $this->fetch_staff_details_by_id($conn, $staff_code);
            $status = array("status" => "success", "details" => $rows);
        } else {
            $rows = $this->fetch_staff_details_by_id($conn, $staff_code);
            $status = array("status" => "error", "details" => $rows);
        }

        return json_encode($status, JSON_PRETTY_PRINT);
    }


    public function login_merchant_staff($conn, $merchant_id, $staff_email, $staff_password)
    {
        $status = "";
        $staff_array = $this->validateStaff($conn, $merchant_id, $staff_email, $staff_password);
        //  var_dump($staff_array);
        if (sizeof($staff_array) > 0) {
            $userId = $staff_email . $staff_password;
            $secret = 'sec!ReT423*&';
            $expiration = time() + 3600;
            $issuer = 'localhost';

            $token = Token::create($userId, $secret, $expiration, $issuer);
            //echo $token;
            $status =  json_encode(array("responseCode" => "00", "message" => "success", "merchant_id" => $staff_array['merchant_id'], "staff_name" => $staff_array['staff_name'], "staff_code" => $staff_array['staff_code'], "email" => $staff_array['staff_email'], "password_status" => $staff_array['password_change'], "tokenType" => "Bearer", "expiresIn" => "3600", "accessToken" => $token, "timestamp" => date('d-M-Y H:i:s')));
        } else {
            $status =  json_encode(array("responseCode" => "04", "message" => "invalidCredentials", "timestamp" => date('d-M-Y H:i:s')));
        }
        $this->server_logs($status);
        return $status;
    }

    public function validateStaff($conn, $merchant_id, $staff_email, $staff_password)
    {
        $json = array();

        $sql = "SELECT * FROM `merchant_staff_accounts` WHERE `merchant_id` = '$merchant_id' AND `staff_email` = '$staff_email' AND `staff_password` = '$staff_password'";
        $result = mysqli_query($conn, $sql);
        $staff_array = mysqli_fetch_array($result, MYSQLI_ASSOC);
        if ($staff_array == NULL) {
            $staff_array = $json;
        } else {
            $json = $staff_array;
        }
        return $json;
    }

    public function createRemitaMandate($conn, $merchant_id, $application_id, $staff_code, $token)
    {

        $status = "";
        if (empty($token)) {
            $status = json_encode(array("responseCode" => "08", "responseMsg" => "invalid_token", "staff_code" => $staff_code, "token" => $token, "timestamp" => date('d-M-Y H:i:s')));
        } else if ($this->validateToken($token) === "true") {

            $service = '';
            $merchant_array = $this->fetch_details_by_id($conn, $merchant_id);
            $application_array = $this->fetch_application_by_id($conn, $merchant_id, $application_id);
            //var_dump($application_array);
            //$merchantId = "8797039389";
            //$apiKey = "Q1dHREVNTzEyMzR8Q1dHREVNTw==";
            //$apiToken = //"SGlQekNzMEdMbjhlRUZsUzJCWk5saDB6SU14Zk15djR4WmkxaUpDTll6bGIxR//Cs4UkVvaGhnPT0=";
            $merchantId = $merchant_array['merchant_remita_id'];
             $apiKey = $merchant_array['merchant_remita_key'];
             $apiToken = $merchant_array['merchant_remita_apitoken'];
            $merchant_name = $merchant_array['merchant_name'];

            //application details
            $phone = $application_array[0]['customer_phone'];
            $account_number = $application_array[0]['account_number'];
            $bank_code = $application_array[0]['bank_code'];
            $period = $application_array[0]['period'];
            $loan_amount = $application_array[0]['amount'];
            $customer_id = $application_array[0]['customerId'];
            $total_repayment = $application_array[0]['total_repayment'];
            $customer_name = $application_array[0]['customer_name'];
            $customer_email = $application_array[0]['customer_email'];
            $bvn = $application_array[0]['customer_bvn'];
            $interest = $application_array[0]['interest'];
            $auth =  $application_array[0]['authcode'];
            //substr(str_shuffle(str_repeat("0123456789", 6)), 0, 6);
            $date = date("d-m-Y H:i:s") . '+0000';
            //loanitem details

            $requestId = substr(str_shuffle(str_repeat("0123456789", 9)), 0, 9);
            $randomnumber = substr(str_shuffle(str_repeat("0123456789", 9)), 0, 9);
            $apiHash = $apiKey . $requestId . $apiToken;
            $key = hash("sha512", $apiHash);

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://remitademo.net/remita/exapp/api/v1/send/api/loansvc/data/api/v2/payday/post/loan',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '{
                "customerId": "'.$customer_id.'",
                "authorisationCode": "'.$auth.'",
                "authorisationChannel": "USSD",
                "phoneNumber": " ' . $phone . '",
                "accountNumber": " ' . $account_number . '",
                "currency": "NGN",
                "loanAmount": " ' . $loan_amount . '",
                "collectionAmount": " ' . $total_repayment . '",
                "dateOfDisbursement": "' . $date . '",
                "dateOfCollection": "' . $date . '",
                "totalCollectionAmount": " ' . $total_repayment . '",
                "numberOfRepayments": " ' . $period . '",
                "bankCode": "'.$bank_code.'"
                }',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'API_KEY: '. $apiKey,
                    'MERCHANT_ID: '.$merchantId,
                    'REQUEST_ID: ' . $requestId,
                    'AUTHORIZATION: remitaConsumerKey=' . $apiKey . ', remitaConsumerToken=' . $key
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            $status =  $response;
            $json = json_decode($response, true);
            $mandateRef =  $json['data']['mandateReference'];
            if ($mandateRef == "") {
                $status = json_encode(array("responseCode" => "08", "responseMsg" => "remita_error", "staff_code" => $staff_code, "token" => $token, "timestamp" => date('d-M-Y H:i:s')));
            } else {
                //echo $response;
                $this->insertMerchantMandates($conn, $merchant_id, $merchant_name, $application_id, $mandateRef, $loan_amount, $period, $interest, $total_repayment, $customer_name, $customer_id, $auth, $bvn);
                $this->updateApplicationStatus($conn, $application_id, $merchant_id);
                $this->acceptMail($customer_email, $merchant_name, $application_id, $customer_name, $loan_amount, $period, 'https://app.stack.net.ng/dataStackService/inc/templates/success.phtml');
            }
        } else {
            $status = json_encode(array("responseCode" => "08", "responseMsg" => "expired_token", "staff_code" => $staff_code, "token" => $token, "timestamp" => date('d-M-Y H:i:s')));
        }

        $this->server_logs($status . ' STAFF-CODE : ' . $staff_code . ' TOKEN : ' . $token);

        return $status;
    }

    public function declineRemitaMandate($conn, $merchant_id, $application_id, $staff_code, $token)
    {

        $status = "";
        if (empty($token)) {
            $status = json_encode(array("responseCode" => "08", "responseMsg" => "invalid_token", "staff_code" => $staff_code, "token" => $token, "timestamp" => date('d-M-Y H:i:s')));
        } else if ($this->validateToken($token) === "true") {
            $merchant_array = $this->fetch_details_by_id($conn, $merchant_id);
            $application_array = $this->fetch_application_by_id($conn, $merchant_id, $application_id);
            $merchant_name = $merchant_array['merchant_name'];

            //application details
            $period = $application_array[0]['period'];
            $loan_amount = $application_array[0]['amount'];
            $customer_name = $application_array[0]['customer_name'];
            $customer_email = $application_array[0]['customer_email'];
            $status = json_encode(array("responseCode" => "00", "responseMsg" => "success", "staff_code" => $staff_code, "token" => $token, "timestamp" => date('d-M-Y H:i:s')));
            $this->declineApplicationStatus($conn, $application_id, $merchant_id);
            $this->rejectMail($customer_email, $merchant_name, $application_id, $customer_name, $loan_amount, $period, 'http://app.stack.net.ng/dataStackService/inc/templates/reject.phtml');
        } else {
            $status = json_encode(array("responseCode" => "08", "responseMsg" => "expired_token", "staff_code" => $staff_code, "token" => $token, "timestamp" => date('d-M-Y H:i:s')));
        }

        $this->server_logs($status . ' STAFF-CODE : ' . $staff_code . ' TOKEN : ' . $token);

        return $status;
    }


    public function fetch_repayment_details($conn,$merchant_id, $application_id)
    {

        $status = "";
        $service = '';
        $merchant_array = $this->fetch_details_by_id($conn, $merchant_id);
        $mandate_array = $this->fetch_mandates($conn, $merchant_id, $application_id);
        //$merchantId = "8797039389";
        //$apiKey = "QzAwMDAxNjE5NTcxMjM0fEMwMDAwMTYxOTU3";
        //$apiToken = //"VUtlOWlhWFhua29IbmpieCs2Y1VxMUNudlltbDE1NUtnMWNIWVV3MTJzTHkyQkpQR//zJCRHM2WFQ3MldPOVJjUA==";
        $merchantId = $merchant_array['merchant_remita_id'];
        $apiKey = $merchant_array['merchant_remita_key'];
        $apiToken = $merchant_array['merchant_remita_apitoken'];
        $merchant_name = $merchant_array['merchant_name'];
        $customer_id = $mandate_array[0]['customer_id'];
        $mandate = $mandate_array[0]['mandate_id'];
        $authcode = $mandate_array[0]['authcode'];
        $requestId = substr(str_shuffle(str_repeat("0123456789", 13)), 0, 13);
        $randomnumber = substr(str_shuffle(str_repeat("0123456789", 6)), 0, 6);
        $apiHash = $apiKey . $requestId . $apiToken;
        $key = hash("sha512", $apiHash);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://remitademo.net/remita/exapp/api/v1/send/api/loansvc/data/api/v2/payday/loan/payment/history',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
             "authorisationCode": "'.$authcode.'",
             "customerId": "'.$customer_id.'",
             "mandateRef": "'.$mandate.'"
            }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'API_KEY:  ' . $apiKey,
                'MERCHANT_ID:' . $merchantId,
                'REQUEST_ID: ' . $requestId,
                'AUTHORIZATION: remitaConsumerKey=' . $apiKey . ', remitaConsumerToken=' . $key,
                'Cookie: b1pi=!oihiTuP9HAqBpN+gprcF4rMnrV7jL8FQO5jU+6u4ZmXOoGgmMqiNDTIKd88KiTOzP4giBT+773DK'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }



    public function stopRemitaMandate($conn, $merchant_id, $application_id, $staff_code, $token)
    {
        $status = "";
        if (empty($token)) {
            $status = json_encode(array("responseCode" => "08", "responseMsg" => "invalid_token", "staff_code" => $staff_code, "token" => $token, "timestamp" => date('d-M-Y H:i:s')));
        } else if ($this->validateToken($token) === "true") {

            $service = '';
            $merchant_array = $this->fetch_details_by_id($conn, $merchant_id);
            $application_array = $this->fetch_application_by_id($conn, $merchant_id, $application_id);
            $mandate_array = $this->fetch_mandate_by_id($conn, $merchant_id, $application_id);
            //var_dump($application_array);
            //$merchantId = "8797039389";
           // $apiKey = "Q1dHREVNTzEyMzR8Q1dHREVNTw==";
           // $apiToken = //"SGlQekNzMEdMbjhlRUZsUzJCWk5saDB6SU14Zk15djR4WmkxaUpDTll6bGIxRC//s4UkVvaGhnPT0=";
            $merchantId = $merchant_array['merchant_remita_id'];
            $apiKey = $merchant_array['merchant_remita_key'];
             $apiToken = $merchant_array['merchant_remita_apitoken'];
            $merchant_name = $merchant_array['merchant_name'];


            // $merchantId = "8797039389";
            // $apiKey = "QzAwMDAxNjE5NTcxMjM0fEMwMDAwMTYxOTU3";
            // $apiToken = "VUtlOWlhWFhua29IbmpieCs2Y1VxMUNudlltbDE1NUtnMWNIWVV3MTJzTHkyQkpQRzJCRHM2WFQ3MldPOVJjUA==";
            // $merchantId = "27768931";
            //$apiKey = "Q1dHREVNTzEyMzR8Q1dHREVNTw==";
            //$apiToken = "SGlQekNzMEdMbjhlRUZsUzJCWk5saDB6SU14Zk15djR4WmkxaUpDTll6bGIxRCs4UkVvaGhnPT0=";

            $customer_id = $application_array[0]['customerId'];
            $mandate = $mandate_array[0]['mandate_id'];
            $auth = $application_array[0]['authcode'];
            // substr(str_shuffle(str_repeat("0123456789", 6)), 0, 6);
            $date = date("d-m-Y H:i:s") . '+0000';


            $requestId = substr(str_shuffle(str_repeat("0123456789", 13)), 0, 13);
            $randomnumber = substr(str_shuffle(str_repeat("0123456789", 6)), 0, 6);
            $apiHash = $apiKey . $requestId . $apiToken;
            $key = hash("sha512", $apiHash);

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://remitademo.net/remita/exapp/api/v1/send/api/loansvc/data/api/v2/payday/stop/loan',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => '{ 
	        "authorisationCode": "'.$auth.'", 
	        "customerId": "'. $customer_id.'", 
	        "mandateReference": "' . $mandate . '" 
            }',
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'API_KEY: '.$apiKey,
                    'MERCHANT_ID: '.$merchantId,
                    'REQUEST_ID: ' . $requestId,
                    'AUTHORIZATION: remitaConsumerKey=' . $apiKey . ', remitaConsumerToken=' . $key
                ),
            ));

            $response = curl_exec($curl);
            $json = json_decode($response, true);
            if ($json['responseMsg'] != "SUCCESS") {
                $status = json_encode(array("responseCode" => "08", "responseMsg" => "remita_error", "staff_code" => $staff_code, "token" => $token, "timestamp" => date('d-M-Y H:i:s')));
            } else {
                $status = $response;
                $this->updateMandateStatus($conn, $mandate);
            }
        } else {
            $status = json_encode(array("responseCode" => "08", "responseMsg" => "expired_token", "staff_code" => $staff_code, "token" => $token, "timestamp" => date('d-M-Y H:i:s')));
        }
        curl_close($curl);
        $this->server_logs($status . ' STAFF-CODE : ' . $staff_code . ' TOKEN : ' . $token);

        return $status;
    }




    public function insertMerchantMandates($conn, $merchant_id, $merhant_name, $application_id, $mandate_id, $amount, $period, $interest, $repayment_amount, $customer_name, $customer_id, $auth_code, $bvn)
    {

        $sql = "INSERT INTO `merchant_mandates`(`merchant_id`, `merchant_name`, `application_id`, `mandate_id`, `amount`, `period`, `merchant_rate`, `repayment_amount`, `customer_name`, `customer_id`, `auth_code`, `customer_bvn`, `status`)
         VALUES ('$merchant_id','$merhant_name','$application_id','$mandate_id','$amount','$period','$interest','$repayment_amount','$customer_name', '$customer_id', '$auth_code', '$bvn', 'N')";
        $result = mysqli_query($conn, $sql);
    }

    public function updateMandateStatus($conn, $mandate)
    {
        $sql = "UPDATE `merchant_mandates` SET `status` ='Z' WHERE `mandate_id` = '$mandate'";
        $result = mysqli_query($conn, $sql);
    }

    public function updateApplicationStatus($conn, $application_id, $merchant_id)
    {
        $sql = "UPDATE `merchant_applications` SET `treated` ='Y' WHERE `application_id` = '$application_id' AND `merchant_id` = '$merchant_id'";
        $result = mysqli_query($conn, $sql);
    }

    public function declineApplicationStatus($conn, $application_id, $merchant_id)
    {
        $sql = "UPDATE `merchant_applications` SET `treated` ='Z' WHERE `application_id` = '$application_id' AND `merchant_id` = '$merchant_id'";
        $result = mysqli_query($conn, $sql);
    }

    public function fetchOpenApplications($conn, $merchant_id, $staff_code, $token)
    {
        $status = "";
        $json = array();
        if (empty($token)) {
            $status = json_encode(array("responseCode" => "08", "message" => "invalid_token", "staff_code" => $staff_code, "token" => $token, "timestamp" => date('d-M-Y H:i:s')));
        } else if ($this->validateToken($token) === "true") {
            $sqlSelect = "SELECT * FROM `merchant_applications` WHERE `merchant_id` = '$merchant_id'  AND `treated` = 'N' order by timestamp DESC";
            $result = mysqli_query($conn, $sqlSelect);
            while ($r = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                $json[] = $r;
            }
            $status = json_encode(array("responseCode" => "00", "message" => "success", "staff_code" => $staff_code, "token" => $token, "data" => $json, "timestamp" => date('d-M-Y H:i:s')));
        } else {
            $status = json_encode(array("responseCode" => "08", "message" => "expired_token", "email" => $staff_code, "token" => $token, "timestamp" => date('d-M-Y H:i:s')));
        }

        $this->server_logs($status);
        return $status;
    }


      public function fetchNonOpenApplications($conn, $merchant_id, $staff_code, $token)
    {
        $status = "";
        $json = array();
        if (empty($token)) {
            $status = json_encode(array("responseCode" => "08", "message" => "invalid_token", "staff_code" => $staff_code, "token" => $token, "timestamp" => date('d-M-Y H:i:s')));
        } else if ($this->validateToken($token) === "true") {
            $sqlSelect = "SELECT * FROM `merchant_open_applications` WHERE `merchant_id` = '$merchant_id'  AND `treated` = 'N' order by timestamp DESC";
            $result = mysqli_query($conn, $sqlSelect);
            while ($r = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                $json[] = $r;
            }
            $status = json_encode(array("responseCode" => "00", "message" => "success", "staff_code" => $staff_code, "token" => $token, "data" => $json, "timestamp" => date('d-M-Y H:i:s')));
        } else {
            $status = json_encode(array("responseCode" => "08", "message" => "expired_token", "email" => $staff_code, "token" => $token, "timestamp" => date('d-M-Y H:i:s')));
        }

        $this->server_logs($status);
        return $status;
    }


    public function fetchMerchantDetails($conn, $merchant_id)
    {
        $ip = $this->getIPAddress();
        $status = "";
        $json = array();
        $sqlSelect = "SELECT * FROM `merchant_accounts` WHERE `merchant_id` = '$merchant_id' AND `status` = 'Y'";
        $result = mysqli_query($conn, $sqlSelect);
        while ($r = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $json[] = $r;
        }
        $status = json_encode(array("ipAddress" => $ip, "responseCode" => "00", "message" => "success", "data" => $json, "timestamp" => date('d-M-Y H:i:s')));

        $this->service_logs($status);
        return $status;
    }



    public function fetchOpenMandates($conn, $merchant_id, $staff_code, $token)
    {
        $status = "";
        $json = array();
        if (empty($token)) {
            $status = json_encode(array("responseCode" => "08", "message" => "invalid_token", "staff_code" => $staff_code, "token" => $token, "timestamp" => date('d-M-Y H:i:s')));
        } else if ($this->validateToken($token) === "true") {
            $sqlSelect = "SELECT * FROM `merchant_mandates` WHERE `merchant_id` = '$merchant_id'  AND `status` = 'N' order by timestamp DESC";
            $result = mysqli_query($conn, $sqlSelect);
            while ($r = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                $json[] = $r;
            }
            $status = json_encode(array("responseCode" => "00", "message" => "success", "staff_code" => $staff_code, "token" => $token, "data" => $json, "timestamp" => date('d-M-Y H:i:s')));
        } else {
            $status = json_encode(array("responseCode" => "08", "message" => "expired_token", "email" => $staff_code, "token" => $token, "timestamp" => date('d-M-Y H:i:s')));
        }

        $this->server_logs($status);
        return $status;
    }


    public function fetch_mandate_by_id($conn, $merchant_id, $application_id)
    {
        $json = array();
        $sqlSelect = "SELECT * FROM `merchant_mandates` WHERE `merchant_id` = '$merchant_id'  AND `application_id` = '$application_id'";
        $result = mysqli_query($conn, $sqlSelect);
        while ($r = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $json[] = $r;
        }

        return $json;
    }


    public function fetch_mandates($conn,$merchant_id, $application_id){
        
        $json  = array();
        $sql = "SELECT MM.customer_id, MM.application_id, MM.customer_id,MM.mandate_id, RD.customerid, RD.authcode FROM merchant_mandates MM, remitadetails RD WHERE MM.application_id = RD.application_id AND MM.application_id = '$application_id' AND MM.merchant_id = '$merchant_id'";
        $result = mysqli_query($conn, $sql);
        while ($r = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $json[] = $r;
        }

        return $json;
    }



    public function fetch_mandate_details($conn, $merchant_id, $application_id, $staff_code, $token)
    {
        $status = "";
        $json = array();
        if (empty($token)) {
            $status = json_encode(array("responseCode" => "08", "message" => "invalid_token", "staff_code" => $staff_code, "token" => $token, "app_id" => $application_id, "timestamp" => date('d-M-Y H:i:s')));
        } else if ($this->validateToken($token) === "true") {
            $sqlSelect = "SELECT * FROM `merchant_mandates` WHERE `merchant_id` = '$merchant_id'  AND `application_id` = '$application_id'";
            $result = mysqli_query($conn, $sqlSelect);
            while ($r = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                $json[] = $r;
            }
            $status = json_encode(array("responseCode" => "00", "message" => "success", "staff_code" => $staff_code, "token" => $token, "data" => $json, "app_id" => $application_id, "timestamp" => date('d-M-Y H:i:s')));
        } else {
            $status = json_encode(array("responseCode" => "08", "message" => "expired_token", "staff_code" => $staff_code, "token" => $token, "app_id" => $application_id, "timestamp" => date('d-M-Y H:i:s')));
        }

        $this->server_logs($status);
        return $status;
    }


    public function fetch_liquidated_mandates($conn, $merchant_id, $staff_code, $token, $date_from, $date_to)
    {
        $status = "";
        $json = array();
        if (empty($token)) {
            $status = json_encode(array("responseCode" => "08", "message" => "invalid_token", "staff_code" => $staff_code, "token" => $token, "timestamp" => date('d-M-Y H:i:s')));
        } else if ($this->validateToken($token) === "true") {
            $sqlSelect = "SELECT * FROM `merchant_mandates` WHERE `merchant_id` = '$merchant_id' AND `status` = 'Z' AND `timestamp` BETWEEN '$date_from' AND '$date_to' ORDER BY `timestamp` DESC";
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


    public function fetch_declined_applications($conn, $merchant_id, $staff_code, $token, $date_from, $date_to)
    {
        $status = "";
        $json = array();
        if (empty($token)) {
            $status = json_encode(array("responseCode" => "08", "message" => "invalid_token", "staff_code" => $staff_code, "token" => $token, "timestamp" => date('d-M-Y H:i:s')));
        } else if ($this->validateToken($token) === "true") {
            $sqlSelect = "SELECT * FROM `merchant_applications` WHERE `merchant_id` = '$merchant_id'  AND `treated` = 'Z' AND `timestamp` BETWEEN '$date_from' AND '$date_to' ORDER BY `timestamp` DESC";
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


    public function fetch_approved_applications($conn, $merchant_id, $staff_code, $token, $date_from, $date_to)
    {
        $status = "";
        $json = array();
        if (empty($token)) {
            $status = json_encode(array("responseCode" => "08", "message" => "invalid_token", "staff_code" => $staff_code, "token" => $token, "timestamp" => date('d-M-Y H:i:s')));
        } else if ($this->validateToken($token) === "true") {
            $sqlSelect = "SELECT * FROM `merchant_applications` WHERE `merchant_id` = '$merchant_id'  AND `treated` = 'Y' AND `timestamp` BETWEEN '$date_from' AND '$date_to' ORDER BY `timestamp` DESC";
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


    public function fetch_service_logs($conn, $merchant_id, $staff_code, $token)
    {
        $status = "";
        $json = array();
        if (empty($token)) {
            $status = json_encode(array("responseCode" => "08", "message" => "invalid_token", "staff_code" => $staff_code, "token" => $token, "timestamp" => date('d-M-Y H:i:s')));
        } else if ($this->validateToken($token) === "true") {
            $sqlSelect = "SELECT * FROM `merchant_call_logs` WHERE `merchant_id` = '$merchant_id'";
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







    public function fetch_webhook_notification($conn, $merchant_id, $staff_code, $token)
    {
        $status = "";
        $json = array();
        if (empty($token)) {
            $status = json_encode(array("responseCode" => "08", "message" => "invalid_token", "staff_code" => $staff_code, "token" => $token, "timestamp" => date('d-M-Y H:i:s')));
        } else if ($this->validateToken($token) === "true") {
            $sqlSelect = "SELECT * FROM `notification` WHERE `merchant_id` = '$merchant_id'";
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


    public function fetch_staff_profile($conn, $merchant_id, $staff_code, $token)
    {
        $status = "";
        $json = array();
        if (empty($token)) {
            $status = json_encode(array("responseCode" => "08", "message" => "invalid_token", "staff_code" => $staff_code, "token" => $token, "timestamp" => date('d-M-Y H:i:s')));
        } else if ($this->validateToken($token) === "true") {
            $sqlSelect = "SELECT * FROM `merchant_staff_accounts` WHERE `merchant_id` = '$merchant_id' AND `staff_code` = '$staff_code'";
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



    public function customer_application($conn, $merchant_id, $customer_name, $bank_code, $bank_name, $account_number, $customer_bvn, $customer_phone, $customer_email, $customer_address, $dob, $gender, $employer_name, $employer_address, $level, $step, $amount, $period, $passport, $identity, $utility)
    {
        $status = "";
        $application_id = $this->create_application_id(); //
         $merchant_name = $this->fetch_merchant_name_by_id($conn, $merchant_id);
        $authentication_code = '1234';
        $ip = $this->getIPAddress();
        $validRemita = $this->processRemitaRecords($conn, $merchant_id, $application_id, $account_number, $bank_code, $customer_bvn);
        $valid = json_decode($validRemita, true);
        if ($valid['status'] != "success") {
           // $status = json_encode(array("responseCode" => "08", //"customer_name" => $customer_name, "merchant_id" => //$merchant_id, "email" => $customer_email, "phone" => //$customer_phone, "responseMessage" => "NotFound"));
            $sql_01 = "INSERT INTO `merchant_applications`(`merchant_id`, `merchant_name`, `application_id`, `customer_name`,`bank_code` ,`customer_bank`, `account_number`, `customer_bvn`, `customer_phone`, `customer_email`, `customer_address`, `date_of_birth`, `gender`, `employer_name`, `employer_address`, `level`, `step`, `amount`, `period`, `passport_link`, `identity_link`, `utility_bills_link`, `auth_code`, `status`,`ip_address`,`treated`)
             VALUES ('$merchant_id','$merchant_name','$application_id','$customer_name','$bank_code','$bank_name','$account_number','$customer_bvn','$customer_phone','$customer_email','$customer_address','$dob','$gender','$employer_name','$employer_address','$level','$step','$amount','$period','$passport','$identity','$utility','$authentication_code','Y','$ip','N')";
            $results = mysqli_query($conn, $sql_01);
            if ($results) {
                $this->updateLoanItems($conn, $merchant_id, $application_id, $amount, $period);
                $status = json_encode(array("responseCode" => "00", "message" => "success", "customer_name" => $customer_name, "merchant_id" => $merchant_id, "email" => $customer_email, "phone" => $customer_phone, "application" => $application_id));
                $this->newMail($customer_email, $merchant_name, $application_id, $customer_name, $amount, $period, 'https://stack.net.ng/stack/dataStackService/inc/templates/newMail.phtml');
            } else {
                $status = json_encode(array("responseCode" => "04", "message" => "fail", "customer_name" => $customer_name, "merchant_id" => $merchant_id, "email" => $customer_email, "phone" => $customer_phone, "application" => "null"));
            }
        } else {
           
            $sql = "INSERT INTO `merchant_applications`(`merchant_id`, `merchant_name`, `application_id`, `customer_name`,`bank_code` ,`customer_bank`, `account_number`, `customer_bvn`, `customer_phone`, `customer_email`, `customer_address`, `date_of_birth`, `gender`, `employer_name`, `employer_address`, `level`, `step`, `amount`, `period`, `passport_link`, `identity_link`, `utility_bills_link`, `auth_code`, `status`,`ip_address`,`treated`)
             VALUES ('$merchant_id','$merchant_name','$application_id','$customer_name','$bank_code','$bank_name','$account_number','$customer_bvn','$customer_phone','$customer_email','$customer_address','$dob','$gender','$employer_name','$employer_address','$level','$step','$amount','$period','$passport','$identity','$utility','$authentication_code','Y','$ip','N')";
            $results = mysqli_query($conn, $sql);
            if ($results) {
                $this->updateLoanItems($conn, $merchant_id, $application_id, $amount, $period);
                $status = json_encode(array("responseCode" => "00", "message" => "success", "customer_name" => $customer_name, "merchant_id" => $merchant_id, "email" => $customer_email, "phone" => $customer_phone, "application" => $application_id));
                $this->newMail($customer_email, $merchant_name, $application_id, $customer_name, $amount, $period, 'https://stack.net.ng/stack/dataStackService/inc/templates/newMail.phtml');
            } else {
                $status = json_encode(array("responseCode" => "04", "message" => "fail", "customer_name" => $customer_name, "merchant_id" => $merchant_id, "email" => $customer_email, "phone" => $customer_phone, "application" => "null"));
            }
        }

        $this->service_logs($status);
        return $status;
    }


    public function updateLoanItems($conn, $merchant_id, $applcationId, $amount, $period)
    {
        $merchant_details = $this->fetch_details_by_id($conn, $merchant_id);
        //  var_dump($merchant_details);
        $merchant_name =  $merchant_details['merchant_name'];
        $percentage = $merchant_details['merchant_rate'] * $amount;
        $month = $percentage * $period;
        $total_repayment = $amount + $month;
        $month_repayment = $total_repayment / $period;
        $insurance = $merchant_details['insurrance'] * $amount;
        $processing = $merchant_details['processing_rate'] * $amount;
        $commitment = $merchant_details['commitment'] * $amount;
        $deduction = $insurance + $processing + $commitment;
        $balance = $amount - $deduction;
        $sql = "INSERT INTO `loanitems`(`merchant_id`,`merchant_name`,`application_id`, `interest`, `insurrance`, `processing`, `commitment`, `deduction`, `total_balance`, `repayment_period`, `monthly_repayment`, `total_repayment`) VALUES ('$merchant_id','$merchant_name','$applcationId','$percentage','$insurance','$processing','$commitment','$deduction','$balance','$period','$month_repayment','$total_repayment')";
        $result = mysqli_query($conn, $sql);
    }

    public function fetch_merchant_details_by_id($conn, $merchant_id)
    {
        $json  = array();
        $sql = "SELECT `merchant_id`, `merchant_name`, `merchant_email`,`merchant_public_id`  FROM `merchant_accounts` WHERE merchant_id = '$merchant_id'";
        $result = mysqli_query($conn, $sql);
        $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
        return $row;
    }


    public function fetch_staff_details_by_id($conn, $staff_code)
    {
        $json  = array();
        $sql = "SELECT `merchant_id`, `merchant_name`, `staff_code`, `staff_name`, `staff_phone`, `staff_email`, `staff_unique_key`, `staff_password` FROM `merchant_staff_accounts`  WHERE staff_code = '$staff_code'";
        $result = mysqli_query($conn, $sql);
        $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
        return $row;
    }



    public function fetch_merchant_name_by_id($conn, $merchant_id)
    {
        $json  = array();
        $sql = "SELECT *  FROM `merchant_accounts` WHERE merchant_id = '$merchant_id'";
        $result = mysqli_query($conn, $sql);
        $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
        return $row['merchant_name'];
    }



    public function create_merchant_id()
    {
        $uni = substr(str_shuffle(str_repeat("0123456789", 10)), 0, 10);

        return $uni;
    }

    public function create_application_id()
    {
        $uni = substr(str_shuffle(str_repeat("0123456789", 10)), 0, 10);

        return $uni;
    }
    
     public function create_ticket_id()
    {
        $uni = substr(str_shuffle(str_repeat("0123456789", 7)), 0, 7);

        return $uni;
    }

    public function create_public_id()
    {
        $uni = substr(str_shuffle(str_repeat("0123456789ABCDEFHJKLMNPRSZTUW", 20)), 0, 20);

        return $uni;
    }


    public function create_staff_unique_key()
    {
        $uni = substr(str_shuffle(str_repeat("0123456789ABCDEFHJKLMNPRSZTUW", 15)), 0, 15);

        return $uni;
    }

    public function create_staff_default_password()
    {
        $uni = substr(str_shuffle(str_repeat("0123456789ABCDEFHJKLMNPRSZTUW", 8)), 0, 8);

        return $uni;
    }


    public function create_staff_id()
    {
        $uni = substr(str_shuffle(str_repeat("0123456789", 6)), 0, 6);

        return $uni;
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


    public function createUnique()
    {
        $uni = substr(str_shuffle(str_repeat("0123456789", 10)), 0, 10);

        return $uni;
    }


    public function generateAuth()
    {
        $uni = substr(str_shuffle(str_repeat("0123456789", 4)), 0, 4);

        return $uni;
    }


    public function fetch_banks($conn)
    {
        $json  = array();
        $sql = "SELECT * FROM `bank_codes` ORDER BY `bank_name` ASC";
        $result = mysqli_query($conn, $sql);
        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $json[] = $row;
        }
        return json_encode($json, JSON_PRETTY_PRINT);
    }

    public function update_password($conn, $staff_email, $staff_password, $token)
    {
        $status = "";
        if (empty($token)) {
            $status = json_encode(array("responseCode" => "08", "message" => "invalid_token", "staff_code" => $staff_email, "token" => $token, "timestamp" => date('d-M-Y H:i:s')));
        } else if ($this->validateToken($token) === "true") {
            $sql = "UPDATE `merchant_staff_accounts` SET `staff_password` = '$staff_password', `password_change` = 'Y' WHERE `staff_email` = '$staff_email'";
            $result = mysqli_query($conn, $sql);
            if ($result) {
                $status = json_encode(array("responseCode" => "00", "message" => "success", "email" => $staff_email, "token" => $token, "timestamp" => date('d-M-Y H:i:s')));
            } else {
                $status = json_encode(array("responseCode" => "04", "message" => "fail", "email" => $staff_email, "token" => $token, "timestamp" => date('d-M-Y H:i:s')));
            }
        } else {
            $status = json_encode(array("responseCode" => "08", "message" => "expired_token", "email" => $staff_email, "token" => $token, "timestamp" => date('d-M-Y H:i:s')));
        }

        $this->server_logs($status);
        return $status;
    }


    public function fetchRemitaDetails($conn, $merchant_id, $staff_code,  $application_id, $token)
    {
        $data = "";
        if (empty($token)) {
            $data = json_encode(array("responseCode" => "08", "message" => "invalid_token", "application_id" => $application_id, "staff_code" => $staff_code, "token" => $token, "timestamp" => date('d-M-Y H:i:s')));
        } else if ($this->validateToken($token) === "true") {
            $salaryitems = $this->fetch_salary_items($conn, $merchant_id, $application_id);
            $loanitems = $this->fetch_loan_items($conn, $merchant_id, $application_id);
            //var_dump($salaryitems);
            $sqlSelect = "select * from remitadetails WHERE application_id = '$application_id' AND `merchant_id` = '$merchant_id'";

            $result = mysqli_query($conn, $sqlSelect);
            $r = mysqli_fetch_array($result, MYSQLI_ASSOC);
            if (!empty($r)) {
                $customerId = $r['customerId'];
                $accountNumber = $r['accountNumber'];
                $bvn = $r['bvn'];
                $companyName = $r['companyName'];
                $customerName = $r['customerName'];
                $loanDetails = $this->fetchLoanItemDetails($conn, $application_id);
                $json = array("customerId" => $customerId, "accountNumber" => $accountNumber, "companyName" => $companyName, "customerName" => $customerName, "loanApplicationDetails" => $loanDetails, "salaryPaymentDetails" => $salaryitems, "loanHistoryDetails" => $loanitems);
                $data = json_encode($json, JSON_PRETTY_PRINT);
            } else {
                $data =  json_encode(array("responseCode" => "07", "message" => "no_data_found", "application_id" => $application_id, "staff_code" => $staff_code, "token" => $token, "timestamp" => date('d-M-Y H:i:s')));;
            }
        } else {
            $data = json_encode(array("responseCode" => "08", "message" => "expired_token", "application_id" => $application_id, "staff_code" => $staff_code, "token" => $token, "timestamp" => date('d-M-Y H:i:s')));
        }
        $this->server_logs($data);
        return $data;
    }


    public function fetchLoanItemDetails($conn, $application_id)
    {
        $json = array();
        $sql = "SELECT LD.merchant_id, LD.application_id, LD.interest, LD.insurrance, LD.processing, LD.commitment, LD.deduction, LD.repayment_period, LD.monthly_repayment, LA.amount, LA.period, LD.total_repayment, LA.identity_link, LA.passport_link, LA.utility_bills_link FROM loanitems LD, merchant_applications LA WHERE LA.application_id = LD.application_id AND LA.application_id = '$application_id'";
        $result = mysqli_query($conn, $sql);
        while ($r = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $json[] = $r;
        }

        return $json;
    }


    public function fetch_salary_items($conn, $merchant_id, $application_id)
    {
        $json = array();

        $sqlSelect = "SELECT * FROM `remitasalaryhistory`  WHERE application_id = '$application_id' AND `merchant_id` = '$merchant_id'";
        $result = mysqli_query($conn, $sqlSelect);
        while ($r = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $json[] = $r;
        }

        return $json;
    }


    public function fetch_loan_items($conn, $merchant_id, $application_id)
    {
        $json = array();
        $status  = "";
        $sql = "SELECT * FROM remitaloanhistory where application_id= '$application_id' AND `merchant_id` = '$merchant_id'";
        $result = mysqli_query($conn, $sql);
        while ($r = mysqli_fetch_array($result, MYSQLI_ASSOC)) {

            $json[] = $r;
        }


        return $json;
    }


    public function validateToken($token)
    {
        $secret = 'sec!ReT423*&';

        $result = Token::validate($token, $secret);
        $converted_res = $result ? 'true' : 'false';
        return '' . $converted_res;
    }

    public function getIPAddress()
    {
        //whether ip is from the share internet  
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }





    public function fetch_application_by_id($conn, $merchant_id, $application_id)
    {
        $json = array();

        $sql = "SELECT APP.application_id, APP.customer_phone, APP.customer_email, APP.customer_name,APP.customer_bvn, APP.account_number, APP.bank_code, APP.period, APP.amount, LOAN.interest, LOAN.monthly_repayment, LOAN.total_repayment, REM.customerId, REM.authcode FROM merchant_applications APP , loanitems LOAN, remitadetails REM WHERE APP.application_id = LOAN.application_id AND APP.application_id = REM.application_id AND APP.application_id = '$application_id' AND APP.merchant_id = '$merchant_id'";
        $result = mysqli_query($conn, $sql);
        while ($r = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $json[] = $r;
        }

        return $json;
    }


    public function processRemitaRecords($conn, $merchant_id, $applcationId, $account_number, $bank_code, $bvn)
    {

        $status = "";
        $service = '';
        $merchant_array = $this->fetch_details_by_id($conn, $merchant_id);
        //$merchantId = "8797039389";
        //$apiKey = "QzAwMDAxNjE5NTcxMjM0fEMwMDAwMTYxOTU3";
        //$apiToken = "VUtlOWlhWFhua29IbmpieCs2Y1VxMUNudlltbDE1NUtnMWNIWVV3MTJzTHkyQkpQRzJCRHM2WFQ3MldPOVJjUA==";
        $merchantId = $merchant_array['merchant_remita_id'];
        $apiKey = $merchant_array['merchant_remita_key'];
        $apiToken = $merchant_array['merchant_remita_apitoken'];
        $merchant_name = $merchant_array['merchant_name'];

        $requestId = substr(str_shuffle(str_repeat("0123456789", 13)), 0, 13);
        $randomnumber = substr(str_shuffle(str_repeat("0123456789", 6)), 0, 6);
        $apiHash = $apiKey . $requestId . $apiToken;
        $key = hash("sha512", $apiHash);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://login.remita.net/remita/exapp/api/v1/send/api/loansvc/data/api/v2/payday/salary/history/ph',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
            "authorisationCode":"' . $randomnumber . '",
            "firstName": "",
            "lastName": "",
            "middleName": "",
            "accountNumber": "' . $account_number . '",
            "bankCode": "' . $bank_code . '",
            "bvn": "' . $bvn . '",
            "authorisationChannel":"USSD"
            }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Api_Key: ' . $apiKey,
                'Merchant_id: ' . $merchantId,
                'Request_id: ' . $requestId,
                'Authorization: remitaConsumerKey=' . $apiKey . ', remitaConsumerToken=' . $key,
                'Cookie: b1pi=!mQF8/cm3bHscyWiVjSCXkaJ//s58RAVA9mRRAqDgCCphpRjbeloobJkZGFlGEn/1B6uZKwOZcWr6'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $remResponse = json_decode($response);
        $validate =  $remResponse->responseMsg;
        if ($validate == 'SUCCESS') {
            $this->getRemitaDetails($conn, $merchant_id, $merchant_name, $applcationId, $response, $randomnumber);
            $status = json_encode(array("status" => "success"), JSON_PRETTY_PRINT);
            $service = $merchant_id . '--' . $merchant_name . '--' . $applcationId . '--' . $randomnumber . '--' . $remResponse->responseMsg;
            $this->call_logs($conn, $merchant_id, $merchant_name, $applcationId, 'REMITA-SALARY-DETAILS-FOR-' . $applcationId . '-REQID-' . $requestId, 'SUCCESSFULL');
        } else {
            $status = json_encode(array("status" => "error"), JSON_PRETTY_PRINT);
            $service = $merchant_id . '--' . $merchant_name . '--' . $applcationId . '--' . $randomnumber . '--' . $remResponse->responseMsg;
            $this->call_logs($conn, $merchant_id, $merchant_name, $applcationId, 'REMITA-SALARY-DETAILS-FOR-' . $applcationId . '-REQID-' . $requestId, 'FAILED');
        }
        $this->service_logs($service);
        return $status;
    }

    public function getRemitaDetails($conn, $merchant_id, $merchant_name, $application_id, $response, $randomnumber)
    {

        $data = json_decode($response, true);

        $customer_id = trim(mysqli_real_escape_string($conn, !empty($data['data']['customerId']) ? $data['data']['customerId'] : ""));
        $account_number = trim(mysqli_real_escape_string($conn, !empty($data['data']['accountNumber']) ? $data['data']['accountNumber'] : ""));
        $bankCode = trim(mysqli_real_escape_string($conn, !empty($data['data']['bankCode']) ? $data['data']['bankCode'] : ""));
        $bvn = trim(mysqli_real_escape_string($conn, !empty($data['data']['bvn']) ? $data['data']['bvn'] : ""));
        $companyName = trim(mysqli_real_escape_string($conn, !empty($data['data']['companyName']) ? $data['data']['companyName'] : ""));
        $customerName = trim(mysqli_real_escape_string($conn, !empty($data['data']['customerName']) ? $data['data']['customerName'] : ""));
        $category = trim(mysqli_real_escape_string($conn, !empty($data['data']['category']) ? $data['data']['category'] : ""));
        $firstPayment = trim(mysqli_real_escape_string($conn, !empty($data['data']['firstPaymentDate']) ? $data['data']['firstPaymentDate'] : ""));
        $salaryCount = trim(mysqli_real_escape_string($conn, !empty($data['data']['salaryCount']) ? $data['data']['salaryCount'] : ""));

        $this->insertRemitaCustomerDetails(
            $conn,
            $merchant_id,
            $merchant_name,
            $application_id,
            $customer_id,
            $account_number,
            $bankCode,
            $bvn,
            $companyName,
            $customerName,
            $category,
            $firstPayment,
            $salaryCount,
            $randomnumber
        );

        foreach ($data['data']['salaryPaymentDetails'] as $accounts) {
            $paymentDate = trim(mysqli_real_escape_string($conn, !empty($accounts['paymentDate']) ? $accounts['paymentDate'] : ""));
            $amount = trim(mysqli_real_escape_string($conn, !empty($accounts['amount']) ? $accounts['amount'] : ""));
            $accountNumber = trim(mysqli_real_escape_string($conn, !empty($accounts['accountNumber']) ? $accounts['accountNumber'] : ""));
            $bankCodes = trim(mysqli_real_escape_string($conn, !empty($accounts['bankCode']) ? $accounts['bankCode'] : ""));
            $this->insertSalaryDetails($conn, $merchant_id, $merchant_name, $application_id, $paymentDate, $amount, $accountNumber, $bankCodes);
        }


        foreach ($data['data']['loanHistoryDetails'] as $loans) {
            $loanProvider = trim(mysqli_real_escape_string($conn, !empty($loans['loanProvider']) ? $loans['loanProvider'] : ""));
            $loanAmount = trim(mysqli_real_escape_string($conn, !empty($loans['loanAmount']) ? $loans['loanAmount'] : ""));
            $outstandingAmount = trim(mysqli_real_escape_string($conn, !empty($loans['outstandingAmount']) ? $loans['outstandingAmount'] : ""));
            $loanDisbursementDate = trim(mysqli_real_escape_string($conn, !empty($loans['loanDisbursementDate']) ? $loans['loanDisbursementDate'] : ""));
            $status_loan = trim(mysqli_real_escape_string($conn, !empty($loans['status']) ? $loans['status'] : ""));
            $repaymentAmount = trim(mysqli_real_escape_string($conn, !empty($loans['repaymentAmount']) ? $loans['repaymentAmount'] : ""));
            $repaymentFreq = trim(mysqli_real_escape_string($conn, !empty($loans['repaymentFreq']) ? $loans['repaymentFreq'] : ""));
            $this->insertLoanDetails($conn, $merchant_id, $merchant_name, $application_id, $loanProvider, $loanAmount, $outstandingAmount, $loanDisbursementDate, $status_loan, $repaymentAmount, $repaymentFreq);
        }
    }


    public function insertLoanDetails(
        $conn,
        $merchant_id,
        $merchant_name,
        $application_id,
        $loanProvider,
        $loanAmount,
        $outstandingAmount,
        $loanDisbursementDate,
        $status_loan,
        $repaymentAmount,
        $repaymentFreq
    ) {

        $sql = "INSERT INTO `remitaloanhistory`(`merchant_id`,`merchant_name`,`application_id`, `loanProvider`, `loanAmount`, `outstandingAmount`, `loanDisbursementDate`, `status`, `repaymentAmount`, `repaymentFreq`) VALUES 
        ('$merchant_id','$merchant_name','$application_id','$loanProvider','$loanAmount','$outstandingAmount','$loanDisbursementDate','$status_loan','$repaymentAmount','$repaymentFreq')";
        $result = mysqli_query($conn, $sql);
    }

    public function insertSalaryDetails($conn, $merchant_id, $merchant_name, $application_id, $paymentDate, $amount, $accountNumber, $bankCode)
    {
        $bankName = $this->fetchBankName($conn, $bankCode);
        $sql = "INSERT INTO `remitasalaryhistory`(`merchant_id`,`merchant_name`,`application_id`, `paymentDate`, `amount`, `accountNumber`, `bankCode`, `bankName`) VALUES
         ('$merchant_id','$merchant_name','$application_id','$paymentDate','$amount','$accountNumber','$bankCode','$bankName')";
        $result = mysqli_query($conn, $sql);
    }

    public function insertRemitaCustomerDetails(
        $conn,
        $merchant_id,
        $merchant_name,
        $application_id,
        $customer_id,
        $account_number,
        $bankCode,
        $bvn,
        $companyName,
        $customerName,
        $category,
        $firstPayment,
        $salaryCount,
        $randomnumber
    ) {
        $bankName = $this->fetchBankName($conn, $bankCode);
        $sql = "INSERT INTO `remitadetails`(`merchant_id`,`merchant_name`,`application_id`, `customerId`, `accountNumber`, `bankCode`, `bankName`, `bvn`, `companyName`, `customerName`, `category`, `firstPaymentDate`, `salaryCount`,`authcode`) VALUES
         ('$merchant_id','$merchant_name','$application_id','$customer_id','$account_number','$bankCode','$bankName','$bvn','$companyName','$customerName','$category','$firstPayment','$salaryCount','$randomnumber')";
        $result = mysqli_query($conn, $sql);
    }

    public function fetchBankName($conn, $bankCode)
    {
        $sql = "SELECT * FROM bank_codes WHERE remita_code = '$bankCode'";
        $res = mysqli_query($conn, $sql);
        $arr = mysqli_fetch_array($res);
        $bank = $arr['bank_name'];
        return $bank;
    }


    public function fetch_details_by_id($conn, $merchant_id)
    {
        $json  = array();
        $sql = "SELECT * FROM `merchant_accounts` WHERE merchant_id = '$merchant_id'";
        $result = mysqli_query($conn, $sql);
        $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
        return $row;
    }



    public function call_logs($conn, $merchant_id, $merchant_name, $applcationId, $service_name, $status_message)
    {
        $sql = "INSERT INTO `merchant_call_logs`(`merchant_id`, `merchant_name`,`status_code`, `service_name`, `status_message`, `status`) VALUES
         ('$merchant_id','$merchant_name','$applcationId','$service_name','$status_message','Y')";
        $result = mysqli_query($conn, $sql);
    }

    public function server_logs($log_msg)
    {

        $log_filename = "server_logs";
        if (!file_exists($log_filename)) {
            // create directory/folder uploads.
            mkdir($log_filename, 0777, true);
        }
        $log_file_data = $log_filename . '/log_' . date('d-M-Y') . '.log';
        // if you don't add `FILE_APPEND`, the file will be erased each time you add a log
        file_put_contents($log_file_data, $log_msg . "\n", FILE_APPEND);
    }


    public function service_logs($log_msg)
    {

        $log_filename = "service_logs";
        if (!file_exists($log_filename)) {
            // create directory/folder uploads.
            mkdir($log_filename, 0777, true);
        }
        $log_file_data = $log_filename . '/log_' . date('d-M-Y') . '.log';
        // if you don't add `FILE_APPEND`, the file will be erased each time you add a log
        file_put_contents($log_file_data, $log_msg . "\n", FILE_APPEND);
    }



    //mailSection
    
    public function support($merchant_email,$merchant_name,$title,$message,$staff_code,$staff_email,$attachment,$filename){
        
        $template = 'http://app.stack.net.ng/dataStackService/inc/templates/supportMail.phtml';
        $body = file_get_contents($template);
        $body = str_replace('%merchant%', $merchant_name, $body);
        $body = str_replace('%message%', $message, $body);
        $body = str_replace('%staff%', $staff_code, $body);
        $mail = new PHPMailer(true);
        try {

            //$mail->SMTPDebug = 3;                      
            $mail->isSMTP();                                            // Send using SMTP
            $mail->Host       = 'twentyseven.qservers.net';                    // Set the SMTP server to send through
            $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
            $mail->Username   = 'support@stack.net.ng';                     // SMTP username
            $mail->Password   = 'Datastack_2023';                                 // SMTP password
            $mail->SMTPSecure  = 'ssl';
            $mail->Port       = 465;
            $mail->setFrom('support@stack.net.ng', 'Stack For ' . $merchant_name);
            $mail->addAddress($merchant_email, $merchant_name);
            $mail->addCC('support@stack.net.ng','Stack Support');
            $mail->addCC($staff_email,'Staff Account');
            $mail->addBCC('omotayotemi47@gmail.com','Support');
            $mail->addBCC('omodarakayode@gmail.com','Support');
            $mail->isHTML(true);                                  // Set email format to HTML  
            $mail->Subject = '#'.$title;
            $mail->Body    = $body;
            $mail->addStringAttachment(file_get_contents($attachment),'attached.png');
            //$mail->AddEmbeddedImage('../endpoints/documents/util/'.$filename, 'logo_2u');

            $mail->send();
            $this->mailer_logs('Mail Sent Successfully To ' . $merchant_email . ' MERCHANT : ' . $merchant_name . ' TIMESTAMP : ' . date('Y-m-d : h:m:s'));
        } catch (Exception $e) {
            //	 echo "Message could not be sent. Mailer Error: {$e->ErrorInfo}";
            $this->mailer_logs('Mail Sending Error ' . $merchant_email . ' MERCHANT : ' . $merchant_name . ' TIMESTAMP : ' . date('Y-m-d : h:m:s'));
        }
    }
    
    public function acceptMail($email, $merchant_name, $application_id, $name, $amount, $period, $template)
    {


        $body = file_get_contents($template);
        $body = str_replace('%merchant%', $merchant_name, $body);
        $body = str_replace('%name%', $name, $body);
        $body = str_replace('%amount%', $amount, $body);
        $body = str_replace('%app%', $application_id, $body);
        $body = str_replace('%period%', $period, $body);
        $mail = new PHPMailer(true);
        try {

            //$mail->SMTPDebug = 3;                      
            $mail->isSMTP();                                            // Send using SMTP
            $mail->Host       = 'twentyseven.qservers.net';                    // Set the SMTP server to send through
            $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
            $mail->Username   = 'no-reply@stack.net.ng';                     // SMTP username
            $mail->Password   = 'Javax.swing_2022';                                 // SMTP password
            $mail->SMTPSecure  = 'ssl';
            $mail->Port       = 465;
            $mail->setFrom('no-reply@stack.net.ng', 'Stack For ' . $merchant_name);
            $mail->addAddress($email, $name);
            $mail->isHTML(true);                                  // Set email format to HTML  
            $mail->Subject = 'Application Status';
            $mail->Body    = $body;
            //  $mail->AddEmbeddedImage('logo-icon.png', 'logo_2u');

            $mail->send();
            $this->mailer_logs('Mail Sent Successfully To ' . $email . ' MERCHANT : ' . $merchant_name . ' TIMESTAMP : ' . date('Y-m-d : h:m:s'));
        } catch (Exception $e) {
            //	 echo "Message could not be sent. Mailer Error: {$e->ErrorInfo}";
            $this->mailer_logs('Mail Sending Error ' . $email . ' MERCHANT : ' . $merchant_name . ' TIMESTAMP : ' . date('Y-m-d : h:m:s'));
        }
    }

    public function rejectMail($email, $merchant_name, $application_id, $name, $amount, $period, $template)
    {


        $body = file_get_contents($template);
        $body = str_replace('%merchant%', $merchant_name, $body);
        $body = str_replace('%name%', $name, $body);
        $body = str_replace('%amount%', $amount, $body);
        $body = str_replace('%app%', $application_id, $body);
        $body = str_replace('%period%', $period, $body);
        $mail = new PHPMailer(true);
        try {

            //$mail->SMTPDebug = 3;                      
            $mail->isSMTP();                                            // Send using SMTP
            $mail->Host       = 'twentyseven.qservers.net';                    // Set the SMTP server to send through
            $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
            $mail->Username   = 'no-reply@stack.net.ng';                     // SMTP username
            $mail->Password   = 'Javax.swing_2022';                                 // SMTP password
            $mail->SMTPSecure  = 'ssl';
            $mail->Port       = 465;
            $mail->setFrom('no-reply@stack.net.ng', 'Stack For ' . $merchant_name);
            $mail->addAddress($email, $name);
            $mail->isHTML(true);                                  // Set email format to HTML  
            $mail->Subject = 'Application Status';
            $mail->Body    = $body;
            // $mail->AddEmbeddedImage('logo-icon.png', 'logo_2u');

            $mail->send();
            //echo 'Message has been sent';
            $this->mailer_logs('Mail Sent Successfully To ' . $email . ' MERCHANT : ' . $merchant_name . ' TIMESTAMP : ' . date('Y-m-d : h:m:s'));
        } catch (Exception $e) {
            //	 echo "Message could not be sent. Mailer Error: {$e->ErrorInfo}";
            $this->mailer_logs('Mail Sending Error ' . $email . ' MERCHANT : ' . $merchant_name . ' TIMESTAMP : ' . date('Y-m-d : h:m:s'));
        }
    }


    public function newMail($email, $merchant_name, $application_id, $name, $amount, $period, $template)
    {

        // echo 'i got here' . $email;
        $body = file_get_contents($template);
        $body = str_replace('%merchant%', $merchant_name, $body);
        $body = str_replace('%name%', $name, $body);
        $body = str_replace('%amount%', $amount, $body);
        $body = str_replace('%app%', $application_id, $body);
        $body = str_replace('%period%', $period, $body);
        $mail = new PHPMailer(true);
        try {

            //	$mail->SMTPDebug = 3;                      
            $mail->isSMTP();                                            // Send using SMTP
            $mail->Host       = 'twentyseven.qservers.net';                    // Set the SMTP server to send through
            $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
            $mail->Username   = 'no-reply@stack.net.ng';                     // SMTP username
            $mail->Password   = 'Javax.swing_2022';                                 // SMTP password
            $mail->SMTPSecure  = 'ssl';
            $mail->Port       = 465;
            $mail->setFrom('no-reply@stack.net.ng', 'Stack For ' . $merchant_name);
            $mail->addAddress($email, $name);
            $mail->isHTML(true);                                  // Set email format to HTML  
            $mail->Subject = 'New Application';
            $mail->Body    = $body;
            //  $mail->AddEmbeddedImage('http://localhost/dataStackService/inc/templates/logo-icon.png', 'logo_2u');

            $mail->send();
            $this->mailer_logs('Mail Sent Successfully To ' . $email . ' MERCHANT : ' . $merchant_name . ' TIMESTAMP : ' . date('Y-m-d : h:m:s'));
        } catch (Exception $e) {
            //	 echo "Message could not be sent. Mailer Error: {$e->ErrorInfo}";
            $this->mailer_logs('Mail Sending Error ' . $email . ' MERCHANT : ' . $merchant_name . ' TIMESTAMP : ' . date('Y-m-d : h:m:s'));
        }
    }


    public function mailer_logs($log_msg)
    {

        $log_filename = "mail_logs";
        if (!file_exists($log_filename)) {
            // create directory/folder uploads.
            mkdir($log_filename, 0777, true);
        }
        $log_file_data = $log_filename . '/log_' . date('d-M-Y') . '.log';
        // if you don't add `FILE_APPEND`, the file will be erased each time you add a log
        file_put_contents($log_file_data, $log_msg . "\n", FILE_APPEND);
    }
}

$portal = new PortalUtility();
//echo $portal->create_account($conn, '$first_name',' $last_name', '$middle_name',' $phone', '$email', '$password', '$firebaseKey');
//$portal->fetch_account_details($conn,'5164733824');
//$portal->testLogs();
//echo ($portal->validateToken('eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjoib21vdGF5b3RlbWk0N0BnbWFpbC5jb21NQ0JXQzAzMCIsImV4cCI6MTY2Njg5NTA4NiwiaXNzIjoibG9jYWxob3N0IiwiaWF0IjoxNjY2ODkxNDg2fQ.LI5ViixClLrJInJAId0DVlGlAIiwcGaP_ptovu2YGRA'));
//var_dump($portal->validateStaff($conn,'8294523827', 'omotayotemi47@gmail.com', 'MCBWC030'));
//echo $portal->fetch_repayment_details($conn,'8294523827', '2283251965');
