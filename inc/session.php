<?php
session_start();
if (!isset($_SESSION['login_user'])) {
    header('Location: /'); 
}

$staffDetails = $_SESSION['login_user'];


