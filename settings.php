<?php
require_once 'includes/auth.php';

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = filter_input(INPUT_POST, 'full_name', FILTER_DEFAULT);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = filter_input(INPUT_POST, 'password', FILTER_DEFAULT);

    if (!$full_name || !$email) {
        $error_message = 'Full name and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Invalid email format.';
    } else {
        // Check for email conflict
        $check_query = "SELECT id FROM users WHERE email = ? AND id != ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, 'si', $email, $_SESSION['user_id']);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        if (mysqli_num_rows($check_result) > 0) {
            $error_message = 'Email already in use.';
        } else {
            // Update user
            $query = "UPDATE users SET full_name = ?, email = ?" . ($password ? ", password = ?" : "") . " WHERE id = ?";
            $stmt = mysqli_prepare($conn, $query);
            $hashed_password = $password ? password_hash($password, PASSWORD_DEFAULT) : null;
            if ($password) {
                mysqli_stmt_bind_param($stmt, 'sssi', $full_name, $email, $hashed_password, $_SESSION['user_id']);
            } else {
                mysqli_stmt_bind_param($stmt, 'ssi', $full_name, $email, $_SESSION['user_id']);
            }
            if (mysqli_stmt_execute($stmt)) {
                $success_message = 'Profile updated successfully!';
                $_SESSION['full_name'] = $full_name;

                // Log profile update
                $log_query = "INSERT INTO activity_log (user_id, action, region, created_at) VALUES (?, 'Updated profile', ?, CURRENT_TIMESTAMP)";
                $log_stmt = mysqli_prepare($conn, $log_query);
                mysqli_stmt_bind_param($log_stmt, 'is', $_SESSION['user_id'], $_SESSION['region']);
                mysqli_stmt_execute($log_stmt);
                mysqli_stmt_close($log_stmt);
            } else {
                $error_message = 'Error updating profile: ' . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
        mysqli_stmt_close($check_stmt);
    }
}

// Fetch current user data
$user_query = "SELECT full_name, email FROM users WHERE id = ?";
$user_stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($user_stmt, 'i', $_SESSION['user_id']);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);
mysqli_stmt_close($user_stmt);
?>

<?php include 'header.php'; ?>

<main class="flex-1 p-8">
    <div class="max-w-md mx-auto bg-white p-6 rounded-lg shadow">
        <h2 class="text-2xl font-bold text-green-800 mb-6">Settings</h2>

        <?php if ($success_message): ?>
            <div class="bg-green-100 text-green-800 p-4 rounded-lg mb-6">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="bg-red-100 text-red-800 p-4 rounded-lg mb-6">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label for="full_name" class="block text-gray-700">Full Name <span class="text-red-500">*</span></label>
                <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" class="w-full p-2 border rounded" required>
            </div>
            <div>
                <label for="email" class="block text-gray-700">Email <span class="text-red-500">*</span></label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="w-full p-2 border rounded" required>
            </div>
            <div>
                <label for="password" class="block text-gray-700">New Password (leave blank to keep current)</label>
                <input type="password" id="password" name="password" class="w-full p-2 border rounded">
            </div>
            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                Update Profile
            </button>
        </form>
    </div>
</main>
</body>

</html>