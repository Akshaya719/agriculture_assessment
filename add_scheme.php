<?php
require_once 'includes/auth.php';

// Only admin can access this page
if (!$_SESSION['isAdmin']) {
    header('Location: schemes.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $scheme_name = trim($_POST['scheme_name']);
    $description = trim($_POST['description']);
    $eligibility = trim($_POST['eligibility']);
    $benefits = trim($_POST['benefits']);
    $official_link = trim($_POST['official_link']);

    if (empty($scheme_name) || empty($description)) {
        $error = 'Scheme name and description are required';
    } else {
        $query = "INSERT INTO beneficiary_schemes (scheme_name, description, eligibility_criteria, benefits, official_link) 
                  VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'sssss', $scheme_name, $description, $eligibility, $benefits, $official_link);
        
        if (mysqli_stmt_execute($stmt)) {
            $scheme_id = mysqli_insert_id($conn);
            $success = 'Scheme added successfully!';
            
            // Log the activity
            $log_query = "INSERT INTO activity_log (user_id, action, created_at) 
                          VALUES (?, 'Added new scheme: $scheme_name', CURRENT_TIMESTAMP)";
            $log_stmt = mysqli_prepare($conn, $log_query);
            mysqli_stmt_bind_param($log_stmt, 'i', $_SESSION['user_id']);
            mysqli_stmt_execute($log_stmt);
            mysqli_stmt_close($log_stmt);
            
            // Redirect after 2 seconds to view the new scheme
            header('Refresh: 2; URL=schemes.php');
        } else {
            $error = 'Error adding scheme: ' . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
}

// Get all regions for implementation status
$regions_query = "SELECT region_id, region_name FROM regions ORDER BY region_name";
$regions_result = mysqli_query($conn, $regions_query);
?>

<?php include 'header.php'; ?>

<main class="flex-1 p-8">
    <div class="max-w-4xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-green-800">Add New Scheme</h1>
            <a href="schemes.php" class="text-gray-600 hover:text-gray-800">
                <i class="fas fa-arrow-left mr-1"></i> Back to Schemes
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
                <div class="space-y-6">
                    <div>
                        <label for="scheme_name" class="block text-sm font-medium text-gray-700">Scheme Name *</label>
                        <input type="text" id="scheme_name" name="scheme_name" required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 p-2 border">
                    </div>
                    
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700">Description *</label>
                        <textarea id="description" name="description" rows="3" required
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 p-2 border"></textarea>
                    </div>
                    
                    <div>
                        <label for="eligibility" class="block text-sm font-medium text-gray-700">Eligibility Criteria</label>
                        <textarea id="eligibility" name="eligibility" rows="2"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 p-2 border"></textarea>
                    </div>
                    
                    <div>
                        <label for="benefits" class="block text-sm font-medium text-gray-700">Benefits</label>
                        <textarea id="benefits" name="benefits" rows="2"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 p-2 border"></textarea>
                    </div>
                    
                    <div>
                        <label for="official_link" class="block text-sm font-medium text-gray-700">Official Website URL</label>
                        <input type="url" id="official_link" name="official_link"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 p-2 border"
                               placeholder="https://">
                    </div>
                    
                    <!-- Region Implementation Status (could be added in a second step) -->
                    
                    <div class="flex justify-end space-x-4 pt-4">
                        <a href="schemes.php" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            Cancel
                        </a>
                        <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            Save Scheme
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</main>

</body>
</html>