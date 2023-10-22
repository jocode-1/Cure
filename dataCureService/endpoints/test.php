<?php

include('db_connection.php');
session_start();
$database = new database();
$conn = $database->getConnection();

class PortalUtility
{

   public function loginUser($conn, $email, $password)
   {
      $num = "";
      //$json = array();
      $query = "SELECT current_email, password from staff_base where current_email = '" . $email . "' and password = '" . $password . "'";
      $result = mysqli_query($conn, $query);
      $r = mysqli_num_rows($result);

      if ($r > 0) {
         $_SESSION['login_user'] = $email;
         $num = 1;
      } else {
         $num = 0;
      }

      return $num;
   }



   public function insertDocument($conn, $user, $title, $description, $file, $size, $ext)
   {
      $status  = "";
      $id = $user . substr(str_shuffle(str_repeat("0123456789ABCTFGHRO", 10)), 0, 10);
      $sql = "INSERT INTO `user_repository`(`staff_id`,`file_id`, `doc_title`, `doc_description`, `staff_state_code`, `doc_url`, `active`,`size`,`extension`)
     VALUES ('$user','$id','$title','$description','1','$file','Y','$size','$ext')";
      $result = mysqli_query($conn, $sql);
      if ($result) {
         $status = json_encode(array("responseCode" => "00", "responseMessage" => "success"), JSON_PRETTY_PRINT);
      } else {
         $status = json_encode(array("responseCode" => "04", "responseMessage" => "error"), JSON_PRETTY_PRINT);
      }

      return $status;
   }


   public function onboardStaff($conn, $staff_full_name, $staff_role, $staff_zone_id, $current_state, $current_level, $email, $phone, $address, $image)
   {
      $status  = "";
      $staff_id = substr(str_shuffle(str_repeat("0123456789", 10)), 0, 10);
      $staff_core_id = substr(str_shuffle(str_repeat("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ", 15)), 0, 15);
      $password = substr(str_shuffle(str_repeat("0123456789", 6)), 0, 6);
      $staff_role_name = $this->fetchStaffName($conn, $staff_role);
      $sql = "INSERT INTO `staff_base`(`staff_id`,`staff_share_id`, `staff_full_name`, `staff_role`, `staff_role_name`, `staff_core_id`, `staff_zone_id`, `current_state`, `current_level`, `current_email`, `current_phone`, `current_address`, `image_str`, `active`,`is_group`,`password`)
       VALUES ('$staff_id','$staff_id','$staff_full_name','$staff_role','$staff_role_name','$staff_core_id','$staff_zone_id','$current_state','$current_level','$email','$phone','$address','http://96.126.123.169/ind.png','Y','N','$password')";
      $result = mysqli_query($conn, $sql);
      if ($result) {
         $this->insertFileDetails($conn, $staff_id);
         $status = json_encode(array("responseCode" => "00", "responseMessage" => "success", "staff_id" => $staff_id), JSON_PRETTY_PRINT);
      } else {
         $status = json_encode(array("responseCode" => "04", "responseMessage" => "error"), JSON_PRETTY_PRINT);
      }

      return $status;
   }


   public function onboardGroup($conn, $group_name, $staff_array)
   {
      $status  = "";
      $json = json_decode($staff_array, true);
      $group_id = substr(str_shuffle(str_repeat("0123456789", 10)), 0, 10);
      foreach ($json as $j) {
         $staff_id = $j;
         $sql = "INSERT INTO `staff_group_base`(`group_id`, `group_name`, `staff_id`, `status`) VALUES 
      ('$group_id','$group_name','$staff_id','Y')";
         $result = mysqli_query($conn, $sql);
      }
      if ($result) {
         $this->onboardFullGroup($conn, $group_id, $group_name);
         $status = json_encode(array("responseCode" => "00", "responseMessage" => "success", "staff_id" => $group_id), JSON_PRETTY_PRINT);
      } else {
         $status = json_encode(array("responseCode" => "04", "responseMessage" => "error"), JSON_PRETTY_PRINT);
      }

      return $status;
   }


   public function onboardFullGroup($conn, $staff_id, $group_name)
   {
      $status  = "";
      $staff_core_id = substr(str_shuffle(str_repeat("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ", 15)), 0, 15);
      $sql = "INSERT INTO `staff_base`(`staff_id`,`staff_share_id`, `staff_full_name`, `staff_role`, `staff_role_name`, `staff_core_id`, `staff_zone_id`, `current_state`, `current_level`, `current_email`, `current_phone`, `current_address`, `image_str`, `active`,`is_group`)
       VALUES ('$staff_id','$staff_id','$group_name','Group','GROUP ROLE','$staff_core_id','GROUP-ZONE','ADMIN','ADMIN','ADMIN','ADMIN','ADMIN','http://96.126.123.169/grp.png','Y','Y')";
      $result = mysqli_query($conn, $sql);
   }

   public function fetchStaffName($conn, $role)
   {
      $sql = "SELECT * FROM `staff_roles` WHERE `role_id` = '$role'";
      $result = mysqli_query($conn, $sql);
      $row  = mysqli_fetch_array($result);
      return $row['role_name'];
   }

   public function fetchUploadedByStaff($conn, $user)
   {
      $json = array();

      $sql = "select * from `user_repository` where `staff_id` = '$user' order by timestamp desc";
      $result = mysqli_query($conn, $sql);
      while ($rows = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
         $json[] = $rows;
      }
      return json_encode($json, JSON_PRETTY_PRINT);
   }
   
   
   public function fetchTopUploadedByStaff($conn, $user)
   {
      $json = array();

      $sql = "select * from `user_repository` where `staff_id` = '$user' order by timestamp desc limit 10";
      $result = mysqli_query($conn, $sql);
      while ($rows = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
         $json[] = $rows;
      }
      return json_encode($json, JSON_PRETTY_PRINT);
   }

   public function fetchAllStaff($conn)
   {
      $json = array();

      $sql = "SELECT * FROM `staff_base` order by timestamp desc";
      $result = mysqli_query($conn, $sql);
      while ($rows = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
         $json[] = $rows;
      }
      return json_encode($json, JSON_PRETTY_PRINT);
   }


   public function fetchAllSharedFileById($conn, $staff_id)
   {
      $json = array();

      $sql = "SELECT * FROM `staff_share` where `sender_id` = '$staff_id' order by timestamp desc";
      $result = mysqli_query($conn, $sql);
      while ($rows = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
         $json[] = $rows;
      }
      return json_encode($json, JSON_PRETTY_PRINT);;
   }

   public function fetchAllReceivedFileById($conn, $staff_id)
   {
      $json = array();

      $sql = "SELECT * FROM `staff_share` where `receiver_id` = '$staff_id' order by timestamp desc";
      $result = mysqli_query($conn, $sql);
      while ($rows = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
         $json[] = $rows;
      }
      return json_encode($json, JSON_PRETTY_PRINT);;
   }

   public function insertFileDetails($conn, $staff_id)
   {

      $sql = "INSERT INTO `staff_file_details`(`staff_id`, `file_size`, `file_limit`, `active`) VALUES
       ('$staff_id','0','200000000','Y')";
      $result = mysqli_query($conn, $sql);
   }



	/*public function trashFiles($conn, $user_id,$file_id){
    	$status = "";
    	$sql = "UPDATE `user_repository` SET `active` = 'N' WHERE `user_id` = '$user_id' AND `file_id` = '$file_id'";
    	$result = mysqli_query($conn,$sql);
     if ($result) {
         $status = json_encode(array("responseCode" => "00", "responseMessage" => "success", "staff_id" => $user_id), JSON_PRETTY_PRINT);
      } else {
         $status = json_encode(array("responseCode" => "04", "responseMessage" => "error", "staff_id" => $user_id), JSON_PRETTY_PRINT);
      }
      return $status;
    }*/



    public function fetch_trashed_file($conn, $user_id)
    {
       $json = array();
 
       $sql = "SELECT * FROM `user_repository`  WHERE  `user_id` = '$user_id' AND `active` = 'N' order by timestamp desc";
       $result = mysqli_query($conn, $sql);
       while ($rows = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
          $json[] = $rows;
       }
       return json_encode($json, JSON_PRETTY_PRINT);
    }
 


	/*public function unTrashFiles($conn, $user_id,$file_id){
    	$status = "";
    	$sql = "UPDATE `user_repository` SET `active` = 'Y' WHERE `user_id` = '$user_id' AND `file_id` = '$file_id'";
    	$result = mysqli_query($conn,$sql);
     if ($result) {
         $status = json_encode(array("responseCode" => "00", "responseMessage" => "success", "staff_id" => $user_id), JSON_PRETTY_PRINT);
      } else {
         $status = json_encode(array("responseCode" => "04", "responseMessage" => "error", "staff_id" => $user_id), JSON_PRETTY_PRINT);
      }
      return $status;
    
    }*/



   public function share_files($conn, $sender, $file_id, $receivers)
   {


   // echo ($file_id);

      $json = $this->fetch_file_details($conn, $file_id);
      $sender_json = $this->fetch_sender_details($conn, $sender);
      $receivers_json = $this->fetch_receivers_details($conn, $receivers);

      // var_dump($json);
      // var_dump($sender_json);
      $title = $json[0]['doc_title'];
      $desc = $json[0]['doc_description'];
      $url = $json[0]['doc_url'];
      $size = $json[0]['size'];
      $ext = $json[0]['extension'];
      $staff_full_name = $sender_json[0]['staff_full_name'];
      $full_name = $receivers_json[0]['staff_full_name'];
      $status = '';
      $share_id = substr(str_shuffle(str_repeat("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ", 15)), 0, 15);

      if ($receivers_json[0]['is_group'] == 'Y') {

         $receiver_array = $this->fetch_group_array_details($conn, $receivers);
         foreach ($receiver_array as $recess) {
            //  var_dump(array($recess['staff_id']));
            $receivers_sub_json = $this->fetch_receivers_details($conn, $recess['staff_id']);
            $full_sub_name = $receivers_sub_json[0]['staff_full_name'];
            $staff_sub_id = $receivers_sub_json[0]['staff_id'];
            $sql = "INSERT INTO `staff_share`(`sender_id`,`sender_name`,`file_id`,`description`, `receiver_id`,`receiver_name`, `share_id`, `file_name`, `file_size`, `file_link`, `active`,`file_type`)
      VALUES ('$sender','$staff_full_name','$file_id','$desc','$staff_sub_id','$full_sub_name','$share_id','$title','$size','$url','Y','$ext')";
            $result = mysqli_query($conn, $sql);
         }
      } else {
         $sql = "INSERT INTO `staff_share`(`sender_id`,`sender_name`,`file_id`,`description`, `receiver_id`,`receiver_name`, `share_id`, `file_name`, `file_size`, `file_link`, `active`,`file_type`)
       VALUES ('$sender','$staff_full_name','$file_id','$desc','$receivers','$full_name','$share_id','$title','$size','$url','Y','$ext')";
         $result = mysqli_query($conn, $sql);
      }
   }


   public function fetch_group_array_details($conn, $receivers)
   {
      $json = array();

      $sql = "SELECT * FROM `staff_group_base` where `group_id` = '$receivers'";
      $result = mysqli_query($conn, $sql);
      while ($rows = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
         $json[] = $rows;
      }
      return $json;
   }

   public function fetch_file_details($conn, $file)
   {
      $json = array();

      $sql = "SELECT * FROM `user_repository` where  `file_id` = '$file' order by timestamp desc";
      $result = mysqli_query($conn, $sql);
      while ($rows = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
         $json[] = $rows;
      }
      return $json;
   }


   public function fetch_sender_details($conn, $sender)
   {
      $json = array();

      $sql = "SELECT * FROM `staff_base` where `staff_id` = '$sender'";
      $result = mysqli_query($conn, $sql);
      while ($rows = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
         $json[] = $rows;
      }
      return $json;
   }


   public function fetch_receivers_details($conn, $sender)
   {
      $json = array();

      $sql = "SELECT * FROM `staff_base` where `staff_id` = '$sender' order by timestamp desc";
      $result = mysqli_query($conn, $sql);
      while ($rows = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
         $json[] = $rows;
      }
      return $json;
   }


   public function fetchStaffById($conn, $staff_id)
   {
      $json = array();

      $sql = "SELECT * FROM `staff_base` where `staff_id` = '$staff_id'";
      $result = mysqli_query($conn, $sql);
      $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
      $staffs_id = $row['staff_id'];
      $full_name = $row['staff_full_name'];
      $staff_role = $row['staff_role'];
      $staff_role_name = $row['staff_role_name'];
      $current_email =  $row['current_email'];
      $current_phone =  $row['current_phone'];
      $image_str = $row['image_str'];
      $file = $this->fetchFileById($conn, $staff_id);
      $basic = array(
         "staff" => $staffs_id, "full_name" => $full_name, "staff_role" => $staff_role, "role_name" => $staff_role_name, "file_data" => $file, "email" => $current_email, "phone" => $current_phone, "image" => $image_str
      );

      return json_encode($basic, JSON_PRETTY_PRINT);
   }


   public function fetchFileById($conn, $staff_id)
   {
      $json = array();

      $sql = "SELECT staff_id,file_size,file_limit FROM `staff_file_details` where `staff_id` = '$staff_id' order by timestamp desc";
      $result = mysqli_query($conn, $sql);
      while ($rows = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
         $json[] = $rows;
      }
      return $json;
   }


   public function fetchCurrentSizeUsed($conn, $user)
   {

      $json = "";
      $sql = "select SUM(size) as file_size FROM `user_repository` WHERE `staff_id` = '$user'";
      $result  = mysqli_query($conn, $sql);
      $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
      if ($row['file_size'] == null) {
         $json = json_encode(array("space_used" => "0", "total_space" => "53687091200"));
      } else {
         $json  = json_encode(array("space_used" => $row['file_size'], "total_space" => "53687091200"));
      }
      return  $json;
   }
}

$portal = new PortalUtility();

// echo $portal->loginUser($conn, "dayo@gmail.com", "tests");

//echo $portal->fetchUploadedByStaff($conn, '8268765337');
//echo $portal->fetch_file_details($conn,'8268765337COG7FG3T15');
//echo $portal->onboardGroup($conn, "Senior Staff Group", json_encode(array("12304556453","345541234","124455345")));
//echo $portal->share_files($conn, '8268765337', '8268765337COG7FG3T15', '3302939150');
echo $portal->fetch_trashed_file($conn, '8268765337');