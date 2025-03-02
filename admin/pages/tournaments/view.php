<?php
session_start();
require_once '../../../config/database.php';
require_once '../../../config/config.php';

function getRegistrationStatusColor($status) {
    return match ($status) {
        'pending' => 'bg-yellow-100 text-yellow-800',
        'approved' => 'bg-green-100 text-green-800',
        'rejected' => 'bg-red-100 text-red-800',
        default => 'bg-gray-100 text-gray-800'
    };
}

// Check admin authentication
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}

$tournament_id = $_GET['id'] ?? 0;

// Fetch tournament details
$stmt = $pdo->prepare("
    SELECT t.*, u.username as organizer_name 
    FROM tournaments t
    LEFT JOIN users u ON t.organizer_id = u.user_id
    WHERE t.tournament_id = ?
");
$stmt->execute([$tournament_id]);
$tournament = $stmt->fetch();

if (!$tournament) {
    header("Location: index.php");
    exit();
}

// Fetch registered teams
$stmt = $pdo->prepare("
    SELECT t.*, tt.status as registration_status, tt.payment_status,
    u.username as captain_name
    FROM teams t
    JOIN tournament_teams tt ON t.team_id = tt.team_id
    LEFT JOIN users u ON t.captain_id = u.user_id
    WHERE tt.tournament_id = ?
");
$stmt->execute([$tournament_id]);
$teams = $stmt->fetchAll();

require_once '../../includes/admin_header.php';
require_once '../../includes/admin_sidebar.php';
?>

<div class="p-4 sm:ml-64 mt-14">
    <div class="p-4 border-2 border-gray-200 rounded-lg">
        <!-- Tournament Details -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex justify-between items-center mb-4">
                <h1 class="text-2xl font-semibold"><?php echo htmlspecialchars($tournament['tournament_name']); ?></h1>
                <span class="px-3 py-1 rounded-full <?php echo getStatusColor($tournament['status']); ?>">
                    <?php echo ucfirst($tournament['status']); ?>
                </span>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-gray-600">Organizer: <?php echo htmlspecialchars($tournament['organizer_name']); ?></p>
                    <p class="text-gray-600">Start Date: <?php echo date('d M Y', strtotime($tournament['start_date'])); ?></p>
                    <p class="text-gray-600">End Date: <?php echo date('d M Y', strtotime($tournament['end_date'])); ?></p>
                </div>
                <div>
                    <p class="text-gray-600">Format: <?php echo ucfirst($tournament['format']); ?></p>
                    <p class="text-gray-600">Entry Fee: ₹<?php echo number_format($tournament['entry_fee'], 2); ?></p>
                    <p class="text-gray-600">Prize Pool: ₹<?php echo number_format($tournament['prize_pool'], 2); ?></p>
                </div>
            </div>
        </div>

        <!-- Registered Teams -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">Registered Teams</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Team Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Captain</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Registration Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payment Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($teams as $team): ?>
                        <tr>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($team['team_name']); ?></td>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($team['captain_name']); ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded-full 
                                    <?php echo getRegistrationStatusColor($team['registration_status']); ?>">
                                    <?php echo ucfirst($team['registration_status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded-full 
                                    <?php echo getPaymentStatusColor($team['payment_status']); ?>">
                                    <?php echo ucfirst($team['payment_status']); ?>
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
require_once '../../includes/admin_footer.php';
?>
