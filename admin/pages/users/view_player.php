<?php
session_start();
require_once '../../../config/database.php';
require_once '../../../config/config.php';

// Check admin authentication
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}

$user_id = $_GET['id'] ?? 0;

// Fetch player details
$stmt = $pdo->prepare("
    SELECT * FROM users 
    WHERE user_id = ? AND role = 'player'
");
$stmt->execute([$user_id]);
$player = $stmt->fetch();

if (!$player) {
    header("Location: players.php");
    exit();
}

// Fetch player's teams
$stmt = $pdo->prepare("
    SELECT t.*, tm.status as membership_status
    FROM teams t
    JOIN team_members tm ON t.team_id = tm.team_id
    WHERE tm.user_id = ?
");
$stmt->execute([$user_id]);
$teams = $stmt->fetchAll();

// Fetch tournament participation
$stmt = $pdo->prepare("
    SELECT DISTINCT t.tournament_name, t.start_date, t.status
    FROM tournaments t
    JOIN tournament_teams tt ON t.tournament_id = tt.tournament_id
    JOIN team_members tm ON tt.team_id = tm.team_id
    WHERE tm.user_id = ?
    ORDER BY t.start_date DESC
");
$stmt->execute([$user_id]);
$tournaments = $stmt->fetchAll();

require_once '../../includes/admin_header.php';
require_once '../../includes/admin_sidebar.php';
?>

<div class="p-4 sm:ml-64 mt-14">
    <div class="p-4 border-2 border-gray-200 rounded-lg">
        <!-- Player Profile -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-semibold">Player Profile</h1>
                <span class="px-3 py-1 rounded-full <?php echo $player['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo $player['is_active'] ? 'Active' : 'Blocked'; ?>
                </span>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-gray-600">Username: <?php echo htmlspecialchars($player['username']); ?></p>
                    <p class="text-gray-600">Email: <?php echo htmlspecialchars($player['email']); ?></p>
                    <p class="text-gray-600">Joined: <?php echo date('d M Y', strtotime($player['created_at'])); ?></p>
                </div>
            </div>
        </div>

        <!-- Teams Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Teams</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Team Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($teams as $team): ?>
                        <tr>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($team['team_name']); ?></td>
                            <td class="px-6 py-4">
                                <?php echo $team['captain_id'] == $user_id ? 'Captain' : 'Player'; ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded-full 
                                    <?php echo getMembershipStatusColor($team['membership_status']); ?>">
                                    <?php echo ucfirst($team['membership_status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tournaments Section -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">Tournament History</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tournament</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($tournaments as $tournament): ?>
                        <tr>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($tournament['tournament_name']); ?></td>
                            <td class="px-6 py-4"><?php echo date('d M Y', strtotime($tournament['start_date'])); ?></td>
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
    </div>
</div>

<?php
function getMembershipStatusColor($status) {
    switch ($status) {
        case 'pending':
            return 'bg-yellow-100 text-yellow-800';
        case 'approved':
            return 'bg-green-100 text-green-800';
        case 'rejected':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}

require_once '../../includes/admin_footer.php';
?>
