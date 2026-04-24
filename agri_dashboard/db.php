<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$servername = "localhost";  // safer than 'localhost'
$username = "root";
$password = "";  // keep empty if you never set one
$database = "agri_scm_v3";  // make sure this DB exists

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>
