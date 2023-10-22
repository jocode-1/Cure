<?php

session_start();

if(session_destroy()) // Destroying All Sessions
{
	session_unset();
    header("Location: index"); // Redirecting To Home Page
}
?>
	
