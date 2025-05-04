<?php

require_once 'includes/auth.php';
require_once 'includes/config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['isAdmin']) || $_SESSION['isAdmin'] != 1) {
    header("Location: login.php");
    exit();
}

// Fetch region details
if (!isset($_GET['region_id'])) {
    header("Location: regions.php?error=" . urlencode("No region specified"));
    exit();
}

$region_id = (int)$_GET['region_id'];
$region_query = "SELECT region_name, state, district, agro_climatic_zone
                 FROM regions
                 WHERE region_id = ?";
$stmt = mysqli_prepare($conn, $region_query);
mysqli_stmt_bind_param($stmt, "i", $region_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($region_data = mysqli_fetch_assoc($result)) {
    $region_name = $region_data['region_name'];
    $state = $region_data['state'];
    $district = $region_data['district'];
    $agro_climatic_zone = $region_data['agro_climatic_zone'];
} else {
    header("Location: regions.php?error=" . urlencode("Region not found"));
    exit();
}
mysqli_stmt_close($stmt);

// Fetch existing land holdings
$holdings_query = "SELECT id, holding_size, count FROM land_holdings WHERE region_id = ?";
$stmt = mysqli_prepare($conn, $holdings_query);
mysqli_stmt_bind_param($stmt, "i", $region_id);
mysqli_stmt_execute($stmt);
$holdings_result = mysqli_stmt_get_result($stmt);
$land_holdings = [];
while ($row = mysqli_fetch_assoc($holdings_result)) {
    $land_holdings[] = $row;
}
mysqli_stmt_close($stmt);

// Fetch existing irrigation sources
$irrigation_query = "SELECT ri.id, s.source_name, ri.area_irrigated
                     FROM region_irrigation ri
                     JOIN irrigation_sources s ON ri.source_id = s.source_id
                     WHERE ri.region_id = ?";
$stmt = mysqli_prepare($conn, $irrigation_query);
mysqli_stmt_bind_param($stmt, "i", $region_id);
mysqli_stmt_execute($stmt);
$irrigation_result = mysqli_stmt_get_result($stmt);
$irrigation_sources = [];
while ($row = mysqli_fetch_assoc($irrigation_result)) {
    $irrigation_sources[] = $row;
}
mysqli_stmt_close($stmt);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_region_name = mysqli_real_escape_string($conn, $_POST['region_name']);
    $new_state = mysqli_real_escape_string($conn, $_POST['state']);
    $new_district = mysqli_real_escape_string($conn, $_POST['district']);
    $new_agro_climatic_zone = mysqli_real_escape_string($conn, $_POST['agro_climatic_zone']);

    mysqli_begin_transaction($conn);

    try {
        // Update region
        $update_region_query = "UPDATE regions 
                                SET region_name = ?, state = ?, district = ?, agro_climatic_zone = ?
                                WHERE region_id = ?";
        $stmt = mysqli_prepare($conn, $update_region_query);
        mysqli_stmt_bind_param($stmt, "ssssi", $new_region_name, $new_state, $new_district, $new_agro_climatic_zone, $region_id);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error updating region: " . mysqli_error($conn));
        }
        mysqli_stmt_close($stmt);

        // Delete existing land holdings and irrigation sources
        $delete_holdings_query = "DELETE FROM land_holdings WHERE region_id = ?";
        $stmt = mysqli_prepare($conn, $delete_holdings_query);
        mysqli_stmt_bind_param($stmt, "i", $region_id);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error deleting land holdings: " . mysqli_error($conn));
        }
        mysqli_stmt_close($stmt);

        $delete_irrigation_query = "DELETE FROM region_irrigation WHERE region_id = ?";
        $stmt = mysqli_prepare($conn, $delete_irrigation_query);
        mysqli_stmt_bind_param($stmt, "i", $region_id);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Error deleting irrigation sources: " . mysqli_error($conn));
        }
        mysqli_stmt_close($stmt);

        // Insert updated land holdings
        if (!empty($_POST['holding_size']) && !empty($_POST['count'])) {
            $holding_sizes = $_POST['holding_size'];
            $counts = $_POST['count'];

            for ($i = 0; $i < count($holding_sizes); $i++) {
                $holding_size = mysqli_real_escape_string($conn, $holding_sizes[$i]);
                $count = (int)$counts[$i];

                $holding_query = "INSERT INTO land_holdings (region_id, holding_size, count) 
                                 VALUES (?, ?, ?)";
                $stmt = mysqli_prepare($conn, $holding_query);
                mysqli_stmt_bind_param($stmt, "isi", $region_id, $holding_size, $count);
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Error inserting land holding: " . mysqli_error($conn));
                }
                mysqli_stmt_close($stmt);
            }
        }

        // Insert updated irrigation sources
        if (!empty($_POST['source_name']) && !empty($_POST['area_irrigated'])) {
            $source_names = $_POST['source_name'];
            $areas_irrigated = $_POST['area_irrigated'];

            for ($i = 0; $i < count($source_names); $i++) {
                $source_name = mysqli_real_escape_string($conn, $source_names[$i]);
                $area_irrigated = (float)$areas_irrigated[$i];

                $source_query = "SELECT source_id FROM irrigation_sources WHERE source_name = ?";
                $stmt = mysqli_prepare($conn, $source_query);
                mysqli_stmt_bind_param($stmt, "s", $source_name);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);

                if (mysqli_num_rows($result) > 0) {
                    $source_row = mysqli_fetch_assoc($result);
                    $source_id = $source_row['source_id'];
                } else {
                    $insert_source_query = "INSERT INTO irrigation_sources (source_name) VALUES (?)";
                    $stmt = mysqli_prepare($conn, $insert_source_query);
                    mysqli_stmt_bind_param($stmt, "s", $source_name);
                    if (!mysqli_stmt_execute($stmt)) {
                        throw new Exception("Error inserting irrigation source: " . mysqli_error($conn));
                    }
                    $source_id = mysqli_insert_id($conn);
                    mysqli_stmt_close($stmt);
                }

                $irrigation_query = "INSERT INTO region_irrigation (region_id, source_id, area_irrigated) 
                                    VALUES (?, ?, ?)";
                $stmt = mysqli_prepare($conn, $irrigation_query);
                mysqli_stmt_bind_param($stmt, "iid", $region_id, $source_id, $area_irrigated);
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Error inserting region irrigation: " . mysqli_error($conn));
                }
                mysqli_stmt_close($stmt);
            }
        }

        // Commit transaction
        mysqli_commit($conn);
        header("Location: regions.php?success=Region updated successfully");
        exit();

    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        $error = $e->getMessage();
        header("Location: regions.php?error=" . urlencode($error));
        exit();
    }
}

include 'header.php';
?>

<main class="flex-1 p-8">
    <h1 class="text-2xl font-bold text-green-800 mb-6">Edit Region Entry</h1>

    <div class="bg-white p6 rounded-lg shadow mb-6">
        <h2 class="text-xl font-semibold mb-4">Edit Region Entry</h2>
        <form method="POST" action="edit_region.php?region_id=<?php echo $region_id; ?>" class="space-y-4">
            <div>
                <label for="region_name" class="block text-gray-700">Region Name</label>
                <input type="text" id="region_name" name="region_name" value="<?php echo htmlspecialchars($region_name); ?>" class="w-full p-2 border rounded" required>
            </div>
            <div>
                <label for="state" class="block text-gray-700">State</label>
                <input type="text" id="state" name="state" value="<?php echo htmlspecialchars($state); ?>" class="w-full p-2 border rounded" required>
            </div>
            <div>
                <label for="district" class="block text-gray-700">District</label>
                <input type="text" id="district" name="district" value="<?php echo htmlspecialchars($district); ?>" class="w-full p-2 border rounded" required>
            </div>
            <div>
                <label for="agro_climatic_zone" class="block text-gray-700">Agro-Climatic Zone</label>
                <input type="text" id="agro_climatic_zone" name="agro_climatic_zone" value="<?php echo htmlspecialchars($agro_climatic_zone); ?>" class="w-full p-2 border rounded" required>
            </div>

            <!-- Land Holdings -->
            <div class="mb-4">
                <label class="block text-gray-700">Land Holdings</label>
                <?php if (empty($land_holdings)): ?>
                    <div class="flex items-center space-x-4">
                        <input type="text" name="holding_size[]" placeholder="Holding Size" class="w-full p-2 border rounded" required>
                        <input type="number" name="count[]" placeholder="Count" class="w-full p-2 border rounded" required>
                        <!-- <button type="button" onclick="addHoldingField()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">+</button> -->
                    </div>
                <?php else: ?>
                    <?php foreach ($land_holdings as $holding): ?>
                        <div class="flex items-center space-x-4">
                            <input type="text" name="holding_size[]" value="<?php echo htmlspecialchars($holding['holding_size']); ?>" class="w-full p-2 border rounded" required>
                            <input type="number" name="count[]" value="<?php echo htmlspecialchars($holding['count']); ?>" class="w-full p-2 border rounded" required>
                            <!-- <button type="button" onclick="addHoldingField()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">+</button> -->
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Irrigation Sources -->
            <div class="mb-4">
                <label class="block text-gray-700">Irrigation Sources</label>
                <?php if (empty($irrigation_sources)): ?>
                    <div class="flex items-center space-x-4">
                        <input type="text" name="source_name[]" placeholder="Source Name" class="w-full p-2 border rounded" required>
                        <input type="number" step="0.01" name="area_irrigated[]" placeholder="Area Irrigated (ha)" class="w-full p-2 border rounded" required>
                        <!-- <button type="button" onclick="addIrrigationField()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">+</button> -->
                    </div>
                <?php else: ?>
                    <?php foreach ($irrigation_sources as $source): ?>
                        <div class="flex items-center space-x-4">
                            <input type="text" name="source_name[]" value="<?php echo htmlspecialchars($source['source_name']); ?>" class="w-full p-2 border rounded" required>
                            <input type="number" step="0.01" name="area_irrigated[]" value="<?php echo htmlspecialchars($source['area_irrigated']); ?>" class="w-full p-2 border rounded" required>
                            <!-- <button type="button" onclick="addIrrigationField()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">+</button> -->
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Update Region Entry</button>
        </form>
    </div>
</main>

<script>
    function addHoldingField() {
        const container = document.querySelector('.mb-4:nth-of-type(1) .flex.items-center');
        const newField = container.cloneNode(true);
        newField.querySelectorAll('input').forEach(input => input.value = '');
        container.parentNode.appendChild(newField);
    }

    function addIrrigationField() {
        const container = document.querySelector('.mb-4:nth-of-type(2) .flex.items-center');
        const newField = container.cloneNode(true);
        newField.querySelectorAll('input').forEach(input => input.value = '');
        container.parentNode.appendChild(newField);
    }
</script>

<?php
mysqli_close($conn);
?>
</body>
</html>