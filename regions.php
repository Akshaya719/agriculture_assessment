<?php
require_once 'includes/auth.php';
require_once 'includes/config.php';

// Fetch regions and group by region_name
$regions_query = "SELECT r.region_id, r.region_name, r.state, r.district, r.agro_climatic_zone,
                        GROUP_CONCAT(CONCAT(lh.holding_size, ': ', lh.count, ' holdings') SEPARATOR ', ') AS holdings,
                        GROUP_CONCAT(CONCAT(s.source_name, ': ', ri.area_irrigated, ' ha') SEPARATOR ', ') AS irrigation
                  FROM regions r
                  LEFT JOIN land_holdings lh ON r.region_id = lh.region_id
                  LEFT JOIN region_irrigation ri ON r.region_id = ri.region_id
                  LEFT JOIN irrigation_sources s ON ri.source_id = s.source_id
                  GROUP BY r.region_id, r.region_name, r.state, r.district, r.agro_climatic_zone";

// Fetch all regions to group them
$regions_result = mysqli_query($conn, $regions_query) or die(mysqli_error($conn));

// Organize data by region_name
$regions_data = [];
while ($row = mysqli_fetch_assoc($regions_result)) {
    $region_name = $row['region_name'];
    if (!isset($regions_data[$region_name])) {
        $regions_data[$region_name] = [];
    }
    $regions_data[$region_name][] = [
        'region_id' => $row['region_id'],
        'state' => $row['state'],
        'district' => $row['district'],
        'agro_climatic_zone' => $row['agro_climatic_zone'],
        'holdings' => $row['holdings'],
        'irrigation' => $row['irrigation']
    ];
}

include 'header.php';
?>
<body style="background-image: url(images/schemes.jpg); background-size: cover;background-repeat: no-repeat;">

<main class="flex-1 p-8">
    <h1 class="text-2xl font-bold text-green-800 mb-6">Regional Data</h1>

    <!-- Display success/error messages -->
    <?php if (isset($_GET['success'])): ?>
        <div class="bg-green-100 text-green-800 p-4 mb-4 rounded"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="bg-red-100 text-red-800 p-4 mb-4 rounded"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <!-- Add New Region Section (Admin Only) -->
    <?php if (isset($_SESSION['isAdmin']) && $_SESSION['isAdmin'] == 1): ?>
        <div class="bg-white p-6 rounded-lg shadow mb-6">
            <h2 class="text-xl font-semibold mb-4">Add New Region</h2>
            <form method="POST" action="add_region.php" class="space-y-4">
                <div>
                    <label for="region_name" class="block text-gray-700">Region Name</label>
                    <input type="text" id="region_name" name="region_name" class="w-full p-2 border rounded" required>
                </div>
                <div>
                    <label for="state" class="block text-gray-700">State</label>
                    <input type="text" id="state" name="state" class="w-full p-2 border rounded" required>
                </div>
                <div>
                    <label for="district" class="block text-gray-700">District</label>
                    <input type="text" id="district" name="district" class="w-full p-2 border rounded" required>
                </div>
                <div>
                    <label for="agro_climatic_zone" class="block text-gray-700">Agro-Climatic Zone</label>
                    <input type="text" id="agro_climatic_zone" name="agro_climatic_zone" class="w-full p-2 border rounded" required>
                </div>

                <!-- Land Holdings -->
                <div class="mb-4">
                    <label class="block text-gray-700">Land Holdings</label>
                    <div class="flex items-center space-x-4">
                        <input type="text" name="holding_size[]" placeholder="Holding Size" class="w-full p-2 border rounded" required>
                        <input type="number" name="count[]" placeholder="Count" class="w-full p-2 border rounded" required>
                        <!-- <button type="button" onclick="addHoldingField()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">+</button> -->
                    </div>
                </div>

                <!-- Irrigation Sources -->
                <div class="mb-4">
                    <label class="block text-gray-700">Irrigation Sources</label>
                    <div class="flex items-center space-x-4">
                        <input type="text" name="source_name[]" placeholder="Source Name" class="w-full p-2 border rounded" required>
                        <input type="number" step="0.01" name="area_irrigated[]" placeholder="Area Irrigated (ha)" class="w-full p-2 border rounded" required>
                        <!-- <button type="button" onclick="addIrrigationField()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">+</button> -->
                    </div>
                </div>

                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Add Region</button>
            </form>
        </div>
    <?php endif; ?>

    <!-- Regions Table with Collapsible States -->
    <div class="bg-white p-6 rounded-lg shadow">
        <h2 class="text-xl font-semibold mb-4">Regions Overview</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="py-2 px-4 text-left">Region</th>
                        <th class="py-2 px-4 text-left">Details</th>
                        <?php if (isset($_SESSION['isAdmin']) && $_SESSION['isAdmin'] == 1): ?>
                            <!-- <th class="py-2 px-4 text-left">Actions</th> -->
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($regions_data as $region_name => $region_entries): ?>
                        <tr class="border-b">
                            <td class="py-2 px-4"><?php echo htmlspecialchars($region_name); ?></td>
                            <td class="py-2 px-4">
                                <button onclick="toggleDetails('region-<?php echo htmlspecialchars(str_replace(' ', '-', $region_name)); ?>')" class="text-blue-600 hover:underline">Show/Hide States</button>
                                <div id="region-<?php echo htmlspecialchars(str_replace(' ', '-', $region_name)); ?>" class="hidden mt-2">
                                    <table class="min-w-full border">
                                        <thead>
                                            <tr class="bg-gray-50">
                                                <th class="py-1 px-2 text-left">State</th>
                                                <th class="py-1 px-2 text-left">District</th>
                                                <th class="py-1 px-2 text-left">Agro-Climatic Zone</th>
                                                <th class="py-1 px-2 text-left">Land Holdings</th>
                                                <th class="py-1 px-2 text-left">Irrigation Sources</th>
                                                <?php if (isset($_SESSION['isAdmin']) && $_SESSION['isAdmin'] == 1): ?>
                                                    <th class="py-1 px-2 text-left">Actions</th>
                                                <?php endif; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($region_entries as $entry): ?>
                                                <tr class="border-b">
                                                    <td class="py-1 px-2"><?php echo htmlspecialchars($entry['state'] ?? 'N/A'); ?></td>
                                                    <td class="py-1 px-2"><?php echo htmlspecialchars($entry['district'] ?? 'N/A'); ?></td>
                                                    <td class="py-1 px-2"><?php echo htmlspecialchars($entry['agro_climatic_zone'] ?? 'N/A'); ?></td>
                                                    <td class="py-1 px-2"><?php echo str_replace(',', '<br>', htmlspecialchars($entry['holdings'] ?? '')); ?></td>
                                                    <td class="py-1 px-2"><?php echo str_replace(',', '<br>', htmlspecialchars($entry['irrigation'] ?? '')); ?></td>
                                                    <?php if (isset($_SESSION['isAdmin']) && $_SESSION['isAdmin'] == 1): ?>
                                                        <td class="py-1 px-2 flex space-x-2">
                                                            <a href="edit_region.php?region_id=<?php echo $entry['region_id']; ?>" class="bg-yellow-500 text-white px-2 py-1 rounded hover:bg-yellow-600">Edit</a>
                                                            <a href="delete_region.php?id=<?php echo $entry['region_id']; ?>" class="bg-red-600 text-white px-2 py-1 rounded hover:bg-red-700" onclick="return confirm('Are you sure you want to delete this region entry?');">Delete</a>
                                                        </td>
                                                    <?php endif; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                            <?php if (isset($_SESSION['isAdmin']) && $_SESSION['isAdmin'] == 1): ?>
                                <td class="py-2 px-4 flex space-x-2">
                                    <!-- Region-level actions (e.g., delete all entries for this region) can be added here if needed -->
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
</body>

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

    function toggleDetails(regionId) {
        const element = document.getElementById(regionId);
        element.classList.toggle('hidden');
    }
</script>

<?php
mysqli_free_result($regions_result);
mysqli_close($conn);
?>
</body>
</html>