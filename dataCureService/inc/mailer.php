<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
// Load Composer's autoloader
require '../vendor/autoload.php';

 class MailService{


    public function acceptMail($email,$merchant_name,$application_id,$name,$amount,$period,$template){

       
        $body = file_get_contents('templates/success.phtml');
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
            $mail->setFrom('no-reply@stack.net.ng', 'Stack For '.$merchant_name);
            $mail->addAddress($email, $name); 
            $mail->isHTML(true);                                  // Set email format to HTML  
    		$mail->Subject = 'Application Status';
    		$mail->Body    = $body;
            $mail->AddEmbeddedImage('logo-icon.png', 'logo_2u');

    		$mail->send();
            $this->mailer_logs('Mail Sent Successfully To '.$email. ' MERCHANT : '.$merchant_name. ' TIMESTAMP : '. date('Y-m-d : h:m:s'));
		} catch (Exception $e) {
   			//	 echo "Message could not be sent. Mailer Error: {$e->ErrorInfo}";
               $this->mailer_logs('Mail Sending Error '.$email. ' MERCHANT : '.$merchant_name. ' TIMESTAMP : '. date('Y-m-d : h:m:s'));
			
			}
    

    }

    public function rejectMail($email,$merchant_name,$application_id,$name,$amount,$period){

       
        $body = file_get_contents('templates/reject.phtml');
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
            $mail->setFrom('no-reply@stack.net.ng', 'Stack For '.$merchant_name);
            $mail->addAddress($email, $name); 
            $mail->isHTML(true);                                  // Set email format to HTML  
    		$mail->Subject = 'Application Status';
    		$mail->Body    = $body;
            $mail->AddEmbeddedImage('logo-icon.png', 'logo_2u');

    		$mail->send();
   			//echo 'Message has been sent';
            $this->mailer_logs('Mail Sent Successfully To '.$email. ' MERCHANT : '.$merchant_name. ' TIMESTAMP : '. date('Y-m-d : h:m:s'));
		} catch (Exception $e) {
   			//	 echo "Message could not be sent. Mailer Error: {$e->ErrorInfo}";
               $this->mailer_logs('Mail Sending Error '.$email. ' MERCHANT : '.$merchant_name. ' TIMESTAMP : '. date('Y-m-d : h:m:s'));
		
			}
    

    }


    public function newMail($email,$merchant_name,$application_id,$name,$amount,$period,$template){

       
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
            $mail->setFrom('no-reply@stack.net.ng', 'Stack For '.$merchant_name);
            $mail->addAddress($email, $name); 
            $mail->isHTML(true);                                  // Set email format to HTML  
    		$mail->Subject = 'New Application';
    		$mail->Body    = $body;
            $mail->AddEmbeddedImage('logo-icon.png', 'logo_2u');

    		$mail->send();
            $this->mailer_logs('Mail Sent Successfully To '.$email. ' MERCHANT : '.$merchant_name. ' TIMESTAMP : '. date('Y-m-d : h:m:s'));
		} catch (Exception $e) {
   			//	 echo "Message could not be sent. Mailer Error: {$e->ErrorInfo}";
               $this->mailer_logs('Mail Sending Error '.$email. ' MERCHANT : '.$merchant_name. ' TIMESTAMP : '. date('Y-m-d : h:m:s'));
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

$portal = new MailService();
// echo $portal->acceptMail('omotayotemi47@gmail.com','BOCTRUST MFB','1234567789','Isaq Mohammed','50000','5');