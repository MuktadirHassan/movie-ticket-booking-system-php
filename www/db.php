<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'cinema';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Test connection
// if ($conn->query("SELECT 1")) {
//     echo "Database connection successful!";
// } else {
//     echo "Database connection failed!";
// }
