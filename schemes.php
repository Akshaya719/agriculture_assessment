<?php
require_once 'includes/auth.php';

// Fetch schemes data
$schemes_query = "SELECT s.scheme_id, s.scheme_name, s.description, s.eligibility_criteria, s.benefits, s.official_link,
                         GROUP_CONCAT(CONCAT(r.region_name, ': ', rs.implementation_status, ' (', rs.beneficiaries_count, ' beneficiaries)') SEPARATOR '|') as regions
                  FROM beneficiary_schemes s
                  LEFT JOIN region_schemes rs ON s.scheme_id = rs.scheme_id
                  LEFT JOIN regions r ON rs.region_id = r.region_id
                  GROUP BY s.scheme_id";
$schemes_result = mysqli_query($conn, $schemes_query) or die("Schemes query failed: " . mysqli_error($conn));
?>

<?php include 'header.php'; ?>

<body style="background-image: url(images/schemes.jpg); background-size: cover;background-repeat: no-repeat;">



    <main class="flex-1 p-8">
        <div class="max-w-4xl mx-auto">
            <!-- Header with Add Button -->
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-green-800">Beneficiary Schemes</h1>
                <?php if ($_SESSION['isAdmin']): ?>
                    <a href="add_scheme.php" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md flex items-center">
                        <i class="fas fa-plus mr-2"></i> Add New Scheme
                    </a>
                <?php endif; ?>
            </div>

            <div class="space-y-6">
                <?php while ($scheme = mysqli_fetch_assoc($schemes_result)):
                    $regions = explode('|', $scheme['regions']);
                    $status_classes = [
                        'ongoing' => 'bg-blue-100 text-blue-800',
                        'completed' => 'bg-green-100 text-green-800',
                        'planned' => 'bg-yellow-100 text-yellow-800'
                    ];
                ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden border border-gray-200 hover:shadow-lg transition-shadow">
                        <div class="p-6">
                            <div class="flex justify-between items-start">
                                <h2 class="text-xl font-bold text-green-700 mb-2"><?php echo htmlspecialchars($scheme['scheme_name']); ?></h2>
                                <?php if ($_SESSION['isAdmin']): ?>
                                    <div class="flex space-x-2">
                                        <a href="edit_scheme.php?id=<?php echo $scheme['scheme_id']; ?>"
                                            class="text-blue-600 hover:text-blue-800 px-2 py-1 rounded hover:bg-blue-50"
                                            title="Edit Scheme">
                                            <i class="fas fa-edit">Edit</i>
                                        </a>
                                        <a href="delete_scheme.php?id=<?php echo $scheme['scheme_id']; ?>"
                                            class="text-red-600 hover:text-red-800 px-2 py-1 rounded hover:bg-red-50"
                                            title="Delete Scheme"
                                            onclick="return confirm('Are you sure you want to delete this scheme?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Rest of the scheme card content remains the same -->
                            <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($scheme['description']); ?></p>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <h3 class="font-semibold text-gray-700">Eligibility Criteria</h3>
                                    <p class="text-gray-600"><?php echo htmlspecialchars($scheme['eligibility_criteria']); ?></p>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-700">Benefits</h3>
                                    <p class="text-gray-600"><?php echo htmlspecialchars($scheme['benefits']); ?></p>
                                </div>
                            </div>

                            <div class="mb-4">
                                <h3 class="font-semibold text-gray-700">Implementation Status</h3>
                                <div class="mt-2 space-y-2">
                                    <?php foreach ($regions as $region):
                                        if (empty($region)) continue;
                                        $parts = explode(':', $region);
                                        $region_name = trim($parts[0]);
                                        $status_info = trim($parts[1]);
                                        preg_match('/(\w+)\s*\((\d+)/', $status_info, $matches);
                                        $status = strtolower($matches[1] ?? '');
                                        $beneficiaries = $matches[2] ?? 0;
                                    ?>
                                        <div class="flex items-center">
                                            <span class="font-medium text-gray-600 w-32"><?php echo htmlspecialchars($region_name); ?></span>
                                            <span class="px-2 py-1 text-xs rounded-full <?php echo $status_classes[$status] ?? 'bg-gray-100 text-gray-800'; ?>">
                                                <?php echo ucfirst($status); ?> (<?php echo $beneficiaries; ?> beneficiaries)
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <?php if (!empty($scheme['official_link'])): ?>
                                <a href="<?php echo htmlspecialchars($scheme['official_link']); ?>" target="_blank" class="inline-flex items-center text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-external-link-alt mr-1"></i> Official Website
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </main>
</body>
<?php
mysqli_free_result($schemes_result);
?>

</body>

</html>