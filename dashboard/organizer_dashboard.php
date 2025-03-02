<?php
session_start();
require_once '../config/database.php';
require_once '../config/config.php';

// Check if user is logged in and is an organizer
if (!isLoggedIn() || getUserRole() != 'organizer') {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}

// After all checks and potential redirects, include the header
require_once '../includes/header.php';

// Get organizer's information
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$organizer = $stmt->fetch();

// Get organizer's tournaments
$stmt = $pdo->prepare("SELECT * FROM tournaments WHERE organizer_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$tournaments = $stmt->fetchAll();
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">Organizer Dashboard</h1>
    
    <!-- Welcome Section -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-2xl font-semibold mb-4">Welcome, <?php echo htmlspecialchars($organizer['username']); ?>!</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-blue-50 p-4 rounded-lg">
                <h3 class="font-semibold mb-2">Active Tournaments</h3>
                <p class="text-2xl"><?php echo count($tournaments); ?></p>
            </div>
            <!-- Add more stats as needed -->
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <a href="<?php echo BASE_URL; ?>/tournaments/create.php" 
           class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow">
            <h3 class="font-semibold mb-2">Create Tournament</h3>
            <p class="text-gray-600">Start a new cricket tournament</p>
        </a>
        <a href="<?php echo BASE_URL; ?>/tournaments/manage.php" 
           class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow">
            <h3 class="font-semibold mb-2">Manage Tournaments</h3>
            <p class="text-gray-600">View and manage your tournaments</p>
        </a>
        <a href="<?php echo BASE_URL; ?>/matches/schedule.php" 
           class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow">
            <h3 class="font-semibold mb-2">Schedule Matches</h3>
            <p class="text-gray-600">Create and manage match schedules</p>
        </a>
    </div>

    <!-- Recent Activity -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-semibold mb-4">Recent Activity</h2>
        <!-- Add recent activity content -->
    </div>
</div> 