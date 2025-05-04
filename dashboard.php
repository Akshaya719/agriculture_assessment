<?php
require_once 'includes/auth.php';
require_once 'includes/config.php';

// Fetch data for Land Size by Region (Bar Chart)
$land_query = "SELECT r.region_name, SUM(l.land_size) as total_land
               FROM lands l
               JOIN regions r ON l.region = r.region_name
               GROUP BY r.region_name";
$land_result = mysqli_query($conn, $land_query);
$land_data = [];
$land_labels = [];
while ($row = mysqli_fetch_assoc($land_result)) {
    $land_labels[] = $row['region_name'];
    $land_data[] = (float)$row['total_land'];
}
mysqli_free_result($land_result);

// Fetch data for Activity Log (Line Chart, last 7 days)
$start_date = date('Y-m-d', strtotime('-7 days'));
$activity_query = "SELECT DATE(created_at) as activity_date, COUNT(*) as activity_count
                  FROM activity_log
                  WHERE created_at >= ?
                  GROUP BY DATE(created_at)
                  ORDER BY activity_date";
$stmt = mysqli_prepare($conn, $activity_query);
mysqli_stmt_bind_param($stmt, 's', $start_date);
mysqli_stmt_execute($stmt);
$activity_result = mysqli_stmt_get_result($stmt);
$activity_data = [];
$activity_labels = [];
while ($row = mysqli_fetch_assoc($activity_result)) {
    $activity_labels[] = date('M d', strtotime($row['activity_date']));
    $activity_data[] = (int)$row['activity_count'];
}
mysqli_stmt_close($stmt);

// Fetch data for Beneficiaries by Scheme Status (Pie Chart)
$schemes_query = "SELECT implementation_status, SUM(beneficiaries_count) as total_beneficiaries
                 FROM region_schemes
                 GROUP BY implementation_status";
$schemes_result = mysqli_query($conn, $schemes_query);
$schemes_data = [];
$schemes_labels = [];
while ($row = mysqli_fetch_assoc($schemes_result)) {
    $schemes_labels[] = ucfirst($row['implementation_status']);
    $schemes_data[] = (int)$row['total_beneficiaries'];
}
mysqli_free_result($schemes_result);

// Fetch data for Irrigation Sources (Pie Chart)
$irrigation_query = "SELECT i.source_name, SUM(ri.area_irrigated) as total_area
                    FROM region_irrigation ri
                    JOIN irrigation_sources i ON ri.source_id = i.source_id
                    GROUP BY i.source_name";
$irrigation_result = mysqli_query($conn, $irrigation_query);
$irrigation_data = [];
$irrigation_labels = [];
while ($row = mysqli_fetch_assoc($irrigation_result)) {
    $irrigation_labels[] = $row['source_name'];
    $irrigation_data[] = (float)$row['total_area'];
}
mysqli_free_result($irrigation_result);

// Fetch data for Cropping Patterns (Bar Chart)
$cropping_query = "SELECT r.region_name, c.crop_name, cp.area_covered
                   FROM cropping_patterns cp
                   JOIN regions r ON cp.region_id = r.region_id
                   JOIN crops c ON cp.crop_id = c.crop_id
                   ORDER BY r.region_name, c.crop_name";
$cropping_result = mysqli_query($conn, $cropping_query);
$cropping_data = [];
$cropping_labels = [];
$cropping_datasets = [];
$regions = [];
$crops = [];
while ($row = mysqli_fetch_assoc($cropping_result)) {
    $region = $row['region_name'];
    $crop = $row['crop_name'];
    $area = (float)$row['area_covered'];

    if (!in_array($region, $regions)) {
        $regions[] = $region;
    }
    if (!in_array($crop, $cropping_labels)) {
        $cropping_labels[] = $crop;
    }
    $cropping_data[$region][$crop] = $area;
}
mysqli_free_result($cropping_result);

// Prepare datasets for cropping patterns (one dataset per region)
$colors = ['#4CAF50', '#FF9800', '#2196F3']; // Colors for regions
foreach ($regions as $index => $region) {
    $data = [];
    foreach ($cropping_labels as $crop) {
        $data[] = isset($cropping_data[$region][$crop]) ? $cropping_data[$region][$crop] : 0;
    }
    $cropping_datasets[] = [
        'label' => $region,
        'data' => $data,
        'backgroundColor' => $colors[$index % count($colors)],
        'borderColor' => $colors[$index % count($colors)],
        'borderWidth' => 1
    ];
}

// Fetch data for Well Depths (Bar Chart)
$wells_query = "SELECT r.region_name, rw.well_type, rw.average_depth
                FROM regional_wells rw
                JOIN regions r ON rw.region_id = r.region_id
                ORDER BY r.region_name, rw.well_type";
$wells_result = mysqli_query($conn, $wells_query);
$wells_data = [];
$wells_labels = [];
$wells_datasets = [];
$well_regions = [];
$well_types = [];
while ($row = mysqli_fetch_assoc($wells_result)) {
    $region = $row['region_name'];
    $well_type = $row['well_type'];
    $depth = (float)$row['average_depth'];

    if (!in_array($region, $well_regions)) {
        $well_regions[] = $region;
    }
    if (!in_array($well_type, $wells_labels)) {
        $wells_labels[] = $well_type;
    }
    $wells_data[$region][$well_type] = $depth;
}
mysqli_free_result($wells_result);

// Prepare datasets for well depths (one dataset per region)
foreach ($well_regions as $index => $region) {
    $data = [];
    foreach ($wells_labels as $well_type) {
        $data[] = isset($wells_data[$region][$well_type]) ? $wells_data[$region][$well_type] : 0;
    }
    $wells_datasets[] = [
        'label' => $region,
        'data' => $data,
        'backgroundColor' => $colors[$index % count($colors)],
        'borderColor' => $colors[$index % count($colors)],
        'borderWidth' => 1
    ];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | AgriData</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-50">
    <?php include 'header.php'; ?>

    <main class="container mx-auto px-4 py-6">
        <h1 class="text-2xl font-bold text-green-800 mb-6">
            <i class="fas fa-tachometer-alt mr-2"></i>Agricultural Insights Dashboard
        </h1>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Land Size by Region (Bar Chart) -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-green-800 mb-4">
                    <i class="fas fa-globe mr-2"></i>Land Size by Region
                </h2>
                <canvas id="landChart" height="150"></canvas>
            </div>

            <!-- Activity Log (Line Chart) -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-green-800 mb-4">
                    <i class="fas fa-chart-line mr-2"></i>Data Entry Activity (Last 7 Days)
                </h2>
                <canvas id="activityChart" height="150"></canvas>
            </div>

            <!-- Beneficiaries by Scheme Status (Pie Chart) -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-green-800 mb-4">
                    <i class="fas fa-users mr-2"></i>Beneficiaries by Scheme Status
                </h2>
                <canvas id="schemesChart" height="150"></canvas>
            </div>

            <!-- Irrigation Sources (Pie Chart) -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-green-800 mb-4">
                    <i class="fas fa-water mr-2"></i>Irrigation Sources Distribution
                </h2>
                <canvas id="irrigationChart" height="150"></canvas>
            </div>


          
        </div>
    </main>

    <?php include 'footer.php'; ?>

    <script>
        // Land Size by Region (Bar Chart)
        const landCtx = document.getElementById('landChart').getContext('2d');
        new Chart(landCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($land_labels); ?>,
                datasets: [{
                    label: 'Land Size (ha)',
                    data: <?php echo json_encode($land_data); ?>,
                    backgroundColor: 'rgba(76, 175, 80, 0.6)',
                    borderColor: 'rgba(76, 175, 80, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Land Size (ha)'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Region'
                        }
                    }
                }
            }
        });

        // Activity Log (Line Chart)
        const activityCtx = document.getElementById('activityChart').getContext('2d');
        new Chart(activityCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($activity_labels); ?>,
                datasets: [{
                    label: 'Activity Count',
                    data: <?php echo json_encode($activity_data); ?>,
                    borderColor: 'rgba(33, 150, 243, 1)',
                    backgroundColor: 'rgba(33, 150, 243, 0.2)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Activity Count'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    }
                }
            }
        });

        // Beneficiaries by Scheme Status (Pie Chart)
        const schemesCtx = document.getElementById('schemesChart').getContext('2d');
        new Chart(schemesCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($schemes_labels); ?>,
                datasets: [{
                    label: 'Beneficiaries',
                    data: <?php echo json_encode($schemes_data); ?>,
                    backgroundColor: ['#4CAF50', '#FF9800', '#F44336'],
                    borderColor: ['#388E3C', '#F57C00', '#D32F2F'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    title: {
                        display: true,
                        text: 'Beneficiaries by Scheme Status'
                    }
                }
            }
        });

        // Irrigation Sources (Pie Chart)
        const irrigationCtx = document.getElementById('irrigationChart').getContext('2d');
        new Chart(irrigationCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode($irrigation_labels); ?>,
                datasets: [{
                    label: 'Irrigated Area (ha)',
                    data: <?php echo json_encode($irrigation_data); ?>,
                    backgroundColor: ['#4CAF50', '#FF9800', '#2196F3', '#F44336', '#9C27B0'],
                    borderColor: ['#388E3C', '#F57C00', '#1976D2', '#D32F2F', '#7B1FA2'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    title: {
                        display: true,
                        text: 'Irrigation Sources Distribution'
                    }
                }
            }
        });
</script>
</body>

</html>