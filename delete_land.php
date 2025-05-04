<?php
require_once 'includes/auth.php';
require_once 'includes/config.php';

if (isset($_SESSION['isAdmin']) && $_SESSION['isAdmin'] == 1 && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $delete_sql = "DELETE FROM lands WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $delete_sql);
    mysqli_stmt_bind_param($stmt, 'ii', $id, $_SESSION['user_id']);
    if (mysqli_stmt_execute($stmt)) {
        header("Location: view_lands.php?success=Land deleted successfully");
    } else {
        header("Location: view_lands.php?error=Error deleting land");
    }
    mysqli_stmt_close($stmt);
} else {
    header("Location: view_lands.php");
}
mysqli_close($conn);
?>