<?php
require_once 'includes/auth.php';

// Only admin can access this page
if (!$_SESSION['isAdmin']) {
    header('Location: schemes.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: schemes.php');
    exit;
}

$scheme_id = intval($_GET['id']);
$error = '';
$success = '';

// Fetch scheme data
$query = "SELECT * FROM beneficiary_schemes WHERE scheme_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'i', $scheme_id);
mysqli_stmt_execute($stmt);

// Retrieve the result
$result = mysqli_stmt_get_result($stmt);

// Fetch the row from the result
$scheme = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);


if (!$scheme) {
    header('Location: schemes.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $scheme_name = trim($_POST['scheme_name']);
    $description = trim($_POST['description']);
    $eligibility = trim($_POST['eligibility']);
    $benefits = trim($_POST['benefits']);
    $official_link = trim($_POST['official_link']);

    if (empty($scheme_name) || empty($description)) {
        $error = 'Scheme name and description are required';
    } else {
        $query = "UPDATE beneficiary_schemes 
                  SET scheme_name = ?, description = ?, eligibility_criteria = ?, benefits = ?, official_link = ?
                  WHERE scheme_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'sssssi', $scheme_name, $description, $eligibility, $benefits, $official_link, $scheme_id);

        if (mysqli_stmt_execute($stmt)) {
            $success = 'Scheme updated successfully!';

            // Log the activity
            $log_query = "INSERT INTO activity_log (user_id, action, created_at) 
                          VALUES (?, 'Updated scheme: $scheme_name', CURRENT_TIMESTAMP)";
            $log_stmt = mysqli_prepare($conn, $log_query);
            mysqli_stmt_bind_param($log_stmt, 'i', $_SESSION['user_id']);
            mysqli_stmt_execute($log_stmt);
            mysqli_stmt_close($log_stmt);
        } else {
            $error = 'Error updating scheme: ' . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
}

?>

<?php include 'header.php'; ?>

<main class="flex-1 p-8">
    <div class="max-w-4xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-green-800">Edit Scheme</h1>
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
                            value="<?php echo htmlspecialchars($scheme['scheme_name']); ?>"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 p-2 border">
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700">Description *</label>
                        <textarea id="description" name="description" rows="3" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 p-2 border"><?php echo htmlspecialchars($scheme['description']); ?></textarea>
                    </div>

                    <div>
                        <label for="eligibility" class="block text-sm font-medium text-gray-700">Eligibility Criteria</label>
                        <textarea id="eligibility" name="eligibility" rows="2"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 p-2 border"><?php echo htmlspecialchars($scheme['eligibility_criteria']); ?></textarea>
                    </div>

                    <div>
                        <label for="benefits" class="block text-sm font-medium text-gray-700">Benefits</label>
                        <textarea id="benefits" name="benefits" rows="2"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 p-2 border"><?php echo htmlspecialchars($scheme['benefits']); ?></textarea>
                    </div>

                    <div>
                        <label for="official_link" class="block text-sm font-medium text-gray-700">Official Website URL</label>
                        <input type="url" id="official_link" name="official_link"
                            value="<?php echo htmlspecialchars($scheme['official_link']); ?>"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring focus:ring-green-200 p-2 border"
                            placeholder="https://">
                    </div>

                    <div class="flex justify-end space-x-4 pt-4">
                        <a href="schemes.php" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            Cancel
                        </a>
                        <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            Update Scheme
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</main>

</body>

</html>