<?php
require_once 'includes/auth.php';
require_once 'includes/config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if land ID is provided
if (!isset($_GET['id'])) {
    header("Location: view_lands.php");
    exit();
}

$land_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Fetch land data
$land_query = "SELECT l.*, r.region_id, r.region_name, r.state, r.district 
               FROM lands l
               LEFT JOIN regions r ON l.region = r.region_name
               WHERE l.id = ? AND l.user_id = ?";
$stmt = mysqli_prepare($conn, $land_query);
mysqli_stmt_bind_param($stmt, 'ii', $land_id, $user_id);
mysqli_stmt_execute($stmt);
$land_result = mysqli_stmt_get_result($stmt);
$land = mysqli_fetch_assoc($land_result);
mysqli_stmt_close($stmt);

if (!$land) {
    header("Location: view_lands.php?error=Land not found");
    exit();
}

// Fetch regions for dropdown
$regions_query = "SELECT region_id, region_name FROM regions";
$regions_result = mysqli_query($conn, $regions_query);

// Fetch irrigation sources
$irrigation_query = "SELECT source_name FROM irrigation_sources";
$irrigation_result = mysqli_query($conn, $irrigation_query);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $region_id = intval($_POST['region_id']);
    $land_size = floatval($_POST['land_size']);
    $irrigation_source = mysqli_real_escape_string($conn, $_POST['irrigation_source']);
    $primary_crop = mysqli_real_escape_string($conn, $_POST['primary_crop']);

    // Get region name
    $region_name = '';
    mysqli_data_seek($regions_result, 0);
    while ($row = mysqli_fetch_assoc($regions_result)) {
        if ($row['region_id'] == $region_id) {
            $region_name = $row['region_name'];
            break;
        }
    }

    if ($land_size <= 0) {
        $error = "Land size must be greater than 0";
    } elseif (empty($region_name)) {
        $error = "Invalid region selected";
    } else {
        $update_query = "UPDATE lands SET 
                        region = ?,
                        land_size = ?,
                        irrigation_source = ?,
                        primary_crop = ?
                        WHERE id = ? AND user_id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, 'sdssii', $region_name, $land_size, $irrigation_source, $primary_crop, $land_id, $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Log activity
            $log_query = "INSERT INTO activity_log (user_id, action, region) 
                          VALUES (?, 'Updated land record', ?)";
            $log_stmt = mysqli_prepare($conn, $log_query);
            mysqli_stmt_bind_param($log_stmt, 'is', $user_id, $region_name);
            mysqli_stmt_execute($log_stmt);
            mysqli_stmt_close($log_stmt);
            
            header("Location: view_lands.php?success=Land updated successfully");
            exit();
        } else {
            $error = "Error updating land: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
}

include 'header.php';
?>

<main class="flex-1 p-8">
    <div class="max-w-3xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-green-800">Edit Land Record</h1>
            <a href="view_lands.php" class="text-gray-600 hover:text-gray-800">
                <i class="fas fa-arrow-left mr-1"></i> Back to My Lands
            </a>
        </div>

        <?php if (isset($error)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                <p><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow-md p-6">
            <form method="POST">
                <div class="space-y-4">
                    <!-- Region Selection -->
                    <div>
                        <label for="region_id" class="block text-gray-700 mb-1">Region</label>
                        <select id="region_id" name="region_id" class="w-full p-2 border rounded" required>
                            <option value="">Select Region</option>
                            <?php while ($region = mysqli_fetch_assoc($regions_result)): ?>
                                <option value="<?php echo $region['region_id']; ?>"
                                    <?php echo ($region['region_id'] == ($land['region_id'] ?? 0)) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($region['region_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Land Size -->
                    <div>
                        <label for="land_size" class="block text-gray-700 mb-1">Land Size (hectares)</label>
                        <input type="number" step="0.01" id="land_size" name="land_size" 
                               class="w-full p-2 border rounded" required
                               value="<?php echo htmlspecialchars($land['land_size']); ?>">
                    </div>

                    <!-- Irrigation Source -->
                    <div>
                        <label for="irrigation_source" class="block text-gray-700 mb-1">Irrigation Source</label>
                        <select id="irrigation_source" name="irrigation_source" class="w-full p-2 border rounded" required>
                            <option value="">Select Source</option>
                            <?php while ($source = mysqli_fetch_assoc($irrigation_result)): ?>
                                <option value="<?php echo htmlspecialchars($source['source_name']); ?>"
                                    <?php echo ($source['source_name'] == $land['irrigation_source']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($source['source_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Primary Crop -->
                    <div>
                        <label for="primary_crop" class="block text-gray-700 mb-1">Primary Crop</label>
                        <input type="text" id="primary_crop" name="primary_crop" 
                               class="w-full p-2 border rounded" required
                               value="<?php echo htmlspecialchars($land['primary_crop']); ?>">
                    </div>

                    <div class="flex justify-end space-x-4 pt-4">
                        <a href="view_lands.php" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            Cancel
                        </a>
                        <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700">
                            Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</main>

<?php
include 'footer.php';
mysqli_free_result($land_result);
mysqli_free_result($regions_result);
mysqli_free_result($irrigation_result);
mysqli_close($conn);
?>