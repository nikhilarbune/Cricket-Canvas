<?php
session_start();
require_once '../config/database.php';
require_once '../config/config.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}

require_once 'includes/admin_header.php';
require_once 'includes/admin_sidebar.php';

// Fetch dashboard statistics
$stats = array(
    'total_tournaments' => $pdo->query("SELECT COUNT(*) FROM tournaments")->fetchColumn(),
    'active_players' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'player'")->fetchColumn(),
    'total_teams' => $pdo->query("SELECT COUNT(*) FROM teams")->fetchColumn(),
    'pending_payments' => $pdo->query("SELECT COUNT(*) FROM payments WHERE payment_status = 'pending'")->fetchColumn()
);

// Fetch recent tournaments
$stmt = $pdo->query("
    SELECT t.*, u.username as organizer_name 
    FROM tournaments t 
    JOIN users u ON t.organizer_id = u.user_id 
    ORDER BY t.created_at DESC 
    LIMIT 5
");
$recent_tournaments = $stmt->fetchAll();

// Fetch recent payments
$stmt = $pdo->query("
    SELECT p.*, t.tournament_name, tm.team_name 
    FROM payments p
    JOIN tournaments t ON p.tournament_id = t.tournament_id
    JOIN teams tm ON p.team_id = tm.team_id
    ORDER BY p.created_at DESC 
    LIMIT 5
");
$recent_payments = $stmt->fetchAll();
?>

<div class="p-4 sm:ml-64 mt-14">
    <div class="p-4 border-2 border-gray-200 rounded-lg">
        <h1 class="text-2xl font-semibold mb-6">Admin Dashboard</h1>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-lg font-semibold text-gray-600">Total Tournaments</h3>
                <p class="text-3xl font-bold text-blue-600"><?php echo $stats['total_tournaments']; ?></p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-lg font-semibold text-gray-600">Active Players</h3>
                <p class="text-3xl font-bold text-green-600"><?php echo $stats['active_players']; ?></p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-lg font-semibold text-gray-600">Total Teams</h3>
                <p class="text-3xl font-bold text-purple-600"><?php echo $stats['total_teams']; ?></p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-lg font-semibold text-gray-600">Pending Payments</h3>
                <p class="text-3xl font-bold text-red-600"><?php echo $stats['pending_payments']; ?></p>
            </div>
        </div>

        <!-- Recent Activity Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Tournaments -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4">Recent Tournaments</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Organizer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($recent_tournaments as $tournament): ?>
                            <tr>
                                <td class="px-6 py-4"><?php echo htmlspecialchars($tournament['tournament_name']); ?></td>
                                <td class="px-6 py-4"><?php echo htmlspecialchars($tournament['organizer_name']); ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs rounded-full 
                                        <?php echo getStatusColor($tournament['status']); ?>">
                                        <?php echo ucfirst($tournament['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Payments -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4">Recent Payments</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tournament</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Team</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($recent_payments as $payment): ?>
                            <tr>
                                <td class="px-6 py-4"><?php echo htmlspecialchars($payment['tournament_name']); ?></td>
                                <td class="px-6 py-4"><?php echo htmlspecialchars($payment['team_name']); ?></td>
                                <td class="px-6 py-4">₹<?php echo number_format($payment['amount'], 2); ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs rounded-full 
                                        <?php echo getPaymentStatusColor($payment['payment_status']); ?>">
                                        <?php echo ucfirst($payment['payment_status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
function getStatusColor($status) {
    switch ($status) {
        case 'draft':
            return 'bg-gray-100 text-gray-800';
        case 'open':
            return 'bg-green-100 text-green-800';
        case 'registration_closed':
            return 'bg-yellow-100 text-yellow-800';
        case 'ongoing':
            return 'bg-blue-100 text-blue-800';
        case 'completed':
            return 'bg-purple-100 text-purple-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}

function getPaymentStatusColor($status) {
    switch ($status) {
        case 'pending':
            return 'bg-yellow-100 text-yellow-800';
        case 'verified':
            return 'bg-green-100 text-green-800';
        case 'rejected':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}

require_once 'includes/admin_footer.php';
?>
