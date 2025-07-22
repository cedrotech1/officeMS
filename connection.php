<?php
// connection.php

// Database credentials (replace with your actual values)
$dbHost = 'localhost';
$dbUser = 'root';
$dbPassword = '';
$dbName = 'office';
$dbPort = 3306; // Default MySQL port

$connection = mysqli_connect($dbHost, $dbUser, $dbPassword, $dbName, $dbPort);

if (!$connection) {
    die('Database connection failed: ' . mysqli_connect_error());
}
?>