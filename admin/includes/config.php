<?php
// Replace these with your actual MySQL credentials
$host = 'localhost';
$user = 'root'; // or your MySQL username
$pass = '';     // password (often blank for 'root' in XAMPP)
$db   = 'agricultural_data';

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
