<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Initialize variables
$error = '';
$success = '';
$regions = [];
$crops = [];
$irrigation_sources = [];

// Fetch regions for dropdown
$region_query = "SELECT region_id, region_name FROM regions";
$region_result = mysqli_query($conn, $region_query);
while ($row = mysqli_fetch_assoc($region_result)) {
    $regions[] = $row;
}

// Fetch crops for dropdown
$crop_query = "SELECT crop_id, crop_name FROM crops";
$crop_result = mysqli_query($conn, $crop_query);
while ($row = mysqli_fetch_assoc($crop_result)) {
    $crops[] = $row;
}

// Fetch irrigation sources
$irrigation_query = "SELECT source_id, source_name FROM irrigation_sources";
$irrigation_result = mysqli_query($conn, $irrigation_query);
while ($row = mysqli_fetch_assoc($irrigation_result)) {
    $irrigation_sources[] = $row;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize inputs
    $region_id = intval($_POST['region_id']);
    $land_size = floatval($_POST['land_size']);
    $irrigation_source = mysqli_real_escape_string($conn, $_POST['irrigation_source']);
    $user_id = $_SESSION['user_id'];

    $primary_crop_id = isset($_POST['primary_crop']) ? intval($_POST['primary_crop']) : 0;
    $primary_crop_name = '';

    foreach ($crops as $crop) {
        if ($crop['crop_id'] == $primary_crop_id) {
            $primary_crop_name = $crop['crop_name'];
            break;
        }
    }

    // Get region name
    $region_name = '';
    foreach ($regions as $region) {
        if ($region['region_id'] == $region_id) {
            $region_name = $region['region_name'];
            break;
        }
    }

    // Basic validation
    if ($land_size <= 0) {
        $error = "Land size must be greater than 0";
    } elseif ($region_id <= 0 || empty($region_name)) {
        $error = "Please select a valid region";
    } elseif (empty($primary_crop_name)) {
        $error = "Please select a primary crop";
    } else {
        // Insert land data
        $insert_sql = "INSERT INTO lands (user_id, region, land_size, irrigation_source, primary_crop) 
                       VALUES (?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($stmt, 'isdss', $user_id, $region_name, $land_size, $irrigation_source, $primary_crop_name);
        
        if (mysqli_stmt_execute($stmt)) {
            $land_id = mysqli_insert_id($conn);
            
            // Insert well data if provided
            if (isset($_POST['well_depth']) && $_POST['well_depth'] > 0) {
                $well_depth = floatval($_POST['well_depth']);
                $pump_type = isset($_POST['pump_type']) ? mysqli_real_escape_string($conn, $_POST['pump_type']) : null;
                
                $well_sql = "INSERT INTO user_wells (land_id, water_depth, pump_type) 
                             VALUES (?, ?, ?)";
                $stmt = mysqli_prepare($conn, $well_sql);
                mysqli_stmt_bind_param($stmt, 'ids', $land_id, $well_depth, $pump_type);
                mysqli_stmt_execute($stmt);
            }
            
            // Log activity
            $activity_sql = "INSERT INTO activity_log (user_id, action, region) 
                            VALUES (?, 'Added land record', ?)";
            $stmt = mysqli_prepare($conn, $activity_sql);
            mysqli_stmt_bind_param($stmt, 'is', $user_id, $region_name);
            mysqli_stmt_execute($stmt);
            
            $success = "Land record added successfully!";
        } else {
            $error = "Error adding land record: " . mysqli_error($conn);
        }
    }
}

include 'header.php';
?>

<main class="flex-1 p-8">
    <h1 class="text-2xl font-bold text-green-800 mb-6">Add New Land Record</h1>

    <?php if ($error): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo $error; ?></span>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo $success; ?></span>
        </div>
    <?php endif; ?>

    <div class="bg-white p-6 rounded-lg shadow">
        <form method="POST" action="add_land.php" class="space-y-4">
            <!-- Region Selection -->
            <div>
                <label for="region_id" class="block text-gray-700">Region</label>
                <select id="region_id" name="region_id" class="w-full p-2 border rounded" required>
                    <option value="">Select Region</option>
                    <?php foreach ($regions as $region): ?>
                        <option value="<?php echo $region['region_id']; ?>" <?php echo isset($_POST['region_id']) && $_POST['region_id'] == $region['region_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($region['region_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Land Details -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="land_size" class="block text-gray-700">Land Size (hectares)</label>
                    <input type="number" step="0.01" id="land_size" name="land_size" 
                           class="w-full p-2 border rounded" required
                           value="<?php echo isset($_POST['land_size']) ? htmlspecialchars($_POST['land_size']) : ''; ?>">
                </div>

                <div>
                    <label for="irrigation_source" class="block text-gray-700">Irrigation Source</label>
                    <select id="irrigation_source" name="irrigation_source" class="w-full p-2 border rounded" required>
                        <option value="">Select Source</option>
                        <?php foreach ($irrigation_sources as $source): ?>
                            <option value="<?php echo $source['source_name']; ?>" <?php echo isset($_POST['irrigation_source']) && $_POST['irrigation_source'] == $source['source_name'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($source['source_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Crop Selection -->
            <div>
                <label for="primary_crop" class="block text-gray-700">Primary Crop</label>
                <select id="primary_crop" name="primary_crop" class="w-full p-2 border rounded" required>
                    <option value="">Select Crop</option>
                    <?php foreach ($crops as $crop): ?>
                        <option value="<?php echo $crop['crop_id']; ?>" <?php echo isset($_POST['primary_crop']) && $_POST['primary_crop'] == $crop['crop_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($crop['crop_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Water Details -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="water_depth" class="block text-gray-700">Water Depth (meters, Optional)</label>
                    <input type="number" step="0.01" id="water_depth" name="well_depth" 
                           class="w-full p-2 border rounded"
                           value="<?php echo isset($_POST['well_depth']) ? htmlspecialchars($_POST['well_depth']) : ''; ?>">
                </div>
            </div>

            <!-- Well Information (Optional) -->
            <div class="border-t pt-4 mt-4">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Well Information (Optional)</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="pump_type" class="block text-gray-700">Pump Type</label>
                        <select id="pump_type" name="pump_type" class="w-full p-2 border rounded">
                            <option value="">Select Pump Type</option>
                            <option value="Submersible" <?php echo isset($_POST['pump_type']) && $_POST['pump_type'] == 'Submersible' ? 'selected' : ''; ?>>Submersible</option>
                            <option value="Centrifugal" <?php echo isset($_POST['pump_type']) && $_POST['pump_type'] == 'Centrifugal' ? 'selected' : ''; ?>>Centrifugal</option>
                            <option value="Turbine" <?php echo isset($_POST['pump_type']) && $_POST['pump_type'] == 'Turbine' ? 'selected' : ''; ?>>Turbine</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="flex justify-end mt-6">
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                    Add Land Record
                </button>
            </div>
        </form>
    </div>
</main>

<script>
// Client-side validation
document.querySelector('form').addEventListener('submit', function(e) {
    const landSize = parseFloat(document.getElementById('land_size').value);
    if (landSize <= 0) {
        alert('Land size must be greater than 0');
        e.preventDefault();
        return false;
    }
    
    const regionId = document.getElementById('region_id').value;
    if (!regionId) {
        alert('Please select a region');
        e.preventDefault();
        return false;
    }
    
    const primaryCrop = document.getElementById('primary_crop').value;
    if (!primaryCrop) {
        alert('Please select a primary crop');
        e.preventDefault();
        return false;
    }
    
    return true;
});
</script>

<?php include 'footer.php'; ?>
