<?php
session_start();
require_once 'includes/config.php';
require_once 'formValidation.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Initialize variables
$error = '';
$success = '';
$user_id = $_SESSION['user_id'];

// Fetch current user data
$query = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt); // Get the result from the prepared statement
$user = mysqli_fetch_assoc($result); // Fetch the data from the result
mysqli_stmt_close($stmt);

if (!$user) {
    die("User not found");
}

// Handle form submission


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize_input($_POST['full_name']);
    $email = sanitize_input($_POST['email']);
    $username = sanitize_input($_POST['username']);
    $region = sanitize_input($_POST['region']);
    $newpassword = sanitize_input($_POST['new_password']);
    $current_password = sanitize_input($_POST['current_password']);
    $confirm_password = sanitize_input($_POST['confirm_password']);

    // Basic validation
    if (empty($full_name) || empty($email) || empty($username)) {
        $error = 'Please fill in all required fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        // Check if username or email already exists
        $check_query = "SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, 'ssi', $username, $email, $user_id);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);

        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $error = 'Username or email already exists';
        } else {
            // Handle password update if new password provided
            $password_to_store = $user['password']; // Default to current password
            
            if (!empty($newpassword)) {
                if (!password_verify($current_password, $user['password'])) {
                    $error = 'Current password is incorrect';
                } elseif ($newpassword !== $confirm_password) {
                    $error = 'New passwords do not match';
                } elseif (strlen($newpassword) < 8) {
                    $error = 'Password must be at least 8 characters long';
                } else {
                    $password_to_store = password_hash($newpassword, PASSWORD_DEFAULT);
                }
            }
            
            if (empty($error)) {
                // Update user data
                $update_query = "UPDATE users SET full_name = ?, email = ?, username = ?, password = ?, region = ? WHERE id = ?";
                $update_stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param($update_stmt, 'sssssi', $full_name, $email, $username, $password_to_store, $region, $user_id);

                if (mysqli_stmt_execute($update_stmt)) {
                    // Update session variables
                    $_SESSION['full_name'] = $full_name;
                    $_SESSION['email'] = $email;
                    $_SESSION['username'] = $username;
                    $_SESSION['region'] = $region;

                    $success = 'Profile updated successfully!';

                    // Log the activity
                    $log_query = "INSERT INTO activity_log (user_id, action, region, created_at) VALUES (?, 'Updated profile', ?, CURRENT_TIMESTAMP)";
                    $log_stmt = mysqli_prepare($conn, $log_query);
                    mysqli_stmt_bind_param($log_stmt, 'is', $user_id, $region);
                    mysqli_stmt_execute($log_stmt);
                    mysqli_stmt_close($log_stmt);
                } else {
                    $error = 'Error updating profile: ' . mysqli_error($conn);
                }
                mysqli_stmt_close($update_stmt);
            }
        }
        mysqli_stmt_close($check_stmt);
    }
}

// Get regions for dropdown
$regions = [];
$regions_query = "SELECT region_name FROM regions ORDER BY region_name";
$regions_result = mysqli_query($conn, $regions_query);
while ($row = mysqli_fetch_assoc($regions_result)) {
    $regions[] = $row['region_name'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | AgriData</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://kit.fontawesome.com/your-kit-id.js" crossorigin="anonymous"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#c2185b',
                        secondary: '#2e7d32',
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-gray-100">
    <?php include 'header.php'; ?>

    <main class="container mx-auto py-8 px-4">
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <!-- Profile Header -->
                <div class="bg-green-800 text-white px-6 py-4">
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center justify-center w-16 h-16 rounded-full bg-[#c2185b] text-white text-2xl font-bold">
                            <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold"><?php echo htmlspecialchars($user['full_name']); ?></h1>
                            <p class="text-green-200"><?php echo htmlspecialchars($user['email']); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Form Section -->
                <div class="p-6">
                    <?php if ($error): ?>
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                            <p><?php echo htmlspecialchars($error); ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                            <p><?php echo htmlspecialchars($success); ?></p>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="profile.php" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Personal Info -->
                            <div class="space-y-4">
                                <h2 class="text-xl font-semibold text-gray-800 border-b pb-2">Personal Information</h2>

                                <div>
                                    <label for="full_name" class="block text-sm font-medium text-gray-700">Full Name</label>
                                    <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 p-2 border">
                                </div>

                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 p-2 border">
                                </div>
                            </div>

                            <!-- Account Info -->
                            <div class="space-y-4">
                                <h2 class="text-xl font-semibold text-gray-800 border-b pb-2">Account Settings</h2>

                                <div>
                                    <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 p-2 border">
                                </div>

                                <div>
                                    <label for="region" class="block text-sm font-medium text-gray-700">Region</label>
                                    <select id="region" name="region" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 p-2 border">
                                        <option value="">Select Region</option>
                                        <?php foreach ($regions as $region): ?>
                                            <option value="<?php echo htmlspecialchars($region); ?>" <?php echo ($region === $user['region']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($region); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Password Section -->
                        <div class="pt-4 border-t">
                            <h2 class="text-xl font-semibold text-gray-800 mb-4">Change Password</h2>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
                                    <input type="password" id="current_password" name="current_password"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 p-2 border">
                                </div>
                                <div>
                                    <label for="new_password" class="block text-sm font-medium text-gray-700">New Password</label>
                                    <input type="password" id="new_password" name="new_password"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 p-2 border">
                                </div>
                                <div>
                                    <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                                    <input type="password" id="confirm_password" name="confirm_password"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 p-2 border">
                                </div>
                            </div>
                            <p class="mt-2 text-sm text-gray-500">Leave blank to keep current password</p>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex justify-end space-x-4 pt-6">
                            <a href="dashboard.php" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                Cancel
                            </a>
                            <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Simple password match validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (newPassword || confirmPassword) {
                if (newPassword !== confirmPassword) {
                    alert('New passwords do not match!');
                    e.preventDefault();
                }

                if (newPassword.length > 0 && newPassword.length < 8) {
                    alert('Password must be at least 8 characters long');
                    e.preventDefault();
                }
            }
        });
    </script>
</body>

</html> 