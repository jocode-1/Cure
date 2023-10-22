<?php

class Logs{


    public function server_logs($log_msg){
    
        $log_filename = "server_logs";
		if (!file_exists($log_filename)) {
			// create directory/folder uploads.
			mkdir($log_filename, 0777, true);
		}
		$log_file_data = $log_filename . '/log_' . date('d-M-Y') . '.log';
		// if you don't add `FILE_APPEND`, the file will be erased each time you add a log
		file_put_contents($log_file_data, $log_msg . "\n", FILE_APPEND);

    }


    public function service_logs($log_msg){
    
        $log_filename = "service_logs";
		if (!file_exists($log_filename)) {
			// create directory/folder uploads.
			mkdir($log_filename, 0777, true);
		}
		$log_file_data = $log_filename . '/log_' . date('d-M-Y') . '.log';
		// if you don't add `FILE_APPEND`, the file will be erased each time you add a log
		file_put_contents($log_file_data, $log_msg . "\n", FILE_APPEND);

    }



}
 //$log = new Logs();
 //$log->server_logs('Hello server');
 //$log->service_logs('Hello service');
?>