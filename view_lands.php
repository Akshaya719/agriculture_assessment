<?php
require_once 'includes/auth.php';
require_once 'includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch farmer-specific data
$user_id = $_SESSION['user_id'];

// Fetch lands data
$lands_query = "SELECT l.*, r.region_name, r.state, r.district, r.agro_climatic_zone 
                FROM lands l
                LEFT JOIN regions r ON l.region = r.region_name
                WHERE l.user_id = ?";
$stmt = mysqli_prepare($conn, $lands_query);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$lands_result = mysqli_stmt_get_result($stmt);
$lands = [];
while ($row = mysqli_fetch_assoc($lands_result)) {
    $lands[] = $row;
}
mysqli_stmt_close($stmt);

include 'header.php';
?>
<body style="background-image: url(images/schemes.jpg); background-size: cover;background-repeat: no-repeat;">

<main class="flex-1 p-8">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-green-800">
                <i class="fas fa-tractor mr-2"></i>My Lands
            </h1>
            <?php if (isset($_SESSION['isAdmin']) && $_SESSION['isAdmin'] == 1): ?>
                <a href="add_land.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
                    <i class="fas fa-plus mr-1"></i>Add New Land
                </a>
                <a href="add_land.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
                    <i class="fas fa-plus mr-1"></i>View All Land
                </a>
            <?php endif; ?>
        </div>

        <?php if (empty($lands)): ?>
            <div class="text-center py-6 text-gray-500">
                <p>No lands registered yet.</p>
                <?php if (isset($_SESSION['isAdmin']) && $_SESSION['isAdmin'] == 1): ?>
                    <a href="add_land.php" class="text-green-600 hover:underline mt-2 inline-block">Add your first land</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white border border-gray-200">
                    <thead>
                        <tr class="bg-gray-100 text-left text-gray-700">
                            <th class="py-2 px-4 border-b">Region</th>
                            <th class="py-2 px-4 border-b">State</th>
                            <th class="py-2 px-4 border-b">District</th>
                            <th class="py-2 px-4 border-b">Agro-Climatic Zone</th>
                            <th class="py-2 px-4 border-b">Land Size (ha)</th>
                            <th class="py-2 px-4 border-b">Irrigation Source</th>
                            <th class="py-2 px-4 border-b">Primary Crop</th>
                            <?php if (isset($_SESSION['isAdmin']) && $_SESSION['isAdmin'] == 1): ?>
                                <th class="py-2 px-4 border-b">Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lands as $land): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($land['region_name'] ?? $land['region']); ?></td>
                                <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($land['state'] ?? 'N/A'); ?></td>
                                <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($land['district'] ?? 'N/A'); ?></td>
                                <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($land['agro_climatic_zone'] ?? 'N/A'); ?></td>
                                <td class="py-2 px-4 border-b"><?php echo number_format($land['land_size'], 2); ?></td>
                                <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($land['irrigation_source']); ?></td>
                                <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($land['primary_crop']); ?></td>
                                <?php if (isset($_SESSION['isAdmin']) && $_SESSION['isAdmin'] == 1): ?>
                                    <td class="py-2 px-4 border-b flex space-x-2">
                                        <a href="edit_land.php?id=<?php echo $land['id']; ?>" class="text-yellow-600 hover:text-yellow-800">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="delete_land.php?id=<?php echo $land['id']; ?>" class="text-red-600 hover:text-red-800" onclick="return confirm('Are you sure you want to delete this land record?');">
                                            <i class="fas fa-trash"></i>Delete
                                        </a>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</main>
</body>


<?php
mysqli_free_result($lands_result);
mysqli_close($conn);
?>