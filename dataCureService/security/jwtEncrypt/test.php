<?php

use ReallySimpleJWT\Token;

require 'vendor/autoload.php';
include('dbconnection.php');
include('../logs/logs.php');
$database = new database();
//$conn = $database->getConnection();
$conn = $database->getMysqlConnection();

class PortalUtility
{

    public $API_KEY_DEV = 'ada75cc9-a36d-418c-b19a-1b88676b770a';
    private $USERNAME_DEV = 'PAUL';
    private $API_KEY = 'dc8509a2e9bbebe435c43b38d7243988';
    private $USERNAME = 'BERNINI47';
    private $DEMO_URL = '';
    private $LIVE_URL = 'https://mobilenig.com/API/airtime_premium?';

    public function create_account($conn, $first_name, $last_name, $middle_name, $phone, $email, $password, $firebaseKey, $bvn, $birth_date, $address, $gender)
    {
        $status = "";
        $bank_id = '1232330';
        $myMobile = '234' . ltrim($phone, '0');
        $full_name  = $last_name . ' ' . $first_name . ' ' . $middle_name;
        $othernames  =  $first_name . ' ' . $middle_name;
        $genCode = '';
        if ($gender == 'Male') {
            $genCode = '0';
        } else {
            $genCode = '1';
        }

        $json = $this->createRichwayAccount($last_name, $othernames, $full_name, $phone, $email, $genCode);
        $account_id =  $json['Message']['AccountNumber'];
        $richCustomerId = $json['Message']['CustomerID'];
        $bankOneAccount = $json['Message']['BankoneAccountNumber'];
        $bankAccountName = $json['Message']['FullName'];
        // $data = array("account_number"=>$richAcct, "account_id"=>$richCustomerId,"bank_one"=>$bankOneAccount,"bank_one_name"=>$bankAccountName);
        //$image_string = $this->renderImage($conn,$account_id,$image);
        //$account_id = $this->createUnique();
        $auth = $this->generateAuth();
        $sql = "INSERT INTO `accounts`(`bank_id`, `user_id`, `full_name`, `first_name`, `last_name`, `middle_name`, `phone`, `email`, `password`, `firebaseKey`, `token`, `active`,`kyc_level`, `customer_id`, `bank_one`, `bank_full_name`, `passactive`) VALUES ('$bank_id','$account_id','$full_name','$first_name','$last_name','$middle_name','$myMobile','$email','$password','$firebaseKey','$auth', 'N','2','$richCustomerId','$bankOneAccount','$bankAccountName','N')";
        $result = mysqli_query($conn, $sql);
        if ($result) {
            $this->update_kyc($conn, $account_id, $bvn, $address, 'N', 'N', 'N', $birth_date);
            $status = $this->sendSms($phone, $auth);
            //$status = json_encode(array("responseCode" => "04", "message" => "unsuccessful"));
        } else {
            $status = json_encode(array("status" => "error", "responseCode" => "04", "message" => "unsuccessful"));
        }
        return $status;
    }



    public function validateBVN($bvn)
    {

        $login_details = $this->loginToCRS();
        $obj = json_decode($login_details);
        $session_code = $obj->SessionCode;
        $agent_id = $obj->AgentID;
        $subscriber_id = $obj->SubscriberID;

        $data = "";
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api2.creditregistry.com/nigeria/AutoCred/v7/api/Customers/FindByBVN2',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
    "MaxRecords": 100,
    "MinRelevance": 0,
    "SessionCode": "' . $session_code . '",
    "BVN": "' . $bvn . '"
  }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        // echo $response;
        $json = json_decode($response, true);
        $status = $json['Success'];
        if ($status == 'true') {
            $full_name =  $json['SearchResult'][0]['Name'];
            $dob =  $json['SearchResult'][0]['DOBI'];
            $data = json_encode(array("status" => "true", "full_name" => $full_name, "dob" => $dob), JSON_PRETTY_PRINT);
        } else {
            $data = json_encode(array("status" => "false", "full_name" => "null", "dob" => "null"), JSON_PRETTY_PRINT);
        }

        return $data;
    }


    public function loginToCRS()
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api2.creditregistry.com/nigeria/AutoCred/v7/api/Agents/Login',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
    "EmailAddress": "info@richwaymfb.com",
    "SubscriberID": "736512736204628456",
    "Password": "richwaymfb2022"
}',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response =  curl_exec($curl);

        curl_close($curl);
        return $response;
    }


    public function activateToken($conn, $token)
    {
        $status  = "";
        $sql = "SELECT * FROM `accounts` WHERE `token` = '$token'";
        $stmt = mysqli_query($conn, $sql);
        $num = mysqli_num_rows($stmt);
        $row = mysqli_fetch_array($stmt, MYSQLI_ASSOC);
        if ($num > 0) {
            $token =  $row['token'];
            $account_id =  $row['user_id'];
            $kyc =  $row['kyc_level'];
            $this->updateActiveFlag($conn, $token);
            $status = $this->fetch_account_details($conn, $account_id);
        } else {
            $status = json_encode(array("status" => "error", "token" => "null"));
        }

        return $status;
    }


    public function resendToken($conn, $phone)
    {
        $auth = $this->generateAuth();
        //$phone = $this->fetchMobileByAccount($conn, $account_id);
        $this->updateAuthCode($conn, $phone, $auth);
        return $this->sendSms($phone, $auth);
    }


    public function fetchMobileByAccount($conn, $account_id)
    {
        $sql = "SELECT * FROM `accounts` WHERE `user_id` = '$account_id'";
        $stmt = mysqli_query($conn, $sql);
        $row = mysqli_fetch_array($stmt, MYSQLI_ASSOC);
        $phone =  $row['phone'];
        return $phone;
    }


    public function updateActiveFlag($conn, $token)
    {
        $sql = "UPDATE accounts SET `active` = 'Y' WHERE `token` = '$token'";
        $stmt = mysqli_query($conn, $sql);
    }

    public function updateAuthCode($conn, $phone, $auth)
    {
        $sql = "UPDATE accounts SET `token` = '$auth' WHERE `phone` = '$phone'";
        $stmt = mysqli_query($conn, $sql);
    }


    public function updateTranspass($conn, $account_id, $pass)
    {
        $status = "";
        $sql = "UPDATE accounts SET `transpass` = '$pass', `passactive` = 'Y' WHERE `user_id` = '$account_id'";
        $stmt = mysqli_query($conn, $sql);
        if ($stmt) {
            $status =  json_encode(array("responseCode" => "00", "message" => "success"));
        } else {
            $status =  json_encode(array("responseCode" => "04", "message" => "error"));
        }
        return $status;
    }



    public function createSavings($conn, $amount, $interest, $tenure, $customer_id, $product_code, $liquidation_account)
    {
        //interest date
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://52.168.85.231/BankOneWebAPI/api/FixedDeposit/CreateFixedDepositAcct2/2?authtoken=ada75cc9-a36d-418c-b19a-1b88676b770a',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
        "IsDiscountDeposit": true,
        "Amount": ' . $amount . ',
        "InterestRate": ' . $interest . ',
        "Tenure": ' . $tenure . ',
        "CustomerID": "' . $customer_id . '",
        "ProductCode": "' . $product_code . '",
        "LiquidationAccount": "' . $liquidation_account . '",
        "InterestAccrualCommenceDate": "2022-06-08T10:48:02.937Z"
        }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }

    public function insertSavings($conn, $account_id, $savings_name, $savings_amount, $frequency_code, $rate, $start_date, $end_date)
    {
        $total_days = $this->freqDays($start_date, $end_date);
        $status = "";
        $sql = "INSERT INTO `savings`(`user_id`, `savings_id`, `savings_name`, `savings_amount`, `frequency_code`, `rate`, `start_date`, `end_date`, `total_days`, `total_amount`, `total_interest`, `status`) VALUES ()";
    }


    public function fetch_account_details($conn, $account_id)
    {

        $sql = "SELECT * FROM `accounts` WHERE `user_id` = '$account_id'";
        $stmt = mysqli_query($conn, $sql);
        $row = mysqli_fetch_array($stmt, MYSQLI_ASSOC);
        $email =  $row['email'];
        $password =  $row['password'];
        $kyc =  $row['kyc_level'];
        $fullname = $row['bank_full_name'];
        $active = $row['active'];
        $pass = $row['passactive'];
        $customer_id = $row['customer_id'];

        return $this->login_users($conn, $account_id, $email, $password, $kyc, $fullname, $pass, $customer_id);
    }





    public function login_users($conn, $account_id, $email, $password, $kyc, $fullname, $pass, $customer_id)
    {
        $status = "";
        if ($this->validateUser($conn, $email, $password) > 0) {
            $userId = $email . $password;
            $secret = 'sec!ReT423*&';
            $expiration = time() + 3600;
            $issuer = 'localhost';

            $token = Token::create($userId, $secret, $expiration, $issuer);
            //echo $token;
            $status =  json_encode(array("account_id" => $account_id, "account_name" => $fullname, "email" => $email, "tokenType" => "Bearer", "expiresIn" => "3600", "accessToken" => $token, "kyc" => $kyc, "customer_id" => $customer_id, "passActive" => $pass));
        } else {
            $status =  json_encode(array("responseCode" => "04", "message" => "invalidCredentials"));
        }

        return $status;
    }

    public function login_users_mobile($conn, $email, $phone, $password, $firebaseKey)
    {
        $status = "";
        $user_data = $this->fetchAccountIdMobile($conn, $email, $phone, $password);
        if ($this->validateUserMobile($conn, $email, $phone, $password, $firebaseKey) > 0) {
            $userId = $email . $password;
            $secret = 'sec!ReT423*&';
            $expiration = time() + 3600;
            $issuer = 'localhost';

            $token = Token::create($userId, $secret, $expiration, $issuer);
            //echo $token;
            $account_id = $user_data['user_id'];
            $fullname = $user_data['bank_full_name'];
            $kyc = $user_data['kyc_level'];
            $active = $user_data['active'];
            $pass = $user_data['passactive'];
            $mail = $user_data['email'];
            $customer_id = $user_data['customer_id'];
            if ($active == 'N') {
                $status =  json_encode(array("responseCode" => "02", "status" => "success", "account_id" => $account_id, "message" => "inactive"));
            } else {
                $status =  json_encode(array("responseCode" => "00", "status" => "success", "message" => "active", "account_id" => $account_id, "account_name" => $fullname, "email" => $mail, "tokenType" => "Bearer", "expiresIn" => "3600", "accessToken" => $token, "kyc" => $kyc, "customer_id" => $customer_id, "passActive" => $pass));
            }
        } else {
            $status =  json_encode(array("responseCode" => "04", "status" => "error", "message" => "invalidCredentials"));
        }

        return $status;
    }


    public function fetchAccountId($conn, $email)
    {
        $sql = "SELECT * FROM `accounts` WHERE `email` = '$email'";
        $stmt = mysqli_query($conn, $sql);
        $row = mysqli_fetch_array($stmt, MYSQLI_ASSOC);
        return $row;
    }

    public function fetchAccountIdMobile($conn, $email, $phone, $password)
    {
        $myMobile = '234' . ltrim($phone, '0');
        $sql = "SELECT * FROM `accounts`  WHERE `email` = '$email' OR `phone` = '$myMobile' AND `password` = '$password'";
        $stmt = mysqli_query($conn, $sql);
        $row = mysqli_fetch_array($stmt, MYSQLI_ASSOC);
        return $row;
    }

    public function fetch_user_details($conn, $account_id)
    {
        $sql = "SELECT * FROM `accounts` WHERE `user_id` = '$account_id'";
        $stmt = mysqli_query($conn, $sql);
        $row = mysqli_fetch_array($stmt, MYSQLI_ASSOC);

        return json_encode($row, true);
    }

    public function validateUser($conn, $email, $password)
    {
        $sql = "SELECT * FROM `accounts` WHERE `email` = '$email' AND password = '$password'";
        $stmt = mysqli_query($conn, $sql);
        $count = mysqli_num_rows($stmt);
        return $count;
    }

    public function validateUserMobile($conn, $email, $phone, $password, $firebaseKey)
    {
        $myMobile = '234' . ltrim($phone, '0');
        $sql = "SELECT * FROM `accounts` WHERE `email` = '$email' OR `phone` = '$myMobile' AND `password` = '$password' AND `firebasekey` = '$firebaseKey'";
        $stmt = mysqli_query($conn, $sql);
        $count = mysqli_num_rows($stmt);
        return $count;
    }


    function getBearerToken()
    {
        $headers = $this->getAuthorizationHeader();
        // HEADER: Get the access token from the header
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
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



    public function sendSms($phone, $token)
    {

        $message = 'Your RichwayMfb mobile token is ' . $token;

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://customer.smsprovider.com.ng/api/?username=dtuzzy@yahoo.com&password=bernini47&message=' . $message . '&sender=MobileToken&mobiles=' . $phone,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }



    public function check_existing_record($conn, $Id)
    {
        $query = "SELECT * FROM `notification` where ref_id = '$Id'";
        $stmt = mysqli_query($conn, $query);
        $user = mysqli_num_rows($stmt);
        return $user;
    }


    public function validateToken($token)
    {

        $secret = 'sec!ReT423*&';

        $result = Token::validate($token, $secret);
        return $result;
    }


    public function freqDays($start, $end)
    {
        /*(    $status = "";
        $str  = date_create($start);
        $en   = date_create($end); // Current time and date
        $diff   = date_diff($str, $end);
        return  $diff->d;*/
    }

    public function calculateSavings($amount, $rate, $freq, $days)
    {
        // $interest = 0.15 * 1;
    }


    public function createRichwayAccount($surname, $othernames, $fullname, $phone, $email, $genCode)
    {

        //$passport =  $appData[0]['passport'];
        //echo $gender;
        $applicationId = substr(str_shuffle(str_repeat("0123456789", 10)), 0, 10);
        $randomnumber = substr(str_shuffle(str_repeat("0123456789", 8)), 0, 8);
        $ref = substr(str_shuffle(str_repeat("0123456789", 9)), 0, 9);
        $trk = substr(str_shuffle(str_repeat("0123456789", 9)), 0, 9);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://52.168.85.231/BankOneWebAPI/api/Account/CreateAccountQuick/2?authtoken=' . $this->API_KEY_DEV,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
    "TransactionTrackingRef": "' . $randomnumber . '",
    "CustomerID": "' . $applicationId . '",
    "AccountReferenceNumber": "' . $ref . '",
    "AccountOpeningTrackingRef": "' . $trk . '",
    "ProductCode": "107",
    "LastName": "' . $surname . '",
    "OtherNames": "' . $othernames . '",
    "AccountName": "' . $fullname . '",
    "BVN": "",
    "FullName": "' . $fullname . '",
    "PhoneNo": "' . $phone . '",
    "Gender": ' . $genCode . ',
    "PlaceOfBirth": "",
    "DateOfBirth": "",
    "Address": "",
    "NationalIdentityNo": "",
    "NextOfKinPhoneNo": "",
    "NextOfKinName": "",
    "ReferralPhoneNo": "",
    "ReferralName": "",
    "HasSufficientInfoOnAccountInfo": true,
    "AccountInformationSource": 0,
    "OtherAccountInformationSource": "",
    "AccountOfficerCode": "001",
    "AccountNumber": "",
    "Email": "' . $email . '",
    "CustomerImage": "",
    "IdentificationImage": "",
    "IdentificationImageType": 0,
    "CustomerSignature": "",
    "NotificationPreference": 3,
    "TransactionPermission": 0,
    "AccountTier": 2
}',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        // echo $response; 

        return json_decode($response, true);
        //	return ;
    }


    public function fetchRichwayDetails($customerId)
    {

        $devUrl = 'http://52.168.85.231/BankOneWebAPI/api/Account/GetActiveSavingsAccountsByCustomerID2/2?authtoken=f0227d19-e169-4f05-845d-25699d33324b';
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://52.168.85.231/BankOneWebAPI/api/Account/GetActiveSavingsAccountsByCustomerID2/2?authtoken=' . $this->API_KEY_DEV . '&customerId=' . $customerId,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        //echo $httpcode;
        return json_decode($response, true);
    }



    public function fetch_balance($account_id)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://52.168.85.231/BankOneWebAPI/api/Account/GetAccountByAccountNumber/2?authtoken=' . $this->API_KEY_DEV . '&accountNumber=' . $account_id,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }


    public function doNameInquiry($account_number)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://52.168.85.231/thirdpartyapiservice/apiservice/Account/AccountEnquiry',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
 "AccountNo":"' . $account_number . '",
 "AuthenticationCode":"' . $this->API_KEY_DEV . '"
 
}',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        //echo $response;
        $json = json_decode($response, true);
        $full_name =  $json['Name'];
        return json_encode(array("name" => $full_name), JSON_PRETTY_PRINT);
    }


    public function doInterNameInquiry($account_number, $bank_code)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://52.168.85.231/thirdpartyapiservice/apiservice/Transfer/NameEnquiry',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
        "AccountNumber": "' . $account_number . '",
        "BankCode": "' . $bank_code . '",
        "Token": "f0227d19-e169-4f05-845d-25699d33324b"
        }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        //  return $response;
        $json = json_decode($response, true);
        $full_name =  $json['Name'];
        return json_encode(array("name" => $full_name), JSON_PRETTY_PRINT);
    }


    public function interTransferFunds($conn, $payer_name, $payer_account, $receiver_name, $receiver_account, $transaction_amount, $bank_code, $narration, $password)
    {

        $status = "";
        $validate = $this->validatePassword($conn, $payer_account, $password);
        if ($validate  == 'success') {
            $status = $this->initiateInterTransfer($payer_name, $payer_account, $receiver_name, $receiver_account, $transaction_amount, $bank_code, $narration);
        } else {
            $status = json_encode(array("ResponseCode" => "04", "ResponseMessage" => "Invalid Token"));
        }

        return $status;
    }


    public function transferFunds($conn, $from_account, $to_account, $amount, $narration, $password)
    {

        $status = "";
        $validate = $this->validatePassword($conn, $from_account, $password);
        if ($validate  == 'success') {
            $status = $this->initiateTransfer($from_account, $to_account, $amount, $narration);
        } else {
            $status = json_encode(array("ResponseCode" => "04", "ResponseMessage" => "Invalid Token"));
        }

        return $status;
    }




    public function initiateInterTransfer($payer_name, $payer_account, $receiver_name, $receiver_account, $transaction_amount, $bank_code, $narration)
    {

        $reference = substr(str_shuffle(str_repeat("0123456789", 12)), 0, 12);
        $total = $transaction_amount . '00';
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://52.168.85.231/thirdpartyapiservice/apiservice/Transfer/InterbankTransfer',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
    "Amount": "' . $total . '",
    "AppzoneAccount": "02230012010015676",
    "Payer": "' . $payer_name . '",
    "PayerAccountNumber": "' . $payer_account . '",
    "ReceiverAccountNumber": "' . $receiver_account . '",
    "ReceiverAccountType": "savings",
    "ReceiverBankCode": "' . $bank_code . '",
    "ReceiverPhoneNumber": "",
    "ReceiverName": "' . $receiver_name . '",
    "ReceiverBVN": "",
    "ReceiverKYC": "",
    "Narration": "' . $narration . '",
    "TransactionReference": "' . $reference . '",
    "NIPSessionID": "000000",
    "Token": "2C92D8D0-E0C8-446F-94A2-90B03B1C68A6",
    "ChannelType": "7",
    "EntityCode": "",
    "InstitutionCode": "999996",
    "Identifier": "VWWYWTUWW"
}',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }

    public function initiateTransfer($from_account, $to_account, $amount, $narration)
    {

        $reference = substr(str_shuffle(str_repeat("0123456789", 7)), 0, 7);
        $total = $amount . '00';
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://52.168.85.231/thirdpartyapiservice/apiservice/CoreTransactions/LocalFundsTransfer',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
    "Amount": "' . $total . '",
    "FromAccountNumber": "' . $from_account . '",
    "ToAccountNumber": "' . $to_account . '",
    "RetrievalReference": "' . $reference . '",
    "Narration": "' . $narration . '",
    "AuthenticationKey": "' . $this->API_KEY_DEV . '"
}',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;
    }

    public function validatePassword($conn, $from_account, $password)
    {

        $status = "";
        $sql = "SELECT * FROM `accounts` WHERE `transpass` = '$password' AND `user_id` = '$from_account' AND `passactive` = 'Y'";
        $stmt = mysqli_query($conn, $sql);
        $num = mysqli_num_rows($stmt);
        if ($num > 0) {
            $status = "success";
        } else {
            $status = "error";
        }
        return $status;
    }


    public function fetchTransactions($account_id)
    {

        $date = date("Y-m-d");
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://52.168.85.231/BankOneWebAPI/api/Account/GetTransactions/2?authtoken=' . $this->API_KEY_DEV . '&accountNumber=' . $account_id . '&fromDate=2022-04-19&toDate=' . $date . '&institutionCode=100640&numberOfItems=10',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }


    public function updateKYC($conn, $account_id)
    {


        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://52.168.85.231/BankOneWebAPI/api/Account/UpdateAccountTier2/2?authtoken=' . $this->API_KEY_DEV . '',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
    "AccountNumber": "' . $account_id . '",
    "AccountTier": 3,
    "SkipAddressVerification": true
}',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        //echo $response;
        $json = json_decode($response, true);
        $status =  $json['IsSuccessful'];
        if ($status == true) {
            $this->update_kyc_third($conn, $account_id);
        }
    }


    public function update_kyc($conn, $account_id, $bvn, $address, $util, $pass, $ide, $birth_date)
    {

        $sql = "INSERT INTO `account_information`(`account_id`, `bvn`, `address`, `utility`, `passport`, `identity`, `date_of_birth`, `flag`) VALUES ('$account_id','$bvn','$address','$util','$pass','$ide','$birth_date','N')";
        $result = mysqli_query($conn, $sql);
    }

    public function update_kyc_third($conn, $account)
    {

        $sql = "UPDATE `accounts` SET `kyc_level` = '3' WHERE `user_id` = '$account'";
        $result = mysqli_query($conn, $sql);
    }


    public function submitApplication($conn, $account_number, $bank_name, $bank_code, $fullname, $amount, $period, $account_id)
    {
        $status = "";
        $applcationId = $this->applicationId();
        $sql = "INSERT INTO `customer_application`(`application_id`,`account_number`, `bank_name`,`bank_code`, `fullname`, `amount`, `period`,`user_approved`,`admin_approved`,`account_id`) VALUES ('$applcationId','$account_number','$bank_name','$bank_code','$fullname','$amount','$period','N','N','$account_id')";
        $result  = mysqli_query($conn, $sql);
        if ($result) {

            $this->updateLoanItems($conn, $applcationId, $amount, $period);
            $status = json_encode(array("responseMessage" => "success", "applicationId" => $applcationId));
        } else {
            $status = json_encode(array("responseMessage" => "error", "applicationId" => "null"));
        }


        return $status;
    }


    public function applicationId()
    {
        $unik = "";
        $unik = substr(str_shuffle(str_repeat("0123456789", 10)), 0, 10);

        return $unik;
    }

    public function updateLoanItems($conn, $applcationId, $amount, $period)
    {
        $percentage = 0.06 * $amount;
        $month = $percentage * $period;
        $total_repayment = $amount + $month;
        $month_repayment = $total_repayment / $period;
        $insurance = 0.005 * $amount;
        $processing = 0.01 * $amount;
        $commitment = 0.01 * $amount;
        $deduction = $insurance + $processing + $commitment;
        $balance = $amount - $deduction;

        $sql = "INSERT INTO `loanitems`(`application_id`, `interest`, `insurrance`, `processing`, `commitment`, `deduction`, `total_balance`, `repayment_period`, `monthly_repayment`, `total_repayment`) VALUES ('$applcationId','$percentage','$insurance','$processing','$commitment','$deduction','$balance','$period','$month_repayment','$total_repayment')";
        $result = mysqli_query($conn, $sql);
    }



    public function fetchApplication($conn, $account_id)
    {
        $json = array();

        $sql = "SELECT * FROM `customer_application` WHERE `account_id` = '$account_id' ORDER BY `timestamp` DESC";
        $result = mysqli_query($conn, $sql);
        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $json[] = $row;
        }
        return json_encode($json, JSON_PRETTY_PRINT);
    }


    public function fetchInterBanks()
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://52.168.85.231/thirdpartyapiservice/apiservice/BillsPayment/GetCommercialBanks/2C92D8D0-E0C8-446F-94A2-90B03B1C68A6',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }

    public function fetch_data_bundles_by_vendor($conn, $vendor_id)
    {
        $json = array();

        $sqlSelect = "SELECT * from cable_tv where cable_id = '$vendor_id'";
        $result = mysqli_query($conn, $sqlSelect);
        while ($r = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $json[] = $r;
        }

        return json_encode($json, JSON_PRETTY_PRINT);
    }


    public function fetch_data_by_vendor($conn, $vendor_id)
    {
        $json = array();

        $sqlSelect = "SELECT * from data_bundles where vendor_id = '$vendor_id'";
        $result = mysqli_query($conn, $sqlSelect);
        while ($r = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            $json[] = $r;
        }

        return json_encode($json, JSON_PRETTY_PRINT);
    }


    public function get_airtime($conn, $account, $network, $phone, $amount, $firebaseKey)
    {
        $status = '';

        $transaction_id = substr(str_shuffle(str_repeat("0123456789", 8)), 0, 8);
        $narration = $network . ' AIRTIME-VTU Recharge of NGN' . $amount . ' to ' . $phone . ' #REF' . $transaction_id;

        $token = $this->verifyMobile($conn, $account, $firebaseKey);
        $balance =  $this->verify_balance($account);
        if ($token < 1) {
            $status =  json_encode(array("trans" => "null", "responseCode" => "09", "message" => "invalidRequest"));
        } else if ($balance < $amount) {
            $status =  json_encode(array("trans" => "null", "responseCode" => "06", "message" => "insufficientFunds"));
        } else {

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://mobilenig.com/API/airtime_premium?username=BERNINI47&api_key=dc8509a2e9bbebe435c43b38d7243988&network=' . $network . '&phoneNumber=' . $phone . '&amount=' . $amount . '&trans_id=' . $transaction_id . '&return_url=http://',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET',
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            //echo $response;
            $json = json_decode($response, true);
            $payment_status =  $json['details']['status'];
            if ($payment_status == 'Pending') {
                $this->debit_bills($account, $amount, $narration, $transaction_id);
                $status =  json_encode(array("trans" => $transaction_id, "responseCode" => "00", "message" => "success"));
            } else {
                $status =  json_encode(array("trans" => "null", "responseCode" => "04", "message" => "error"));
            }
        }
        return $status;
    }


    public function get_dataBundle($conn, $vendor, $phone, $product_code, $amount, $account, $firebaseKey)
    {
        $status = '';
        $unique = substr(str_shuffle(str_repeat("0123456789", 10)), 0, 10);
        $narration = $vendor . 'DATA-BUNDLE Recharge of NGN' . $amount . ' to ' . $phone . ' #REF' . $unique;

        $token = $this->verifyMobile($conn, $account, $firebaseKey);
        $balance =  $this->verify_balance($account);
        if ($token < 1) {
            $status =  json_encode(array("trans" => "null", "responseCode" => "09", "message" => "invalidRequest"));
        } else if ($balance < $amount) {
            $status =  json_encode(array("trans" => "null", "responseCode" => "06", "message" => "insufficientFunds"));
        } else {


            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://mobilenig.com/API/data?username=BERNINI47&api_key=dc8509a2e9bbebe435c43b38d7243988&network=' . $vendor . '&phoneNumber=' . $phone . '&product_code=' . $product_code . '&price=' . $amount . '&trans_id=' . $unique . '&return_url=https://mywebsite.com/order_status.asp',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            //echo $response;
            $json = json_decode($response, true);
            $payment_status =  $json['details']['status'];
            if ($payment_status == 'Pending') {
                $this->debit_bills($account, $amount, $narration, $unique);
                $status =  json_encode(array("trans" => $unique, "responseCode" => "00", "message" => "success"));
            } else {
                $status =  json_encode(array("trans" => "null", "responseCode" => "04", "message" => "error"));
            }
        }
        return $status;
    }



    public function cable_inquiry($decoder, $code)
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://mobilenig.com/API/bills/user_check?username=BERNINI47&api_key=dc8509a2e9bbebe435c43b38d7243988&service=' . $code . '&number=' . $decoder,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }


    public function pay_cable_bills($conn, $vendor, $card_number, $product_code, $customer_name, $price, $account_id, $firebaseKey)
    {

        $status = '';
        $slimName = preg_replace('/\s+/', '', strtoupper($customer_name));

        $unique = substr(str_shuffle(str_repeat("0123456789", 10)), 0, 10);

        $narration = $vendor . ' Subscription of NGN' . $price . ' #REF' . $unique;
        $token = $this->verifyMobile($conn, $account_id, $firebaseKey);
        $balance =  $this->verify_balance($account_id);
        $narration = $vendor . ' Subscription of NGN' . $price . ' #REF' . $unique;
        if ($token < 1) {
            $status =  json_encode(array("trans" => "null", "responseCode" => "09", "message" => "invalidRequest"));
        } else if ($balance < $price) {
            $status =  json_encode(array("trans" => "null", "responseCode" => "06", "message" => "insufficientFunds"));
        } else {

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://mobilenig.com/API/bills/' . strtolower($vendor) . '?username=BERNINI47&api_key=dc8509a2e9bbebe435c43b38d7243988&smartno=' . $card_number . '&product_code=' . $product_code . '&customer_name=' . $slimName . '&customer_number=' . $account_id . '&price=' . $price . '&trans_id=' . $unique,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            // echo $response;
            $json = json_decode($response, true);
            $payment_status =  $json['details']['status'];
            if ($payment_status == 'SUCCESSFUL') {
                $this->debit_bills($account_id, $price, $narration, $unique);
                $status =  json_encode(array("trans" => $unique, "responseCode" => "00", "message" => "success"));
            } else {
                $status =  json_encode(array("trans" => "null", "responseCode" => "04", "message" => "error"));
            }
        }

        return $status;
    }

    public function verify_balance($account_id)
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://52.168.85.231/BankOneWebAPI/api/Account/GetAccountByAccountNumber/2?authtoken=' . $this->API_KEY_DEV . '&accountNumber=' . $account_id,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        //return $response;
        $json = json_decode($response, true);
        $status =  $json['AvailableBalance'];
        return str_replace(",", "", $status);
    }

    public function verifyMobile($conn, $account, $firebaseKey)
    {
        $sql = "SELECT * FROM `accounts` WHERE `user_id` = '$account' AND `firebasekey` = '$firebaseKey'";
        $stmt = mysqli_query($conn, $sql);
        $count = mysqli_num_rows($stmt);
        return $count;
    }

    public function debit_bills($from_account, $amount, $narration, $reference)
    {

        //$reference = substr(str_shuffle(str_repeat("0123456789", 7)), 0, 7);
        $total = $amount . '00';
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'http://52.168.85.231/thirdpartyapiservice/apiservice/CoreTransactions/LocalFundsTransfer',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
   		    "Amount": "' . $total . '",
   		    "FromAccountNumber": "' . $from_account . '",
            "ToAccountNumber": "1100036645",
            "RetrievalReference": "' . $reference . '",
            "Narration": "' . $narration . '",
            "AuthenticationKey": "' . $this->API_KEY_DEV . '"
             }',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;
    }


    public function renderImage($conn, $name, $image)
    {
        $unik = substr(str_shuffle(str_repeat("0123456789", 14)), 0, 14);
        $unique = base64_encode($unik . $name);
        $path = "../image_upload/passport/$unique.png";

        $actualpath = "https://richwaymfb.org/digitalBanking/image_upload/passport/$unique.png";
        file_put_contents($path, base64_decode($image));
        return $actualpath;
    }

    //notification
    public function push_notification_android($device_id, $title, $message)
    {
        //API URL of FCM
        $url = 'https://fcm.googleapis.com/fcm/send';

        /*api_key available in:
    Firebase Console -> Project Settings -> CLOUD MESSAGING -> Server key*/
        $api_key = 'AAAAx_KHHVI:APA91bHijv8dEZwLjDGrwRx4imzUHQEPxryj3ufFAVPrQHeFP-qbHYMQokkTSWtusoO4xAPM8O6cZm75XluGlt7ph4szZC0Ok5gb_nr0eFB_iLoAiXMFpiGlEg5IPxdZzLOGbL6vcTAD'; //Replace with yours

        $target = $device_id;

        $fields = array();
        $fields['priority'] = "high";
        $fields['notification'] = [
            "title" => $title,
            "body" => $message,
            'data' => ['message' => $message],
            "sound" => "default"
        ];
        if (is_array($target)) {
            $fields['registration_ids'] = $target;
        } else {
            $fields['to'] = $target;
        }

        //header includes Content type and api key
        $headers = array(
            'Content-Type:application/json',
            'Authorization:key=' . $api_key
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        $result = curl_exec($ch);
        if ($result === FALSE) {
            die('FCM Send Error: ' . curl_error($ch));
        }
        curl_close($ch);
        return $result;
    }
}




$portal = new PortalUtility();
//echo $portal->create_account($conn, '$first_name',' $last_name', '$middle_name',' $phone', '$email', '$password', '$firebaseKey');
//$portal->fetch_account_details($conn,'5164733824');
//$portal->validateUser($conn,'$email', '$password');
//$portal->login_users($conn,'122344','dtuzzy@yahoo.com','1234');
//echo $portal->fetch_user_details($conn,'5164733824');
//$portal->freqDays('2022-03-21', '2022-03-25');
//$portal->sendSms('2348165753518', '453220');
//$portal->createRichwayAccount('Oluwaseun', 'Jesufemi Adekunle','Oluwaseun Jesufemi Adekunle', '07084344644', 'dtuzzy@yahoo.com');
//$portal->fetch_balance('1100036614');
//echo $portal->validateBVN('22175581674');
//echo $portal->fetchTransactions('1100036700');
//echo $portal->fetchApplication($conn,'1100036700');
//echo $portal->doInterNameInquiry('0230650585','035');
//echo $portal->fetch_data_bundles_by_vendor($conn, '1');
//echo $portal->verify_balance('1100036748');
//echo $portal->renderImage($conn,'10223303','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');
