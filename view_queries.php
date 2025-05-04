<?php
require_once 'includes/auth.php';
require_once 'includes/config.php';

// Admin access only
if (!$_SESSION['isAdmin']) {
    header('Location: dashboard.php');
    exit();
}

// Handle all POST actions (status updates, notes, and deletion)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $query_id = intval($_POST['query_id']);

    if (isset($_POST['update_status'])) {
        // Toggle read status (0/1)
        $new_status = $_POST['status'] == 1 ? 0 : 1;
        $update_query = "UPDATE user_queries SET status = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, 'ii', $new_status, $query_id);
    } elseif (isset($_POST['save_notes'])) {
        // Save admin notes
        $admin_notes = mysqli_real_escape_string($conn, $_POST['admin_notes']);
        $update_query = "UPDATE user_queries SET admin_notes = ?, status = 1 WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, 'si', $admin_notes, $query_id);
    } elseif (isset($_POST['delete_query'])) {
        // Delete query
        $delete_query = "DELETE FROM user_queries WHERE id = ?";
        $stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($stmt, 'i', $query_id);
    }

    if (isset($stmt)) {
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    header("Location: view_queries.php");
    exit();
}

// Fetch all queries with user info
$queries_query = "SELECT q.*, u.full_name, u.email 
                 FROM user_queries q
                 JOIN users u ON q.user_id = u.id
                 ORDER BY q.status ASC, q.created_at DESC";
$queries_result = mysqli_query($conn, $queries_query);
?>

<?php include 'header.php'; ?>
<body style="background-image: url(images/schemes.jpg); background-size: cover;background-repeat: no-repeat;">

<main class="flex-1 p-8">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-2xl font-bold text-green-800 mb-6">User Query Management</h1>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="py-3 px-4 text-left">Status</th>
                            <th class="py-3 px-4 text-left">User</th>
                            <th class="py-3 px-4 text-left">Email</th>
                            <th class="py-3 px-4 text-left">Message</th>
                            <th class="py-3 px-4 text-left">Date</th>
                            <th class="py-3 px-4 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($query = mysqli_fetch_assoc($queries_result)): ?>
                            <tr class="border-b hover:bg-gray-50 <?php echo $query['status'] ? 'bg-gray-50' : 'bg-blue-50'; ?>">
                                <td class="py-3 px-4">
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="query_id" value="<?php echo $query['id']; ?>">
                                        <input type="hidden" name="status" value="<?php echo $query['status']; ?>">
                                        <button type="submit" name="update_status" class="text-xs px-2 py-1 rounded <?php echo $query['status'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo $query['status'] ? 'Read' : 'Unread'; ?>
                                        </button>
                                    </form>
                                </td>
                                <td class="py-3 px-4"><?php echo htmlspecialchars($query['full_name']); ?></td>
                                <td class="py-3 px-4"><?php echo htmlspecialchars($query['email']); ?></td>
                                <td class="py-3 px-4">
                                    <div class="font-medium"><?php echo nl2br(htmlspecialchars($query['query_text'])); ?></div>
                                    <?php if (!empty($query['admin_notes'])): ?>
                                        <div class="text-sm text-gray-600 mt-1">
                                            <strong>Notes:</strong> <?php echo htmlspecialchars($query['admin_notes']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4"><?php echo date('M j, Y g:i a', strtotime($query['created_at'])); ?></td>
                                <td class="py-3 px-4 flex space-x-2">
                                    <button onclick="document.getElementById('notes-modal-<?php echo $query['id']; ?>').showModal()"
                                        class="text-blue-600 hover:text-blue-800 text-sm">
                                        <i class="fas fa-edit mr-1"></i> Notes
                                    </button>

                                    <!-- Delete Form -->
                                    <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this query?');">
                                        <input type="hidden" name="query_id" value="<?php echo $query['id']; ?>">
                                        <button type="submit" name="delete_query" class="text-red-600 hover:text-red-800 text-sm">
                                            <i class="fas fa-trash mr-1"></i> Delete
                                        </button>
                                    </form>

                                    <!-- Notes Modal -->
                                    <dialog id="notes-modal-<?php echo $query['id']; ?>" class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
                                        <form method="POST">
                                            <input type="hidden" name="query_id" value="<?php echo $query['id']; ?>">
                                            <h3 class="font-bold text-lg mb-4">Admin Notes</h3>
                                            <textarea name="admin_notes" class="w-full p-2 border rounded mb-4" rows="4"
                                                placeholder="Enter your notes here..."><?php echo htmlspecialchars($query['admin_notes'] ?? ''); ?></textarea>
                                            <div class="flex justify-end space-x-2">
                                                <button type="button" onclick="document.getElementById('notes-modal-<?php echo $query['id']; ?>').close()"
                                                    class="px-4 py-2 border border-gray-300 rounded">
                                                    Cancel
                                                </button>
                                                <button type="submit" name="save_notes" class="px-4 py-2 bg-green-600 text-white rounded">
                                                    Save Notes
                                                </button>
                                            </div>
                                        </form>
                                    </dialog>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
</body>
<script>
    // Close modals when clicking outside
    document.querySelectorAll('dialog').forEach(dialog => {
        dialog.addEventListener('click', (e) => {
            if (e.target === dialog) {
                dialog.close();
            }
        });
    });
</script>

<?php

mysqli_free_result($queries_result);
?>