<?php
session_start();
require_once '../../../config/database.php';
require_once '../../../config/config.php';

// Check admin authentication
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}

$tournament_id = $_GET['id'] ?? 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $team_id = $_POST['team_id'] ?? 0;
    
    if ($action === 'update_status') {
        $new_status = $_POST['status'];
        $stmt = $pdo->prepare("UPDATE tournaments SET status = ? WHERE tournament_id = ?");
        $stmt->execute([$new_status, $tournament_id]);
    }
    
    if ($action === 'update_team_status') {
        $new_status = $_POST['team_status'];
        $stmt = $pdo->prepare("UPDATE tournament_teams SET status = ? WHERE tournament_id = ? AND team_id = ?");
        $stmt->execute([$new_status, $tournament_id, $team_id]);
    }
    
    header("Location: manage.php?id=" . $tournament_id);
    exit();
}

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

// Fetch pending team registrations
$stmt = $pdo->prepare("
    SELECT t.*, tt.status as registration_status, tt.payment_status,
    u.username as captain_name
    FROM teams t
    JOIN tournament_teams tt ON t.team_id = tt.team_id
    LEFT JOIN users u ON t.captain_id = u.user_id
    WHERE tt.tournament_id = ? AND tt.status = 'pending'
");
$stmt->execute([$tournament_id]);
$pending_teams = $stmt->fetchAll();

require_once '../../includes/admin_header.php';
require_once '../../includes/admin_sidebar.php';
?>

<div class="p-4 sm:ml-64 mt-14">
    <div class="p-4 border-2 border-gray-200 rounded-lg">
        <!-- Tournament Management -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h1 class="text-2xl font-semibold mb-4">Manage Tournament</h1>
            
            <!-- Tournament Status Update -->
            <form method="POST" class="mb-6">
                <input type="hidden" name="action" value="update_status">
                <div class="flex gap-4 items-center">
                    <label class="font-medium">Tournament Status:</label>
                    <select name="status" class="p-2 border rounded-lg">
                        <option value="draft" <?php echo $tournament['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                        <option value="open" <?php echo $tournament['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                        <option value="registration_closed" <?php echo $tournament['status'] === 'registration_closed' ? 'selected' : ''; ?>>Registration Closed</option>
                        <option value="ongoing" <?php echo $tournament['status'] === 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                        <option value="completed" <?php echo $tournament['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg">
                        Update Status
                    </button>
                </div>
            </form>
        </div>

        <!-- Pending Team Registrations -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">Pending Team Registrations</h2>
            <?php if (empty($pending_teams)): ?>
                <p class="text-gray-500">No pending team registrations</p>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Team Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Captain</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payment Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($pending_teams as $team): ?>
                        <tr>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($team['team_name']); ?></td>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($team['captain_name']); ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded-full 
                                    <?php echo getPaymentStatusColor($team['payment_status']); ?>">
                                    <?php echo ucfirst($team['payment_status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <form method="POST" class="inline">
                                    <input type="hidden" name="action" value="update_team_status">
                                    <input type="hidden" name="team_id" value="<?php echo $team['team_id']; ?>">
                                    <button type="submit" name="team_status" value="approved" 
                                            class="text-green-600 hover:text-green-900 mr-3">
                                        Approve
                                    </button>
                                    <button type="submit" name="team_status" value="rejected"
                                            class="text-red-600 hover:text-red-900">
                                        Reject
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
require_once '../../includes/admin_footer.php';
?>
