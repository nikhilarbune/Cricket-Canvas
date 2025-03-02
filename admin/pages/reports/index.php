<?php
session_start();
require_once '../../../config/database.php';
require_once '../../../config/config.php';

// Check admin authentication
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}

require_once '../../includes/admin_header.php';
require_once '../../includes/admin_sidebar.php';
?>

<div class="p-4 sm:ml-64 mt-14">
    <div class="p-4 border-2 border-gray-200 rounded-lg">
        <h1 class="text-2xl font-semibold mb-6">Reports Dashboard</h1>

        <!-- Reports Links -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
            <a href="tournament_report.php" class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow">
                <h3 class="text-xl font-semibold mb-2">Tournament Report</h3>
                <p class="text-gray-600">View detailed statistics of all tournaments</p>
            </a>
            
            <a href="payment_report.php" class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow">
                <h3 class="text-xl font-semibold mb-2">Payment Report</h3>
                <p class="text-gray-600">View detailed statistics of all payments</p>
            </a>
            
            <a href="user_activity.php" class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow">
                <h3 class="text-xl font-semibold mb-2">User Activity Report</h3>
                <p class="text-gray-600">View detailed statistics of user activities</p>
            </a>
        </div>
    </div>
</div>

<?php
require_once '../../includes/admin_footer.php';
?>
