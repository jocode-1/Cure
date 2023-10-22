<?php

 class database{

    private $host = "localhost";
    private $db_name = "cure";
    private $username = "root";
    private $password = "";
    public $conn;

     public function getConnection(){

        $this->conn = null;
  
        try{
            $this->conn = new mysqli($this->host,$this->username, $this->password, $this->db_name);
    
	//	echo 'Success';
        }catch(Exception $exception){
            echo "Connection error: " . $exception->getMessage();
        }
  
        return $this->conn;
  
      
    }
	
	
}

//$connect = new database();
//$connect->getConnection();



?>
