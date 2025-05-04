<?php
require_once 'includes/auth.php';

// Only admin can access this page
if (!$_SESSION['isAdmin']) {
    header('Location: schemes.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: schemes.php');
    exit;
}

$scheme_id = intval($_GET['id']);

// Get scheme name for logging
$query = "SELECT scheme_name FROM beneficiary_schemes WHERE scheme_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'i', $scheme_id);
mysqli_stmt_execute($stmt);

// Retrieve the result set
$result = mysqli_stmt_get_result($stmt);

// Fetch the scheme name from the result set
$scheme = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


if ($scheme) {
    // Delete from region_schemes first (foreign key constraint)
    $delete_region_query = "DELETE FROM region_schemes WHERE scheme_id = ?";
    $stmt = mysqli_prepare($conn, $delete_region_query);
    mysqli_stmt_bind_param($stmt, 'i', $scheme_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Then delete from beneficiary_schemes
    $delete_query = "DELETE FROM beneficiary_schemes WHERE scheme_id = ?";
    $stmt = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($stmt, 'i', $scheme_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Log the activity
    $log_query = "INSERT INTO activity_log (user_id, action, created_at) 
                  VALUES (?, 'Deleted scheme: {$scheme['scheme_name']}', CURRENT_TIMESTAMP)";
    $log_stmt = mysqli_prepare($conn, $log_query);
    mysqli_stmt_bind_param($log_stmt, 'i', $_SESSION['user_id']);
    mysqli_stmt_execute($log_stmt);
    mysqli_stmt_close($log_stmt);
}

header('Location: schemes.php');
exit;
