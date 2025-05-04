<?php
session_start(); // Ensure session is started

require_once 'config.php'; // Assuming config.php sets up $conn

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Store the requested page to redirect back after login
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php");
    exit();
}

// Fetch user details for isAdmin check
$user_id = $_SESSION['user_id'];
$query = "SELECT isAdmin FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($user = mysqli_fetch_assoc($result)) {
    $_SESSION['isAdmin'] = $user['isAdmin'];
} else {
    // If user not found, destroy session and redirect to login
    session_destroy();
    header("Location: login.php");
    exit();
}

mysqli_stmt_close($stmt);
?>