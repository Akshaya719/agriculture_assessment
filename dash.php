<?php
require_once 'includes/auth.php';
require_once 'includes/config.php';

// Fetch farmer-specific data
$user_id = $_SESSION['user_id'];
$region = $_SESSION['region'];

// 1. Get quick stats
$stats_query = "SELECT 
    SUM(l.land_size) as total_land,
    COUNT(DISTINCT s.scheme_id) as active_schemes
    FROM lands l
    LEFT JOIN regions r ON l.region = r.region_name
    LEFT JOIN region_schemes rs ON r.region_id = rs.region_id
    LEFT JOIN beneficiary_schemes s ON rs.scheme_id = s.scheme_id
    WHERE l.user_id = ?";
$stmt = mysqli_prepare($conn, $stats_query);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$stats_result = mysqli_stmt_get_result($stmt);
$stats = mysqli_fetch_assoc($stats_result) ?: ['total_land' => 0, 'active_schemes' => 0];
mysqli_stmt_close($stmt);
mysqli_free_result($stats_result);

// 2. Get farmer's lands
$lands_query = "SELECT * FROM lands WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $lands_query);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$lands = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

// 3. Get scheme alerts
$schemes_query = "SELECT s.scheme_name, rs.implementation_status
                 FROM region_schemes rs
                 JOIN beneficiary_schemes s ON rs.scheme_id = s.scheme_id
                 JOIN regions r ON rs.region_id = r.region_id
                 WHERE r.region_name = ?
                 LIMIT 2";
$stmt = mysqli_prepare($conn, $schemes_query);
mysqli_stmt_bind_param($stmt, 's', $region);
mysqli_stmt_execute($stmt);
$schemes = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmer Dashboard | AgriData</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .weather-icon {
            width: 50px;
            height: 50px;
        }
    </style>
</head>

<body class="bg-gray-50">
    <?php include 'header.php'; ?>

    <main class="container mx-auto px-4 py-6">
        <!-- Welcome Banner -->
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6">
            <h1 class="text-2xl font-bold">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h1>
            <p>Your regional dashboard for <?php echo htmlspecialchars($region); ?></p>
        </div>

        <!-- Dashboard Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Quick Stats -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold mb-4 text-green-800">
                        <i class="fas fa-chart-line mr-2"></i>Your Quick Stats
                    </h2>
                    <div class="grid grid-cols-3 gap-4">
                        <div class="text-center p-4 bg-blue-50 rounded-lg">
                            <div class="text-3xl font-bold text-blue-600"><?php echo number_format($stats['total_land'], 2); ?> ha</div>
                            <div class="text-sm text-gray-600">Total Land</div>
                        </div>
                        <div class="text-center p-4 bg-green-50 rounded-lg">
                            <div class="text-3xl font-bold text-green-600"><?php echo $stats['active_schemes']; ?></div>
                            <div class="text-sm text-gray-600">Active Schemes</div>
                        </div>
                        <div class="text-center p-4 bg-yellow-50 rounded-lg">
                            <div class="text-3xl font-bold text-yellow-600">3.2t</div>
                            <div class="text-sm text-gray-600">Avg Yield</div>
                        </div>
                    </div>
                </div>

                <!-- My Lands -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-semibold text-green-800">
                            <i class="fas fa-tractor mr-2"></i>My Lands
                        </h2>
                        <a href="add_land.php" class="text-sm bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded">
                            <i class="fas fa-plus mr-1"></i>Add Land
                        </a>
                        <a href="view_lands.php" class="text-sm bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded">
                            View All Lands
                        </a>
                    </div>

                    <div class="space-y-3">
                        <?php if (mysqli_num_rows($lands) > 0): ?>
                            <?php while ($land = mysqli_fetch_assoc($lands)): ?>
                                <div class="border rounded-lg p-4 hover:bg-gray-50">
                                    <div class="flex justify-between">
                                        <div>
                                            <h3 class="font-medium"><?php echo htmlspecialchars($land['primary_crop']); ?> Field</h3>
                                            <p class="text-sm text-gray-600">
                                                <?php echo htmlspecialchars($land['land_size']); ?> ha •
                                                <?php echo htmlspecialchars($land['irrigation_source']); ?>
                                            </p>
                                        </div>
                                        <span class="px-2 py-1 text-xs rounded mt-3 bg-green-100 text-green-800">
                                            Active
                                        </span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center py-4 text-gray-500">
                                <p>No lands registered yet</p>
                                <a href="add_land.php" class="text-green-600 hover:underline">Add your first land</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-6">
                <!-- Scheme Alerts -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold mb-4 text-green-800">
                        <i class="fas fa-calendar-check mr-2"></i>Scheme Alerts
                    </h2>
                    <div class="space-y-3">
                        <?php if (mysqli_num_rows($schemes) > 0): ?>
                            <?php while ($scheme = mysqli_fetch_assoc($schemes)): ?>
                                <div class="p-3 bg-yellow-50 rounded-lg">
                                    <h3 class="font-medium"><?php echo htmlspecialchars($scheme['scheme_name']); ?></h3>
                                    <div class="text-sm mt-1">
                                        <span class="font-medium"><?php echo ucfirst($scheme['implementation_status']); ?></span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-gray-500">active schemes</p>
                        <?php endif; ?>
                        <a href="schemes.php" class="block text-center text-sm text-green-600 hover:underline mt-2">
                            View all schemes →
                        </a>
                    </div>
                </div>

                <!-- Weather Forecast -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold mb-4 text-green-800">
                        <i class="fas fa-cloud-sun mr-2"></i>Weather Forecast
                    </h2>
                    <div class="weather-container">
                        <div class="flex items-center mb-4">
                            <input type="text" id="weatherCity" placeholder="Enter city name"
                                class="flex-1 border rounded-l px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500"
                                value="<?php echo htmlspecialchars($region); ?>">
                            <button id="getWeatherBtn" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-r">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>

                        <!-- Current Weather -->
                        <div id="currentWeather" class="bg-gradient-to-r from-blue-50 to-blue-100 rounded-lg p-4 mb-4 hidden">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-xl font-bold" id="cityName"></h3>
                                    <div class="text-4xl font-bold my-2 flex items-center" id="temperature">
                                        <span id="tempValue"></span>
                                        <span class="text-lg ml-1">°C</span>
                                    </div>
                                    <div class="text-gray-600 capitalize" id="description"></div>
                                    <div class="flex items-center mt-2 text-sm text-gray-600">
                                        <i class="fas fa-tint mr-1"></i>
                                        <span id="humidity"></span>%
                                        <i class="fas fa-wind ml-3 mr-1"></i>
                                        <span id="windSpeed"></span> km/h
                                    </div>
                                </div>
                                <div class="text-right">
                                    <img id="weatherIcon" src="" alt="Weather icon" class="w-24 h-24">
                                    <div class="text-sm text-gray-600" id="currentDate"></div>
                                </div>
                            </div>
                        </div>

                        <!-- 3-Day Forecast -->
                        <div id="forecastContainer" class="grid grid-cols-1 sm:grid-cols-3 gap-3 hidden">
                            <!-- Forecast days will be inserted here by JavaScript -->
                        </div>

                        <!-- Loading and Error States -->
                        <div id="weatherLoading" class="text-center py-4 hidden">
                            <i class="fas fa-spinner fa-spin text-green-600 text-2xl"></i>
                            <p class="mt-2">Loading weather data...</p>
                        </div>
                        <div id="weatherError" class="text-center py-4 text-red-600 hidden"></div>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <?php include 'footer.php'; ?>


    <script>
        // Weather API functionality
        const apiKey = 'ee8dea3f8fcdab151ab577683fc35bbf';
        const getWeatherBtn = document.getElementById('getWeatherBtn');
        const weatherCity = document.getElementById('weatherCity');
        const currentWeather = document.getElementById('currentWeather');
        const forecastContainer = document.getElementById('forecastContainer');
        const weatherLoading = document.getElementById('weatherLoading');
        const weatherError = document.getElementById('weatherError');

        // Function to get weather icon class based on OpenWeatherMap icon code
        function getWeatherIconClass(iconCode) {
            const iconMap = {
                '01d': 'fas fa-sun text-yellow-400',
                '01n': 'fas fa-moon text-blue-300',
                '02d': 'fas fa-cloud-sun text-yellow-300',
                '02n': 'fas fa-cloud-moon text-blue-200',
                '03d': 'fas fa-cloud text-gray-400',
                '03n': 'fas fa-cloud text-gray-400',
                '04d': 'fas fa-cloud-meatball text-gray-500',
                '04n': 'fas fa-cloud-meatball text-gray-500',
                '09d': 'fas fa-cloud-rain text-blue-400',
                '09n': 'fas fa-cloud-rain text-blue-400',
                '10d': 'fas fa-cloud-showers-heavy text-blue-500',
                '10n': 'fas fa-cloud-showers-heavy text-blue-500',
                '11d': 'fas fa-bolt text-yellow-500',
                '11n': 'fas fa-bolt text-yellow-500',
                '13d': 'far fa-snowflake text-blue-200',
                '13n': 'far fa-snowflake text-blue-200',
                '50d': 'fas fa-smog text-gray-400',
                '50n': 'fas fa-smog text-gray-400'
            };
            return iconMap[iconCode] || 'fas fa-question-circle';
        }

        // Function to format date
        function formatDate(timestamp) {
            const date = new Date(timestamp * 1000);
            return date.toLocaleDateString('en-US', {
                weekday: 'long',
                month: 'short',
                day: 'numeric'
            });
        }

        // Function to fetch weather data
        async function fetchWeather(city) {
            weatherLoading.classList.remove('hidden');
            currentWeather.classList.add('hidden');
            forecastContainer.classList.add('hidden');
            weatherError.classList.add('hidden');

            try {
                // Fetch current weather
                const currentResponse = await fetch(
                    `https://api.openweathermap.org/data/2.5/weather?q=${city}&appid=${apiKey}&units=metric`
                );
                const currentData = await currentResponse.json();

                if (currentData.cod !== 200) {
                    throw new Error(currentData.message || 'City not found');
                }

                // Fetch forecast
                const forecastResponse = await fetch(
                    `https://api.openweathermap.org/data/2.5/forecast?q=${city}&appid=${apiKey}&units=metric`
                );
                const forecastData = await forecastResponse.json();

                if (forecastData.cod !== '200') {
                    throw new Error(forecastData.message || 'Forecast not available');
                }

                // Display current weather
                document.getElementById('cityName').textContent = `${currentData.name}, ${currentData.sys.country}`;
                document.getElementById('tempValue').textContent = Math.round(currentData.main.temp);
                document.getElementById('description').textContent = currentData.weather[0].description;
                document.getElementById('humidity').textContent = currentData.main.humidity;
                document.getElementById('windSpeed').textContent = Math.round(currentData.wind.speed * 3.6); // Convert m/s to km/h
                document.getElementById('weatherIcon').src =
                    `https://openweathermap.org/img/wn/${currentData.weather[0].icon}@4x.png`;
                document.getElementById('currentDate').textContent = formatDate(currentData.dt);

                currentWeather.classList.remove('hidden');

                // Process and display forecast
                const forecastList = forecastData.list;
                let dayMap = {};

                // Get forecast for noon (12:00) of each day for better representation
                forecastList.forEach(item => {
                    const date = item.dt_txt.split(' ')[0];
                    const time = item.dt_txt.split(' ')[1];
                    if (time.includes('12:00:00') && !dayMap[date] && Object.keys(dayMap).length < 3) {
                        dayMap[date] = item;
                    }
                });

                // If we didn't get noon data, fallback to first available time for each day
                if (Object.keys(dayMap).length < 3) {
                    forecastList.forEach(item => {
                        const date = item.dt_txt.split(' ')[0];
                        if (!dayMap[date] && Object.keys(dayMap).length < 3) {
                            dayMap[date] = item;
                        }
                    });
                }

                // Generate forecast HTML
                let forecastHTML = '';
                Object.values(dayMap).forEach(day => {
                    const date = new Date(day.dt * 1000);
                    const dayName = date.toLocaleDateString('en-US', {
                        weekday: 'short'
                    });
                    const temp = Math.round(day.main.temp);
                    const iconClass = getWeatherIconClass(day.weather[0].icon);
                    const condition = day.weather[0].main;
                    const humidity = day.main.humidity;
                    const windSpeed = Math.round(day.wind.speed * 3.6);

                    forecastHTML += `
                    <div class="bg-gradient-to-b from-blue-50 to-blue-100 rounded-lg p-4 hover:shadow-md transition-shadow">
                        <div class="text-center font-medium text-blue-800">${dayName}</div>
                        <div class="flex justify-center my-3">
                            <i class="${iconClass} text-4xl"></i>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold">${temp}°C</div>
                            <div class="text-sm text-gray-600 capitalize">${condition}</div>
                        </div>
                        <div class="flex justify-between mt-3 text-xs text-gray-600">
                            <div class="flex items-center">
                                <i class="fas fa-tint mr-1"></i>
                                ${humidity}%
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-wind mr-1"></i>
                                ${windSpeed} km/h
                            </div>
                        </div>
                    </div>
                `;
                });

                forecastContainer.innerHTML = forecastHTML;
                forecastContainer.classList.remove('hidden');

            } catch (error) {
                weatherError.textContent = error.message;
                weatherError.classList.remove('hidden');
            } finally {
                weatherLoading.classList.add('hidden');
            }
        }

        // Event listeners
        getWeatherBtn.addEventListener('click', () => {
            const city = weatherCity.value.trim();
            if (city) {
                fetchWeather(city);
            } else {
                weatherError.textContent = 'Please enter a city name';
                weatherError.classList.remove('hidden');
            }
        });

        // Load weather for user's region by default
        document.addEventListener('DOMContentLoaded', () => {
            const defaultCity = weatherCity.value.trim();
            if (defaultCity) {
                fetchWeather(defaultCity);
            }
        });
    </script>
</body>

</html>
<?php
mysqli_free_result($lands);
mysqli_free_result($schemes);
