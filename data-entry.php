<?php
require_once 'includes/auth.php';
require_once 'includes/config.php'; // Ensure $conn is defined here

// Initialize variables for form feedback
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize form data
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $region = filter_input(INPUT_POST, 'region', FILTER_DEFAULT);
    $land_size = filter_input(INPUT_POST, 'land_size', FILTER_VALIDATE_FLOAT);
    $irrigation_source = filter_input(INPUT_POST, 'irrigation_source', FILTER_DEFAULT);
    $primary_crop = filter_input(INPUT_POST, 'primary_crop', FILTER_DEFAULT);
    $secondary_crop = filter_input(INPUT_POST, 'secondary_crop', FILTER_DEFAULT) ?: null;
    $water_depth = filter_input(INPUT_POST, 'water_depth', FILTER_VALIDATE_FLOAT) ?: null;

    // Validate required fields
    if (!$user_id || !$region || !$land_size || !$irrigation_source || !$primary_crop) {
        $error_message = 'All required fields must be filled.';
    } elseif ($land_size <= 0) {
        $error_message = 'Land size must be greater than 0.';
    } else {
        // Validate foreign key constraints using prepared statements
        $user_check_query = "SELECT id FROM users WHERE id = ?";
        $user_stmt = mysqli_prepare($conn, $user_check_query);
        mysqli_stmt_bind_param($user_stmt, 'i', $user_id);
        mysqli_stmt_execute($user_stmt);
        $user_result = mysqli_stmt_get_result($user_stmt);
        $user_exists = mysqli_num_rows($user_result);
        mysqli_stmt_close($user_stmt);

        $region_check_query = "SELECT region_name FROM regions WHERE region_name = ?";
        $region_stmt = mysqli_prepare($conn, $region_check_query);
        mysqli_stmt_bind_param($region_stmt, 's', $region);
        mysqli_stmt_execute($region_stmt);
        $region_result = mysqli_stmt_get_result($region_stmt);
        $region_exists = mysqli_num_rows($region_result);
        mysqli_stmt_close($region_stmt);

        $source_check_query = "SELECT source_name FROM irrigation_sources WHERE source_name = ?";
        $source_stmt = mysqli_prepare($conn, $source_check_query);
        mysqli_stmt_bind_param($source_stmt, 's', $irrigation_source);
        mysqli_stmt_execute($source_stmt);
        $source_result = mysqli_stmt_get_result($source_stmt);
        $source_exists = mysqli_num_rows($source_result);
        mysqli_stmt_close($source_stmt);

        if ($user_exists === 0) {
            $error_message = 'Invalid user ID selected.';
        } elseif ($region_exists === 0) {
            $error_message = 'Invalid region selected.';
        } elseif ($source_exists === 0) {
            $error_message = 'Invalid irrigation source selected.';
        } else {
            // Insert data into lands table (removed secondary_crop and water_depth)
            $query = "INSERT INTO lands (user_id, region, land_size, irrigation_source, primary_crop, created_at) 
                      VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
            $stmt = mysqli_prepare($conn, $query);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'isdss', $user_id, $region, $land_size, $irrigation_source, $primary_crop);
                if (mysqli_stmt_execute($stmt)) {
                    $land_id = mysqli_insert_id($conn);

                    // Insert water_depth into user_wells if provided
                    if ($water_depth !== null) {
                        $well_query = "INSERT INTO user_wells (land_id, water_depth, created_at) VALUES (?, ?, CURRENT_TIMESTAMP)";
                        $well_stmt = mysqli_prepare($conn, $well_query);
                        mysqli_stmt_bind_param($well_stmt, 'id', $land_id, $water_depth);
                        mysqli_stmt_execute($well_stmt);
                        mysqli_stmt_close($well_stmt);
                    }

                    // Log data entry action
                    $log_query = "INSERT INTO activity_log (user_id, action, region, created_at) VALUES (?, 'Added land data', ?, CURRENT_TIMESTAMP)";
                    $log_stmt = mysqli_prepare($conn, $log_query);
                    mysqli_stmt_bind_param($log_stmt, 'is', $_SESSION['user_id'], $region);
                    mysqli_stmt_execute($log_stmt);
                    mysqli_stmt_close($log_stmt);

                    $success_message = 'Land data added successfully!';
                } else {
                    $error_message = 'Error adding land data: ' . mysqli_error($conn);
                    if (strpos(mysqli_error($conn), 'Duplicate entry') !== false) {
                        $error_message .= ' (Possible issue: Duplicate primary key. Ensure the id column is AUTO_INCREMENT.)';
                    }
                }
                mysqli_stmt_close($stmt);
            } else {
                $error_message = 'Error preparing statement: ' . mysqli_error($conn);
            }
        }
        mysqli_free_result($user_result);
        mysqli_free_result($region_result);
        mysqli_free_result($source_result);
    }
}

// Fetch users for dropdown (include isAdmin status)
$users_query = "SELECT id, full_name, isAdmin FROM users ORDER BY full_name";
$users_result = mysqli_query($conn, $users_query) or die("Users query failed: " . mysqli_error($conn));

// Fetch regions for dropdown
$regions_query = "SELECT region_name FROM regions ORDER BY region_name";
$regions_result = mysqli_query($conn, $regions_query) or die("Regions query failed: " . mysqli_error($conn));

// Fetch irrigation sources for dropdown
$irrigation_query = "SELECT source_name FROM irrigation_sources ORDER BY source_name";
$irrigation_result = mysqli_query($conn, $irrigation_query) or die("Irrigation sources query failed: " . mysqli_error($conn));
?>

<?php include 'header.php'; ?>

<body style="background-image: url(images/schemes.jpg); background-size: cover;background-repeat: no-repeat;">

    <main class="flex-1 p-8">
        <h1 class="text-2xl font-bold text-green-800 mb-6">Agricultural Data Entry</h1>

        <!-- Feedback Messages -->
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

        <!-- Form -->
        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-xl font-semibold mb-4">Add Land Data</h2>
            <form method="POST" class="space-y-4">
                <!-- User ID -->
                <div>
                    <label for="user_id" class="block text-gray-700">User ID <span class="text-red-500">*</span></label>
                    <select id="user_id" name="user_id" class="w-full p-2 border rounded" required>
                        <option value="">Select User</option>
                        <?php while ($user = mysqli_fetch_assoc($users_result)): ?>
                            <option value="<?php echo htmlspecialchars($user['id']); ?>">
                                <?php echo htmlspecialchars($user['full_name']) . ($user['isAdmin'] ? ' (Admin)' : ''); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Region -->
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

                <!-- Land Size -->
                <div>
                    <label for="land_size" class="block text-gray-700">Land Size (hectares) <span class="text-red-500">*</span></label>
                    <input type="number" id="land_size" name="land_size" step="0.01" min="0.01" class="w-full p-2 border rounded" required>
                </div>

                <!-- Irrigation Source -->
                <div>
                    <label for="irrigation_source" class="block text-gray-700">Irrigation Source <span class="text-red-500">*</span></label>
                    <select id="irrigation_source" name="irrigation_source" class="w-full p-2 border rounded" required>
                        <option value="">Select Irrigation Source</option>
                        <?php while ($source = mysqli_fetch_assoc($irrigation_result)): ?>
                            <option value="<?php echo htmlspecialchars($source['source_name']); ?>">
                                <?php echo htmlspecialchars($source['source_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Primary Crop -->
                <div>
                    <label for="primary_crop" class="block text-gray-700">Primary Crop <span class="text-red-500">*</span></label>
                    <input type="text" id="primary_crop" name="primary_crop" class="w-full p-2 border rounded" required>
                </div>

                <!-- Secondary Crop -->
                <!-- <div>
                <label for="secondary_crop" class="block text-gray-700">Secondary Crop</label>
                <input type="text" id="secondary_crop" name="secondary_crop" class="w-full p-2 border rounded">
            </div> -->

                <!-- Water Depth -->
                <div>
                    <label for="water_depth" class="block text-gray-700">Water Depth (meters)</label>
                    <input type="number" id="water_depth" name="water_depth" step="0.01" min="0" class="w-full p-2 border rounded">
                </div>

                <!-- Buttons -->
                <div class="flex space-x-4">
                    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                        Submit
                    </button>
                    <button type="reset" class="bg-gray-300 text-gray-800 px-4 py-2 rounded hover:bg-gray-400">
                        Reset
                    </button>
                </div>
            </form>
        </div>
    </main>
</body>

</html>

<?php
// Free result sets
mysqli_free_result($users_result);
mysqli_free_result($regions_result);
mysqli_free_result($irrigation_result);
?>