<?php
session_start();
require_once 'includes/config.php';

// Log logout action
if (isset($_SESSION['user_id'])) {
    $log_query = "INSERT INTO activity_log (user_id, action, region, created_at) VALUES (?, 'Logged out', ?, CURRENT_TIMESTAMP)";
    $log_stmt = mysqli_prepare($conn, $log_query);
    $region = $_SESSION['region'] ?? null;
    mysqli_stmt_bind_param($log_stmt, 'is', $_SESSION['user_id'], $region);
    mysqli_stmt_execute($log_stmt);
    mysqli_stmt_close($log_stmt);
}
session_unset();
session_destroy();
header('Location: index.php');
exit;
?>