<?php
session_start();
require_once '../../../config/database.php';
require_once '../../../config/config.php';

// Check admin authentication
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}

// Get user stats
$stats = [
    'total_players' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'player'")->fetchColumn(),
    'active_players' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'player' AND is_active = 1")->fetchColumn(),
    'total_organizers' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'organizer'")->fetchColumn(),
    'pending_organizers' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'organizer' AND is_active = 0")->fetchColumn()
];

// Fetch recent user registrations
$stmt = $pdo->query("
    SELECT u.*, 
           COUNT(DISTINCT tm.team_id) as team_count,
           COUNT(DISTINCT t.tournament_id) as tournament_count
    FROM users u
    LEFT JOIN team_members tm ON u.user_id = tm.user_id
    LEFT JOIN tournaments t ON u.user_id = t.organizer_id
    WHERE u.role IN ('player', 'organizer')
    GROUP BY u.user_id
    ORDER BY u.created_at DESC
    LIMIT 10
");
$recent_users = $stmt->fetchAll();

require_once '../../includes/admin_header.php';
require_once '../../includes/admin_sidebar.php';
?>

<div class="p-4 sm:ml-64 mt-14">
    <div class="p-4 border-2 border-gray-200 rounded-lg">
        <h1 class="text-2xl font-semibold mb-6">User Management Dashboard</h1>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-lg font-semibold text-gray-600">Total Players</h3>
                <p class="text-3xl font-bold text-blue-600"><?php echo $stats['total_players']; ?></p>
                <p class="text-sm text-gray-500"><?php echo $stats['active_players']; ?> active</p>
            </div>
            
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-lg font-semibold text-gray-600">Total Organizers</h3>
                <p class="text-3xl font-bold text-green-600"><?php echo $stats['total_organizers']; ?></p>
                <p class="text-sm text-gray-500"><?php echo $stats['pending_organizers']; ?> pending approval</p>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
            <a href="players.php" class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow">
                <h3 class="text-xl font-semibold mb-2">Manage Players</h3>
                <p class="text-gray-600">View and manage player accounts, teams, and participation</p>
            </a>
            
            <a href="organizers.php" class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow">
                <h3 class="text-xl font-semibold mb-2">Manage Organizers</h3>
                <p class="text-gray-600">Review and approve tournament organizers</p>
            </a>
        </div>

        <!-- Recent Users -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">Recent User Registrations</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Username</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Activity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($recent_users as $user): ?>
                        <tr>
                            <td class="px-6 py-4">
                                <?php echo htmlspecialchars($user['username']); ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded-full 
                                    <?php echo $user['role'] === 'player' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($user['role'] === 'player'): ?>
                                    <?php echo $user['team_count']; ?> teams
                                <?php else: ?>
                                    <?php echo $user['tournament_count']; ?> tournaments
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded-full 
                                    <?php echo $user['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <a href="<?php echo $user['role']; ?>s.php" 
                                   class="text-blue-600 hover:text-blue-900">
                                    Manage
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../../includes/admin_footer.php';
?>
