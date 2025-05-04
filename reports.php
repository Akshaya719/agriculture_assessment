<?php
require_once 'includes/auth.php';

if (isset($_GET['download'])) {
    // Generate CSV report
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="land_data_report.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Region', 'Land Size (ha)', 'Irrigation Source', 'Primary Crop', 'Created At']);

    $report_query = "SELECT l.region, l.land_size, l.irrigation_source, l.primary_crop, l.created_at 
                     FROM lands l
                     ORDER BY l.region, l.created_at";
    $report_result = mysqli_query($conn, $report_query) or die("Report query failed: " . mysqli_error($conn));

    while ($row = mysqli_fetch_assoc($report_result)) {
        fputcsv($output, [
            $row['region'],
            $row['land_size'],
            $row['irrigation_source'],
            $row['primary_crop'],
            $row['created_at']
        ]);
    }

    fclose($output);
    mysqli_free_result($report_result);
    exit;
}

// Fetch summary data for display
$summary_query = "SELECT l.region, 
                         COUNT(*) as land_count, 
                         AVG(l.land_size) as avg_land_size, 
                         GROUP_CONCAT(DISTINCT l.irrigation_source) as irrigation_sources
                  FROM lands l
                  GROUP BY l.region";
$summary_result = mysqli_query($conn, $summary_query) or die("Summary query failed: " . mysqli_error($conn));
?>

<?php include 'header.php'; ?>

<body style="background-image: url(images/1.jpg); background-size: cover;background-repeat: no-repeat;">

    <main class="flex-1 p-8">
        <h1 class="text-2xl font-bold text-green-800 mb-6">Reports</h1>

        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-xl font-semibold mb-4">Land Data Summary</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="py-2 px-4 text-left">Region</th>
                            <th class="py-2 px-4 text-left">Number of Lands</th>
                            <th class="py-2 px-4 text-left">Average Land Size (ha)</th>
                            <th class="py-2 px-4 text-left">Irrigation Sources</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($summary = mysqli_fetch_assoc($summary_result)): ?>
                            <tr class="border-b">
                                <td class="py-2 px-4"><?php echo htmlspecialchars($summary['region']); ?></td>
                                <td class="py-2 px-4"><?php echo $summary['land_count']; ?></td>
                                <td class="py-2 px-4"><?php echo number_format($summary['avg_land_size'], 2); ?></td>
                                <td class="py-2 px-4"><?php echo htmlspecialchars($summary['irrigation_sources']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <a href="?download=1" class="inline-block mt-4 bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                Download CSV Report
            </a>
        </div>
    </main>
</body>
    <?php
    mysqli_free_result($summary_result);
    ?>