<?php
session_start();
require_once '../../../config/database.php';
require_once '../../../config/config.php';

// Check admin authentication
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}

$match_id = $_GET['id'] ?? 0;

// Fetch match details
$stmt = $pdo->prepare("
    SELECT m.*, t.tournament_name, tm1.team_name as team1_name, tm2.team_name as team2_name 
    FROM matches m
    LEFT JOIN tournaments t ON m.tournament_id = t.tournament_id
    LEFT JOIN teams tm1 ON m.team1_id = tm1.team_id
    LEFT JOIN teams tm2 ON m.team2_id = tm2.team_id
    WHERE m.match_id = ?
");
$stmt->execute([$match_id]);
$match = $stmt->fetch();

if (!$match) {
    header("Location: index.php");
    exit();
}

// Fetch match scores
$stmt = $pdo->prepare("
    SELECT ms.*, tm.team_name 
    FROM match_scores ms
    LEFT JOIN teams tm ON ms.team_id = tm.team_id
    WHERE ms.match_id = ?
");
$stmt->execute([$match_id]);
$scores = $stmt->fetchAll();

require_once '../../includes/admin_header.php';
require_once '../../includes/admin_sidebar.php';
?>

<div class="p-4 sm:ml-64 mt-14">
    <div class="p-4 border-2 border-gray-200 rounded-lg">
        <h1 class="text-2xl font-semibold mb-6">Match Details</h1>

        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Match Information</h2>
            <p><strong>Tournament:</strong> <?php echo htmlspecialchars($match['tournament_name']); ?></p>
            <p><strong>Team 1:</strong> <?php echo htmlspecialchars($match['team1_name']); ?></p>
            <p><strong>Team 2:</strong> <?php echo htmlspecialchars($match['team2_name']); ?></p>
            <p><strong>Date:</strong> <?php echo date('d M Y, h:i A', strtotime($match['match_date'])); ?></p>
            <p><strong>Status:</strong> <span class="px-2 py-1 text-xs rounded-full <?php echo getMatchStatusColor($match['status']); ?>"><?php echo ucfirst($match['status']); ?></span></p>
            <p><strong>Venue:</strong> <?php echo htmlspecialchars($match['venue']); ?></p>
            <p><strong>Round:</strong> <?php echo htmlspecialchars($match['round_number']); ?></p>
            <p><strong>Group:</strong> <?php echo htmlspecialchars($match['group_name']); ?></p>
        </div>

        <!-- Match Scores -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">Match Scores</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Team</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Runs</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Wickets</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Overs</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Extras</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($scores as $score): ?>
                        <tr>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($score['team_name']); ?></td>
                            <td class="px-6 py-4"><?php echo $score['runs']; ?></td>
                            <td class="px-6 py-4"><?php echo $score['wickets']; ?></td>
                            <td class="px-6 py-4"><?php echo $score['overs']; ?></td>
                            <td class="px-6 py-4"><?php echo $score['extras']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
function getMatchStatusColor($status) {
    switch ($status) {
        case 'scheduled':
            return 'bg-yellow-100 text-yellow-800';
        case 'in_progress':
            return 'bg-blue-100 text-blue-800';
        case 'completed':
            return 'bg-green-100 text-green-800';
        case 'cancelled':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}

require_once '../../includes/admin_footer.php';
?>