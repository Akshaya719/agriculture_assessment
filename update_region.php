<?php
require_once 'includes/auth.php';

// Only admin can access this page
if (!$_SESSION['isAdmin']) {
    header('Location: regions.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: regions.php');
    exit;
}

$region_id = intval($_GET['id']);
$error = '';
$success = '';

// Fetch region data
$region_query = "SELECT * FROM regions WHERE region_id = ?";
$stmt = mysqli_prepare($conn, $region_query);

// Bind the region_id parameter to the prepared statement
mysqli_stmt_bind_param($stmt, 'i', $region_id);

// Execute the statement
mysqli_stmt_execute($stmt);

// Get the result from the executed statement
$result = mysqli_stmt_get_result($stmt);

// Fetch the region as an associative array
$region = mysqli_fetch_assoc($result);

// Close the prepared statement
mysqli_stmt_close($stmt);


if (!$region) {
    header('Location: regions.php');
    exit;
}

// Fetch land holdings for this region
$holdings_query = "SELECT * FROM land_holdings WHERE region_id = ?";
$stmt = mysqli_prepare($conn, $holdings_query);
mysqli_stmt_bind_param($stmt, 'i', $region_id);
mysqli_stmt_execute($stmt);
$holdings_result = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

// Fetch irrigation sources for this region
$irrigation_query = "SELECT ri.*, s.source_name 
                     FROM region_irrigation ri
                     JOIN irrigation_sources s ON ri.source_id = s.source_id
                     WHERE ri.region_id = ?";
$stmt = mysqli_prepare($conn, $irrigation_query);
mysqli_stmt_bind_param($stmt, 'i', $region_id);
mysqli_stmt_execute($stmt);
$irrigation_result = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

// Get all irrigation sources for dropdown
$all_sources_query = "SELECT * FROM irrigation_sources ORDER BY source_name";
$all_sources_result = mysqli_query($conn, $all_sources_query);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic region info
    $region_name = trim($_POST['region_name']);
    $state = trim($_POST['state']);
    $district = trim($_POST['district']);
    $agro_climatic_zone = trim($_POST['agro_climatic_zone']);

    // Land holdings data
    $land_holdings = $_POST['land_holdings'] ?? [];

    // Irrigation data
    $irrigation_sources = $_POST['irrigation'] ?? [];

    if (empty($region_name) || empty($state) || empty($district)) {
        $error = 'Region name, state and district are required';
    } else {
        // Start transaction
        mysqli_begin_transaction($conn);

        try {
            // Update region basic info
            $update_query = "UPDATE regions SET 
                            region_name = ?, 
                            state = ?, 
                            district = ?, 
                            agro_climatic_zone = ?
                            WHERE region_id = ?";
            $stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($stmt, 'ssssi', $region_name, $state, $district, $agro_climatic_zone, $region_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // Delete existing land holdings
            $delete_holdings = "DELETE FROM land_holdings WHERE region_id = ?";
            $stmt = mysqli_prepare($conn, $delete_holdings);
            mysqli_stmt_bind_param($stmt, 'i', $region_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // Insert new land holdings
            foreach ($land_holdings as $holding) {
                if (!empty($holding['size_category']) && !empty($holding['percentage']) && !empty($holding['average_size'])) {
                    $insert_holding = "INSERT INTO land_holdings 
                                     (region_id, size_category, percentage, average_size)
                                     VALUES (?, ?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $insert_holding);
                    mysqli_stmt_bind_param(
                        $stmt,
                        'isdd',
                        $region_id,
                        $holding['size_category'],
                        $holding['percentage'],
                        $holding['average_size']
                    );
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }
            }

            // Delete existing irrigation sources
            $delete_irrigation = "DELETE FROM region_irrigation WHERE region_id = ?";
            $stmt = mysqli_prepare($conn, $delete_irrigation);
            mysqli_stmt_bind_param($stmt, 'i', $region_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // Insert new irrigation sources
            foreach ($irrigation_sources as $irrigation) {
                if (!empty($irrigation['source_id']) && !empty($irrigation['percentage'])) {
                    $insert_irrigation = "INSERT INTO region_irrigation 
                                         (region_id, source_id, percentage)
                                         VALUES (?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $insert_irrigation);
                    mysqli_stmt_bind_param(
                        $stmt,
                        'iid',
                        $region_id,
                        $irrigation['source_id'],
                        $irrigation['percentage']
                    );
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }
            }

            // Commit transaction
            mysqli_commit($conn);

            $success = 'Region updated successfully!';

            // Log the activity
            $log_query = "INSERT INTO activity_log (user_id, action, region, created_at) 
                          VALUES (?, 'Updated region: $region_name', ?, CURRENT_TIMESTAMP)";
            $log_stmt = mysqli_prepare($conn, $log_query);
            mysqli_stmt_bind_param($log_stmt, 'is', $_SESSION['user_id'], $region_name);
            mysqli_stmt_execute($log_stmt);
            mysqli_stmt_close($log_stmt);

            // Refresh the data
            header("Location: update_region.php?id=$region_id");
            exit;
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = 'Error updating region: ' . $e->getMessage();
        }
    }
}

// After getting the POST data, add debug output
error_log(print_r($_POST, true));

// Then process the arrays more carefully:
$land_holdings = [];
if (!empty($_POST['land_holdings']) && is_array($_POST['land_holdings'])) {
    foreach ($_POST['land_holdings'] as $holding) {
        if (!empty($holding['size_category']) && isset($holding['percentage']) && isset($holding['average_size'])) {
            $land_holdings[] = [
                'size_category' => $holding['size_category'],
                'percentage' => (float)$holding['percentage'],
                'average_size' => (float)$holding['average_size']
            ];
        }
    }
}

$irrigation_sources = [];
if (!empty($_POST['irrigation']) && is_array($_POST['irrigation'])) {
    foreach ($_POST['irrigation'] as $source) {
        if (!empty($source['source_id']) && isset($source['percentage'])) {
            $irrigation_sources[] = [
                'source_id' => (int)$source['source_id'],
                'percentage' => (float)$source['percentage']
            ];
        }
    }
}

// Then use these sanitized arrays in your database operations
?>

<?php include 'header.php'; ?>

<main class="flex-1 p-8">
    <div class="max-w-6xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-green-800">Update Region: <?php echo htmlspecialchars($region['region_name']); ?></h1>
            <a href="regions.php" class="text-gray-600 hover:text-gray-800">
                <i class="fas fa-arrow-left mr-1"></i> Back to Regions
            </a>
        </div>

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

        <div class="bg-white rounded-lg shadow-md p-6">
            <form method="POST">
                <div class="space-y-8">
                    <!-- Basic Region Info -->
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-2">Basic Information</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="region_name" class="block text-sm font-medium text-gray-700">Region Name *</label>
                                <input type="text" id="region_name" name="region_name" required
                                    value="<?php echo htmlspecialchars($region['region_name']); ?>"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 p-2 border">
                            </div>
                            <div>
                                <label for="state" class="block text-sm font-medium text-gray-700">State *</label>
                                <input type="text" id="state" name="state" required
                                    value="<?php echo htmlspecialchars($region['state']); ?>"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 p-2 border">
                            </div>
                            <div>
                                <label for="district" class="block text-sm font-medium text-gray-700">District *</label>
                                <input type="text" id="district" name="district" required
                                    value="<?php echo htmlspecialchars($region['district']); ?>"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 p-2 border">
                            </div>
                            <div>
                                <label for="agro_climatic_zone" class="block text-sm font-medium text-gray-700">Agro-Climatic Zone</label>
                                <input type="text" id="agro_climatic_zone" name="agro_climatic_zone"
                                    value="<?php echo htmlspecialchars($region['agro_climatic_zone']); ?>"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 p-2 border">
                            </div>
                        </div>
                    </div>

                    <!-- Land Holdings -->
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-2">Land Holdings</h2>
                        <div id="land-holdings-container" class="space-y-4">
                            <?php if (mysqli_num_rows($holdings_result) > 0): ?>
                                <?php while ($holding = mysqli_fetch_assoc($holdings_result)): ?>
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end holding-row">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Size Category</label>
                                            <select name="land_holdings[][size_category]" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 p-2 border">
                                                <option value="small" <?php echo $holding['size_category'] === 'small' ? 'selected' : ''; ?>>Small</option>
                                                <option value="medium" <?php echo $holding['size_category'] === 'medium' ? 'selected' : ''; ?>>Medium</option>
                                                <option value="large" <?php echo $holding['size_category'] === 'large' ? 'selected' : ''; ?>>Large</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Percentage</label>
                                            <input type="number" step="0.01" name="land_holdings[][percentage]"
                                                value="<?php echo htmlspecialchars($holding['percentage']); ?>"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 p-2 border">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Average Size (ha)</label>
                                            <input type="number" step="0.01" name="land_holdings[][average_size]"
                                                value="<?php echo htmlspecialchars($holding['average_size']); ?>"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 p-2 border">
                                        </div>
                                        <div>
                                            <button type="button" class="remove-holding-btn bg-red-100 text-red-700 hover:bg-red-200 px-3 py-2 rounded-md text-sm">
                                                <i class="fas fa-trash mr-1"></i> Remove
                                            </button>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end holding-row">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Size Category</label>
                                        <select name="land_holdings[][size_category]" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 p-2 border">
                                            <option value="small">Small</option>
                                            <option value="medium">Medium</option>
                                            <option value="large">Large</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Percentage</label>
                                        <input type="number" step="0.01" name="land_holdings[][percentage]"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 p-2 border">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Average Size (ha)</label>
                                        <input type="number" step="0.01" name="land_holdings[][average_size]"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 p-2 border">
                                    </div>
                                    <div>
                                        <button type="button" class="remove-holding-btn bg-red-100 text-red-700 hover:bg-red-200 px-3 py-2 rounded-md text-sm">
                                            <i class="fas fa-trash mr-1"></i> Remove
                                        </button>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <button type="button" id="add-holding-btn" class="mt-4 bg-gray-100 text-gray-700 hover:bg-gray-200 px-4 py-2 rounded-md text-sm">
                            <i class="fas fa-plus mr-1"></i> Add Land Holding
                        </button>
                    </div>

                    <!-- Irrigation Sources -->
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800 mb-4 border-b pb-2">Irrigation Sources</h2>
                        <div id="irrigation-container" class="space-y-4">
                            <?php if (mysqli_num_rows($irrigation_result) > 0): ?>
                                <?php while ($irrigation = mysqli_fetch_assoc($irrigation_result)): ?>
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end irrigation-row">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Source</label>
                                            <select name="irrigation[][source_id]" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 p-2 border">
                                                <?php mysqli_data_seek($all_sources_result, 0); ?>
                                                <?php while ($source = mysqli_fetch_assoc($all_sources_result)): ?>
                                                    <option value="<?php echo $source['source_id']; ?>" <?php echo $irrigation['source_id'] == $source['source_id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($source['source_name']); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Percentage</label>
                                            <input type="number" step="0.01" name="irrigation[][percentage]"
                                                value="<?php echo htmlspecialchars($irrigation['percentage']); ?>"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 p-2 border">
                                        </div>
                                        <div class="col-span-2">
                                            <button type="button" class="remove-irrigation-btn bg-red-100 text-red-700 hover:bg-red-200 px-3 py-2 rounded-md text-sm">
                                                <i class="fas fa-trash mr-1"></i> Remove
                                            </button>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end irrigation-row">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Source</label>
                                        <select name="irrigation[][source_id]" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 p-2 border">
                                            <?php mysqli_data_seek($all_sources_result, 0); ?>
                                            <?php while ($source = mysqli_fetch_assoc($all_sources_result)): ?>
                                                <option value="<?php echo $source['source_id']; ?>">
                                                    <?php echo htmlspecialchars($source['source_name']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Percentage</label>
                                        <input type="number" step="0.01" name="irrigation[][percentage]"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 p-2 border">
                                    </div>
                                    <div class="col-span-2">
                                        <button type="button" class="remove-irrigation-btn bg-red-100 text-red-700 hover:bg-red-200 px-3 py-2 rounded-md text-sm">
                                            <i class="fas fa-trash mr-1"></i> Remove
                                        </button>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <button type="button" id="add-irrigation-btn" class="mt-4 bg-gray-100 text-gray-700 hover:bg-gray-200 px-4 py-2 rounded-md text-sm">
                            <i class="fas fa-plus mr-1"></i> Add Irrigation Source
                        </button>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end space-x-4 pt-6">
                        <a href="regions.php" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            Cancel
                        </a>
                        <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            Update Region
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
    // When cloning rows, ensure the array indices are maintained
    document.getElementById('add-holding-btn').addEventListener('click', function() {
        const container = document.getElementById('land-holdings-container');
        const newRow = document.querySelector('.holding-row').cloneNode(true);

        // Clear values but maintain the array structure
        newRow.querySelectorAll('input').forEach(input => input.value = '');
        newRow.querySelector('select').selectedIndex = 0;

        // Remove any existing index numbers to ensure PHP creates new array elements
        Array.from(newRow.querySelectorAll('[name]')).forEach(el => {
            el.name = el.name.replace(/\[\d+\]/, '[]');
        });

        container.appendChild(newRow);
    });

    document.addEventListener('DOMContentLoaded', function() {
        // Add new land holding row
        document.getElementById('add-holding-btn').addEventListener('click', function() {
            const container = document.getElementById('land-holdings-container');
            const newRow = document.querySelector('.holding-row').cloneNode(true);

            // Clear input values
            newRow.querySelectorAll('input').forEach(input => input.value = '');
            newRow.querySelector('select').selectedIndex = 0;

            container.appendChild(newRow);
        });

        // Add new irrigation row
        document.getElementById('add-irrigation-btn').addEventListener('click', function() {
            const container = document.getElementById('irrigation-container');
            const newRow = document.querySelector('.irrigation-row').cloneNode(true);

            // Clear input values
            newRow.querySelector('input').value = '';
            newRow.querySelector('select').selectedIndex = 0;

            container.appendChild(newRow);
        });

        // Remove land holding row
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-holding-btn')) {
                const container = document.getElementById('land-holdings-container');
                if (container.children.length > 1) {
                    e.target.closest('.holding-row').remove();
                } else {
                    alert('You must have at least one land holding entry.');
                }
            }

            if (e.target.classList.contains('remove-irrigation-btn')) {
                const container = document.getElementById('irrigation-container');
                if (container.children.length > 1) {
                    e.target.closest('.irrigation-row').remove();
                } else {
                    alert('You must have at least one irrigation source entry.');
                }
            }
        });
    });
</script>

</body>

</html>