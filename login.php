<?php
session_start();
require_once '../agriculture_assessment/includes/config.php';




$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = filter_input(INPUT_POST, 'username', FILTER_DEFAULT);
    $password = filter_input(INPUT_POST, 'password', FILTER_DEFAULT);

    // Log submitted data for debugging
    error_log("Login attempt: username='$username'");

    if (!$username || !$password) {
        $error_message = 'Please fill in both username and password.';
    } else {
        $query = "SELECT id, full_name, password, isAdmin, region FROM users WHERE username = ?";
        $stmt = mysqli_prepare($conn, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            if (!$user) {
                $error_message = 'Username not found. Please check your username or sign up.';
                error_log("Username '$username' not found in users table");
            } elseif (!password_verify($password, $user['password'])) {
                $error_message = 'Incorrect password. Please try again.';
                error_log("Password verification failed for username='$username'");
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['isAdmin'] = $user['isAdmin'];
                $_SESSION['region'] = $user['region'];
                $_SESSION['email'] = $user['email'];

                // Log login action
                $log_query = "INSERT INTO activity_log (user_id, action, region, created_at) VALUES (?, 'Logged in', ?, CURRENT_TIMESTAMP)";
                $log_stmt = mysqli_prepare($conn, $log_query);
                mysqli_stmt_bind_param($log_stmt, 'is', $user['id'], $user['region']);
                mysqli_stmt_execute($log_stmt);
                mysqli_stmt_close($log_stmt);

                header('Location: dash.php');
                // header('Location: /agriculture_assessment/dashboard.php');
                exit;
            }
        } else {
            $error_message = 'Database error: ' . mysqli_error($conn);
            error_log("Query preparation failed: " . mysqli_error($conn));
        }
    }
}
?>

<?php include 'header.php'; ?>

<body style="background-image: url(images/3.jpg); background-size: cover;background-repeat: no-repeat;">

    <main class="flex-1 p-8 mt-24">
        <div class="max-w-md mx-auto bg-white p-6 rounded-lg shadow">
            <h2 class="text-2xl font-bold text-green-800 mb-6">Login</h2>

            <?php if ($error_message): ?>
                <div class="bg-red-100 text-red-800 p-4 rounded-lg mb-6">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div>
                    <label for="username" class="block text-gray-700">Username</label>
                    <input type="text" id="username" name="username" class="w-full p-2 border rounded" required>
                </div>
                <div>
                    <div class="flex justify-between mb-2">
                    <label for="password" class="block text-gray-700">Password</label>
                    <a href="forgetPassword.php" class="text-[green] font-semibold">Forget Password      </a>

                    </div>
                    <input type="password" id="password" name="password" class="w-full p-2 border rounded" required>
                </div>
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                    Login
                </button>
            </form>
            <p class="mt-4 text-gray-600">
                Don't have an account? <a href="signup.php" class="text-green-600 hover:underline">Sign Up</a>
            </p>
        </div>
    </main>
</body>

</html>