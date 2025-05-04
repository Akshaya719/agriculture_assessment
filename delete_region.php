<?php
session_start();
require_once 'includes/auth.php';
require_once 'includes/config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['isAdmin']) || $_SESSION['isAdmin'] != 1) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: regions.php?error=" . urlencode("No region specified"));
    exit();
}

$region_id = (int)$_GET['id'];

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Delete related land_holdings and region_irrigation
    $delete_holdings_query = "DELETE FROM land_holdings WHERE region_id = ?";
    $stmt = mysqli_prepare($conn, $delete_holdings_query);
    mysqli_stmt_bind_param($stmt, "i", $region_id);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error deleting land holdings: " . mysqli_error($conn));
    }
    mysqli_stmt_close($stmt);

    $delete_irrigation_query = "DELETE FROM region_irrigation WHERE region_id = ?";
    $stmt = mysqli_prepare($conn, $delete_irrigation_query);
    mysqli_stmt_bind_param($stmt, "i", $region_id);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error deleting irrigation sources: " . mysqli_error($conn));
    }
    mysqli_stmt_close($stmt);

    // Delete the region entry
    $delete_region_query = "DELETE FROM regions WHERE region_id = ?";
    $stmt = mysqli_prepare($conn, $delete_region_query);
    mysqli_stmt_bind_param($stmt, "i", $region_id);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error deleting region: " . mysqli_error($conn));
    }
    mysqli_stmt_close($stmt);

    // Commit transaction
    mysqli_commit($conn);
    header("Location: regions.php?success=Region entry deleted successfully");
    exit();

} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    $error = $e->getMessage();
    header("Location: regions.php?error=" . urlencode($error));
    exit();
}

mysqli_close($conn);
?>