<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize variables safely
$username = $_SESSION['full_name'] ?? '';
$email = $_SESSION['email'] ?? '';
$initial = isset($_SESSION['full_name']) ? strtoupper(substr($_SESSION['full_name'], 0, 1)) : '';
$isAdmin = $_SESSION['isAdmin'] ?? false;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <!-- ... existing head content ... -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body>
    <header class="bg-green-800 text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <!-- Logo and Title -->
            <div class="flex items-center space-x-2">
                <a href="dash.php">
                    <i class="fas fa-tractor text-xl block"></i>
                    <h1 class="text-xl font-bold">AgriLand</h1>
                </a>
            </div>

            <!-- Navigation Links -->
            <nav class="flex items-center space-x-6">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- Logged-in User -->
                    <a href="dashboard.php" class="hover:text-green-300 text-white">Dashboard</a>
                    <?php if ($isAdmin): ?>
                        <a href="data-entry.php" class="hover:text-green-300 text-white">Data Entry</a>
                        <!-- <a href="admin.php" class="hover:text-green-300 text-white">Admin</a> -->
                    <?php endif; ?>

                    <a href="regions.php" class="hover:text-green-300 text-white">Regional Data</a>
                    <a href="schemes.php" class="hover:text-green-300 text-white">Beneficiary Schemes</a>
                    <a href="reports.php" class="hover:text-green-300 text-white">Reports</a>
                    <?php if (!($isAdmin)): ?>

                        <a href="contact_us.php" class="hover:text-green-300 text-white">Contact Us</a>
                    <?php endif; ?>
                    <?php if (($isAdmin)): ?>

                        <a href="view_queries.php" class="hover:text-green-300 text-white">Feedback</a>
                    <?php endif; ?>
                    <!-- Profile Dropdown -->
                    <div class="relative inline-block text-left">
                        <button type="button" id="menu-button" class="inline-flex items-center gap-2 rounded-full px-4 py-2 border-2 border-white bg-black text-white hover:bg-gray-900 transition-colors">
                            <span class="flex items-center justify-center w-8 h-8 rounded-full bg-[#c2185b] text-white font-bold">
                                <?php echo htmlspecialchars($initial); ?>
                            </span>
                            <span class="text-sm"><?php echo htmlspecialchars($username); ?></span>
                            <svg class="-mr-1 size-5 text-gray-300" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        <div id="profile-dropdown" class="absolute right-0 mt-2 w-56 origin-top-right rounded-md bg-gray-900 shadow-lg ring-1 ring-gray-700 hidden">
                            <div class="py-2 px-4 text-gray-300">
                                <div class="text-sm"><?php echo htmlspecialchars($username); ?></div>
                                <div class="text-xs text-gray-400 mt-1"><?php echo htmlspecialchars($email); ?></div>
                                <div class="border-t border-gray-700 my-2"></div>
                                <a href="profile.php" class="block w-full px-4 py-2 text-left text-sm text-white hover:bg-gray-800 rounded"><i class="fas fa-user"></i>
                                    Profile</a>
                                <a href="logout.php" class="block w-full px-4 py-2 text-left text-sm text-white hover:bg-gray-800 rounded"><i class="fas fa-sign-out-alt"></i>
                                    Logout</a>
                            </div>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- Not Logged In -->
                    <a href="index.php" class="hover:text-green-300 text-white">Login</a>
                    <a href="signup.php" class="bg-blue-600 px-4 py-2 rounded hover:bg-blue-700 text-white">Sign Up</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <script>
        // Improved dropdown script with accessibility
        document.addEventListener('DOMContentLoaded', function() {
            const menuButton = document.getElementById('menu-button');
            const dropdown = document.getElementById('profile-dropdown');

            if (menuButton && dropdown) {
                menuButton.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const isExpanded = this.getAttribute('aria-expanded') === 'true';
                    dropdown.classList.toggle('hidden');
                    this.setAttribute('aria-expanded', !isExpanded);
                });

                // Close when clicking outside
                document.addEventListener('click', function() {
                    dropdown.classList.add('hidden');
                    menuButton.setAttribute('aria-expanded', 'false');
                });

                // Keyboard navigation support
                menuButton.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        dropdown.classList.add('hidden');
                        this.setAttribute('aria-expanded', 'false');
                    }
                });
            }
        });
    </script>