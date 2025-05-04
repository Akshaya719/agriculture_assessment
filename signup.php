<?php
require_once 'includes/config.php';

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize form data
    $username = filter_input(INPUT_POST, 'username', FILTER_DEFAULT);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $full_name = filter_input(INPUT_POST, 'full_name', FILTER_DEFAULT);
    $password = filter_input(INPUT_POST, 'password', FILTER_DEFAULT);
    $region = filter_input(INPUT_POST, 'region', FILTER_DEFAULT);

    // Validate inputs
    if (!$username || !$email || !$full_name || !$password || !$region) {
        $error_message = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Invalid email format.';
    } elseif (strlen($password) < 6) {
        $error_message = 'Password must be at least 6 characters.';
    } else {
        // Check for existing username or email
        $check_query = "SELECT id FROM users WHERE username = ? OR email = ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, 'ss', $username, $email);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        if (mysqli_num_rows($check_result) > 0) {
            $error_message = 'Username or email already exists.';
        } else {
            // Insert new user (removed 'role' column)
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $query = "INSERT INTO users (username, password, email, full_name, isAdmin, region, created_at) 
                      VALUES (?, ?, ?, ?, 0, ?, CURRENT_TIMESTAMP)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, 'sssss', $username, $hashed_password, $email, $full_name, $region);
            if (mysqli_stmt_execute($stmt)) {
                $user_id = mysqli_insert_id($conn);
                $success_message = 'Registration successful! Please log in.';

                // Log signup action
                $log_query = "INSERT INTO activity_log (user_id, action, region, created_at) VALUES (?, 'Signed up', ?, CURRENT_TIMESTAMP)";
                $log_stmt = mysqli_prepare($conn, $log_query);
                mysqli_stmt_bind_param($log_stmt, 'is', $user_id, $region);
                mysqli_stmt_execute($log_stmt);
                mysqli_stmt_close($log_stmt);
            } else {
                $error_message = 'Error registering user: ' . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
        mysqli_stmt_close($check_stmt);
    }
}

// Fetch regions for dropdown
$regions_query = "SELECT region_name FROM regions ORDER BY region_name";
$regions_result = mysqli_query($conn, $regions_query) or die("Regions query failed: " . mysqli_error($conn));
?>

<?php include 'header.php'; ?>

<body style="background-image: url(images/3.jpg); background-size: cover;background-repeat: no-repeat;">
    <main class="flex-1 p-8">
        <div class="max-w-md mx-auto bg-white p-6 rounded-lg shadow">
            <h2 class="text-2xl font-bold text-green-800 mb-6">Sign Up</h2>

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
                    <label for="username" class="block text-gray-700">Username <span class="text-red-500">*</span></label>
                    <input type="text" id="username" name="username" class="w-full p-2 border rounded" required>
                </div>
                <div>
                    <label for="email" class="block text-gray-700">Email <span class="text-red-500">*</span></label>
                    <input type="email" id="email" name="email" class="w-full p-2 border rounded" required>
                </div>
                <div>
                    <label for="full_name" class="block text-gray-700">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" id="full_name" name="full_name" class="w-full p-2 border rounded" required>
                </div>
                <div>
                    <label for="password" class="block text-gray-700">Password <span class="text-red-500">*</span></label>
                    <input type="password" id="password" name="password" class="w-full p-2 border rounded" required>
                </div>
                <div>
                    <label for="region" class="block text-gray-700">Region <span class="text-red-500">*</span></label>
                    <select id="region" name="region" class="w-full p-2 border rounded" required>
                        <option value="">Select Region</option>
                        <?php while ($region = mysqli_fetch_assoc($regions_result)): ?>
                            <option value="<?php echo htmlspecialchars($region['region_name']); ?>">
                                <?php echo htmlspecialchars($region['region_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                    Sign Up
                </button>
            </form>
            <p class="mt-4 text-gray-600">
                Already have an account? <a href="index.php" class="text-green-600 hover:underline">Login</a>
            </p>
        </div>
    </main>
</body>

</html>

<?php
mysqli_free_result($regions_result);
?>