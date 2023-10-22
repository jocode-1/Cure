<?php
session_start();

if(isset($_GET['token'])){
    $token = $_GET['token'];
    $staff_code = $_GET['staff_code'];
    $staff_name = $_GET['staff_name'];
    $staff_email = $_GET['staff_email'];
    $hospital_id = $_GET['hospital_id']; 
    $hospital_name = $_GET['hospital_name']; 
    $password_status = $_GET['password_status'];    
    $password = $_GET['staff_password'];    

   $staffDetails = array("hospital_id"=>$hospital_id,"hospital_name"=>$hospital_name,"staff_name"=>$staff_name,"token"=>$token,"staff_code"=>$staff_code,"staff_email"=>$staff_email,"password_status"=>$password_status,);
   // var_dump($userDetails);
    $_SESSION['login_user'] = $staffDetails;
    header("Location: dashboard.php");
    exit();
}
