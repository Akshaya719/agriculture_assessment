<?php
require_once 'includes/config.php';

$users = [
    ['username' => 'admin1', 'password' => 'admin123'],
    ['username' => 'farmer1', 'password' => 'farmer123'],
    ['username' => 'farmer2', 'password' => 'farmer456'],
    ['username' => 'farmer3', 'password' => 'farmer789']
];

foreach ($users as $user) {
    $hashed_password = password_hash($user['password'], PASSWORD_DEFAULT);
    $query = "UPDATE users SET password = ? WHERE username = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'ss', $hashed_password, $user['username']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

echo "Passwords updated successfully!";
?>