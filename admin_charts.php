<?php
require_once 'includes/auth.php';
    // if (!isAdmin()) {
    //     header("Location: dash.php");
    //     exit;
    // }

$success_message = '';
$error_message = '';

// Fetch cropping patterns data
$cropping_query = "
    SELECT r.region_id, r.region_name, c.crop_id, c.crop_name, cp.area_covered
    FROM cropping_patterns cp
    JOIN regions r ON cp.region_id = r.region_id
    JOIN crops c ON cp.crop_id = c.crop_id
    ORDER BY r.region_name, c.crop_name";
$cropping_result = mysqli_query($conn, $cropping_query);

$cropping_data = [];
while ($row = mysqli_fetch_assoc($cropping_result)) {
    $cropping_data[] = $row;
}
mysqli_free_result($cropping_result);

// Fetch well depth data
$well_query = "
    SELECT r.region_id, r.region_name, rw.well_type, rw.average_depth
    FROM regional_wells rw
    JOIN regions r ON rw.region_id = r.region_id
    ORDER BY r.region_name, rw.well_type";
$well_result = mysqli_query($conn, $well_query);

$well_data = [];
while ($row = mysqli_fetch_assoc($well_result)) {
    $well_data[] = $row;
}
mysqli_free_result($well_result);

// Fetch regions and crops for dropdowns
$regions_query = "SELECT region_id, region_name FROM regions ORDER BY region_name";
$regions_result = mysqli_query($conn, $regions_query);

$crops_query = "SELECT crop_id, crop_name FROM crops ORDER BY crop_name";
$crops_result = mysqli_query($conn, $crops_query);

// Prepare data for Chart.js
$cropping_labels = [];
$cropping_datasets = [];
$well_labels = [];
$well_datasets = [];

// Process cropping patterns
$regions = array_unique(array_column($cropping_data, 'region_name'));
$crops = array_unique(array_column($cropping_data, 'crop_name'));
foreach ($crops as $crop) {
    $data = [];
    foreach ($regions as $region) {
        $area = 0;
        foreach ($cropping_data as $row) {
            if ($row['region_name'] === $region && $row['crop_name'] === $crop) {
                $area = $row['area_covered'];
                break;
            }
        }
        $data[] = $area;
    }
    $cropping_datasets[] = [
        'label' => $crop,
        'data' => $data,
        'backgroundColor' => sprintf('rgba(%d, %d, %d, 0.6)', rand(0, 255), rand(0, 255), rand(0, 255)),
    ];
}
$cropping_labels = $regions;

// Process well depths
$well_types = array_unique(array_column($well_data, 'well_type'));
foreach ($well_types as $well_type) {
    $data = [];
    foreach ($regions as $region) {
        $depth = 0;
        foreach ($well_data as $row) {
            if ($row['region_name'] === $region && $row['well_type'] === $well_type) {
                $depth = $row['average_depth'];
                break;
            }
        }
        $data[] = $depth;
    }
    $well_datasets[] = [
        'label' => $well_type,
        'data' => $data,
        'backgroundColor' => sprintf('rgba(%d, %d, %d, 0.6)', rand(0, 255), rand(0, 255), rand(0, 255)),
    ];
}
$well_labels = $regions;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_data'])) {
    $data_type = filter_input(INPUT_POST, 'data_type', FILTER_SANITIZE_STRING);
    $region_id = filter_input(INPUT_POST, 'region_id', FILTER_VALIDATE_INT);
    $crop_id = filter_input(INPUT_POST, 'crop_id', FILTER_VALIDATE_INT);
    $area_covered = filter_input(INPUT_POST, 'area_covered', FILTER_VALIDATE_FLOAT);
    $well_type = filter_input(INPUT_POST, 'well_type', FILTER_SANITIZE_STRING);
    $average_depth = filter_input(INPUT_POST, 'average_depth', FILTER_VALIDATE_FLOAT);

    if ($data_type === 'cropping' && $region_id && $crop_id && $area_covered !== null && $area_covered > 0) {
        $query = "INSERT INTO cropping_patterns (region_id, crop_id, area_covered, season) VALUES (?, ?, ?, 'Kharif')";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'iid', $region_id, $crop_id, $area_covered);
        if (mysqli_stmt_execute($stmt)) {
            $success_message = 'Cropping pattern added successfully!';
        } else {
            $error_message = 'Error adding cropping pattern: ' . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    } elseif ($data_type === 'well' && $region_id && $well_type && $average_depth !== null && $average_depth > 0) {
        $query = "INSERT INTO regional_wells (region_id, well_type, count, average_depth) VALUES (?, ?, 1, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'isd', $region_id, $well_type, $average_depth);
        if (mysqli_stmt_execute($stmt)) {
            $success_message = 'Well data added successfully!';
        } else {
            $error_message = 'Error adding well data: ' . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    } else {
        $error_message = 'Invalid input data.';
    }

    // Refresh data after submission
    if (!$error_message) {
        header("Location: admin_charts.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Charts | AgriData</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-50">
    <?php include 'header.php'; ?>

    <main class="container mx-auto px-4 py-6">
        <div class="text-center mb-6">
            <h1 class="text-3xl font-bold text-green-800">
                <i class="fas fa-chart-bar mr-2"></i>Admin Data Insights
            </h1>
            <p class="text-lg text-gray-600">Cultivating Tomorrow: Your Land, Our Vision!</p>
        </div>

        <?php if ($success_message): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Add Data Button and Form -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <button id="toggleFormBtn" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded mb-4">
                <i class="fas fa-plus mr-2"></i>Add Data
            </button>
            <div id="addDataForm" class="mt-4 hidden">
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="add_data" value="1">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Data Type</label>
                        <select name="data_type" id="dataType" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                            <option value="cropping">Cropping Patterns</option>
                            <option value="well">Well Depth</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Region</label>
                        <select name="region_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                            <option value="">Select Region</option>
                            <?php while ($region = mysqli_fetch_assoc($regions_result)): ?>
                                <option value="<?php echo htmlspecialchars($region['region_id']); ?>">
                                    <?php echo htmlspecialchars($region['region_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div id="cropField" class="hidden">
                        <label class="block text-sm font-medium text-gray-700">Crop</label>
                        <select name="crop_id" id="cropSelect" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                            <option value="">Select Crop</option>
                            <?php
                            mysqli_data_seek($crops_result, 0); // Reset pointer
                            while ($crop = mysqli_fetch_assoc($crops_result)): ?>
                                <option value="<?php echo htmlspecialchars($crop['crop_id']); ?>">
                                    <?php echo htmlspecialchars($crop['crop_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div id="wellField" class="hidden">
                        <label class="block text-sm font-medium text-gray-700">Well Type</label>
                        <input type="text" name="well_type" id="wellType" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500" placeholder="e.g., Tube Well">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Value</label>
                        <input type="number" step="0.01" name="area_covered" id="areaInput" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500" placeholder="Area Covered (ha) or Average Depth (m)">
                    </div>
                    <div>
                        <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                            <i class="fas fa-save mr-2"></i>Save Data
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Cropping Patterns Chart -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-green-800 mb-4">
                    <i class="fas fa-seedling mr-2"></i>Cropping Patterns by Region
                </h2>
                <canvas id="croppingChart" height="200"></canvas>
            </div>

            <!-- Well Depth Chart -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-green-800 mb-4">
                    <i class="fas fa-tint mr-2"></i>Average Well Depth by Region
                </h2>
                <canvas id="wellDepthChart" height="200"></canvas>
            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>
    <?php
    mysqli_free_result($regions_result);
    mysqli_free_result($crops_result);
    ?>

    <script>
        // Toggle form visibility
        const toggleFormBtn = document.getElementById('toggleFormBtn');
        const addDataForm = document.getElementById('addDataForm');

        toggleFormBtn.addEventListener('click', function() {
            addDataForm.classList.toggle('hidden');
        });

        // Show/hide relevant fields based on data type and manage required attributes
        const dataTypeSelect = document.getElementById('dataType');
        const cropField = document.getElementById('cropField');
        const wellField = document.getElementById('wellField');
        const cropSelect = document.getElementById('cropSelect');
        const wellTypeInput = document.getElementById('wellType');
        const areaInput = document.getElementById('areaInput');

        dataTypeSelect.addEventListener('change', function() {
            if (this.value === 'cropping') {
                cropField.classList.remove('hidden');
                wellField.classList.add('hidden');
                cropSelect.setAttribute('required', 'required');
                wellTypeInput.removeAttribute('required');
                areaInput.placeholder = 'Area Covered (ha)';
            } else if (this.value === 'well') {
                cropField.classList.add('hidden');
                wellField.classList.remove('hidden');
                cropSelect.removeAttribute('required');
                wellTypeInput.setAttribute('required', 'required');
                areaInput.placeholder = 'Average Depth (m)';
            }
        });

        // Initialize form state on page load
        if (dataTypeSelect.value === 'cropping') {
            cropField.classList.remove('hidden');
            wellField.classList.add('hidden');
            cropSelect.setAttribute('required', 'required');
            wellTypeInput.removeAttribute('required');
            areaInput.placeholder = 'Area Covered (ha)';
        }

        // Cropping Patterns Chart
        const croppingCtx = document.getElementById('croppingChart').getContext('2d');
        new Chart(croppingCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($cropping_labels); ?>,
                datasets: <?php echo json_encode($cropping_datasets); ?>
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Region'
                        },
                        stacked: true
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Area Covered (ha)'
                        },
                        stacked: true,
                        beginAtZero: true,
                        max: 1.0
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            boxWidth: 20
                        }
                    }
                }
            }
        });

        // Well Depth Chart
        const wellDepthCtx = document.getElementById('wellDepthChart').getContext('2d');
        new Chart(wellDepthCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($well_labels); ?>,
                datasets: <?php echo json_encode($well_datasets); ?>
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Region'
                        },
                        stacked: true
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Average Depth (m)'
                        },
                        stacked: true,
                        beginAtZero: true,
                        max: 1.0
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            boxWidth: 20
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>