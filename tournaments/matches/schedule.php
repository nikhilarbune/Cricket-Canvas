<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/config.php';

// Check if user is logged in and is an organizer
if (!isLoggedIn() || getUserRole() != 'organizer') {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}

$tournament_id = $_GET['id'] ?? null;

// Get tournament details
$stmt = $pdo->prepare("
    SELECT t.*, 
           (SELECT COUNT(*) FROM tournament_teams WHERE tournament_id = t.tournament_id AND status = 'approved') as team_count
    FROM tournaments t 
    WHERE t.tournament_id = ? AND t.organizer_id = ?
");
$stmt->execute([$tournament_id, $_SESSION['user_id']]);
$tournament = $stmt->fetch();

// Get approved teams
$stmt = $pdo->prepare("
    SELECT t.* 
    FROM teams t
    JOIN tournament_teams tt ON t.team_id = tt.team_id
    WHERE tt.tournament_id = ? AND tt.status = 'approved'
    ORDER BY t.team_name
");
$stmt->execute([$tournament_id]);
$teams = $stmt->fetchAll();

require_once '../../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <a href="<?php echo BASE_URL; ?>/tournaments/manage.php" 
           class="text-blue-500 hover:text-blue-700">
            ← Back to Tournament Management
        </a>
    </div>

    <h1 class="text-3xl font-bold mb-8">Schedule Matches</h1>

    <!-- Match Creation Form -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4">Create New Match</h2>
        <form action="<?php echo BASE_URL; ?>/tournaments/process/create_match.php" method="POST">
            <input type="hidden" name="tournament_id" value="<?php echo $tournament_id; ?>">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Team 1</label>
                    <select name="team1_id" required class="w-full px-3 py-2 border rounded-lg">
                        <option value="">Select Team</option>
                        <?php foreach ($teams as $team): ?>
                            <option value="<?php echo $team['team_id']; ?>">
                                <?php echo htmlspecialchars($team['team_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Team 2</label>
                    <select name="team2_id" required class="w-full px-3 py-2 border rounded-lg">
                        <option value="">Select Team</option>
                        <?php foreach ($teams as $team): ?>
                            <option value="<?php echo $team['team_id']; ?>">
                                <?php echo htmlspecialchars($team['team_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Match Date & Time</label>
                    <input type="datetime-local" 
                           name="match_date" 
                           required
                           class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Venue</label>
                    <input type="text" 
                           name="venue" 
                           required
                           value="<?php echo htmlspecialchars($tournament['venue']); ?>"
                           class="w-full px-3 py-2 border rounded-lg">
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" 
                        class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg">
                    Schedule Match
                </button>
            </div>
        </form>
    </div>

    <!-- Scheduled Matches -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold mb-4">Scheduled Matches</h2>
        
        <?php
        // Get all scheduled matches for this tournament
        $stmt = $pdo->prepare("
            SELECT 
                m.*,
                t1.team_name as team1_name,
                t2.team_name as team2_name
            FROM matches m
            JOIN teams t1 ON m.team1_id = t1.team_id
            JOIN teams t2 ON m.team2_id = t2.team_id
            WHERE m.tournament_id = ?
            ORDER BY m.match_date ASC
        ");
        $stmt->execute([$tournament_id]);
        $matches = $stmt->fetchAll();
        ?>

        <?php if (empty($matches)): ?>
            <p class="text-gray-500 text-center">No matches scheduled yet.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teams</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Venue</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($matches as $match): ?>
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($match['team1_name']); ?>
                                        <span class="text-gray-500 mx-2">vs</span>
                                        <?php echo htmlspecialchars($match['team2_name']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('M j, Y g:i A', strtotime($match['match_date'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($match['venue']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php echo getMatchStatusColor($match['match_status']); ?>">
                                        <?php echo ucfirst($match['match_status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <?php if ($match['match_status'] === 'scheduled'): ?>
                                        <form action="<?php echo BASE_URL; ?>/tournaments/process/cancel_match.php" 
                                              method="POST" 
                                              class="inline"
                                              onsubmit="return confirm('Are you sure you want to cancel this match?');">
                                            <input type="hidden" name="match_id" value="<?php echo $match['match_id']; ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-900">Cancel</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
function getMatchStatusColor($status) {
    switch ($status) {
        case 'scheduled':
            return 'bg-blue-100 text-blue-800';
        case 'completed':
            return 'bg-green-100 text-green-800';
        case 'cancelled':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}
?>
</rewritten_file>