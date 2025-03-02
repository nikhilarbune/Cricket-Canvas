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

// Handle filters
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Build query based on filters
$query = "SELECT m.*, t.tournament_name, tm1.team_name as team1_name, tm2.team_name as team2_name 
          FROM matches m
          LEFT JOIN tournaments t ON m.tournament_id = t.tournament_id
          LEFT JOIN teams tm1 ON m.team1_id = tm1.team_id
          LEFT JOIN teams tm2 ON m.team2_id = tm2.team_id";

$where_conditions = [];
$params = [];

if ($status_filter) {
    $where_conditions[] = "m.status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $where_conditions[] = "(t.tournament_name LIKE ? OR tm1.team_name LIKE ? OR tm2.team_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($where_conditions)) {
    $query .= " WHERE " . implode(" AND ", $where_conditions);
}

$query .= " ORDER BY m.match_date DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$matches = $stmt->fetchAll();
?>

<div class="p-4 sm:ml-64 mt-14">
    <div class="p-4 border-2 border-gray-200 rounded-lg">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-semibold">Match Management</h1>
        </div>

        <!-- Filters -->
        <div class="mb-6">
            <form method="GET" class="flex gap-4">
                <input type="text" name="search" placeholder="Search matches..." 
                       value="<?php echo htmlspecialchars($search); ?>"
                       class="p-2 border rounded-lg">
                
                <select name="status" class="p-2 border rounded-lg">
                    <option value="">All Status</option>
                    <option value="scheduled" <?php echo $status_filter === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                    <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>

                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg">
                    Filter
                </button>
            </form>
        </div>

        <!-- Matches Table -->
        <div class="overflow-x-auto bg-white rounded-lg shadow">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tournament</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Team 1</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Team 2</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($matches as $match): ?>
                    <tr>
                        <td class="px-6 py-4"><?php echo htmlspecialchars($match['tournament_name']); ?></td>
                        <td class="px-6 py-4"><?php echo htmlspecialchars($match['team1_name']); ?></td>
                        <td class="px-6 py-4"><?php echo htmlspecialchars($match['team2_name']); ?></td>
                        <td class="px-6 py-4"><?php echo date('d M Y, h:i A', strtotime($match['match_date'])); ?></td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs rounded-full 
                                <?php echo getMatchStatusColor($match['status']); ?>">
                                <?php echo ucfirst($match['status']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <a href="view.php?id=<?php echo $match['match_id']; ?>" 
                               class="text-blue-600 hover:text-blue-900">View</a>
                            <a href="update.php?id=<?php echo $match['match_id']; ?>" 
                               class="ml-3 text-green-600 hover:text-green-900">Update</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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
