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
$query = "SELECT t.*, u.username as organizer_name, 
          COUNT(DISTINCT tt.team_id) as team_count
          FROM tournaments t 
          LEFT JOIN users u ON t.organizer_id = u.user_id
          LEFT JOIN tournament_teams tt ON t.tournament_id = tt.tournament_id";

$where_conditions = [];
$params = [];

if ($status_filter) {
    $where_conditions[] = "t.status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $where_conditions[] = "(t.tournament_name LIKE ? OR u.username LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($where_conditions)) {
    $query .= " WHERE " . implode(" AND ", $where_conditions);
}

$query .= " GROUP BY t.tournament_id ORDER BY t.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tournaments = $stmt->fetchAll();
?>

<div class="p-4 sm:ml-64 mt-14">
    <div class="p-4 border-2 border-gray-200 rounded-lg">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-semibold">Tournament Management</h1>
        </div>

        <!-- Filters -->
        <div class="mb-6">
            <form method="GET" class="flex gap-4">
                <input type="text" name="search" placeholder="Search tournaments..." 
                       value="<?php echo htmlspecialchars($search); ?>"
                       class="p-2 border rounded-lg">
                
                <select name="status" class="p-2 border rounded-lg">
                    <option value="">All Status</option>
                    <option value="draft" <?php echo $status_filter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                    <option value="open" <?php echo $status_filter === 'open' ? 'selected' : ''; ?>>Open</option>
                    <option value="ongoing" <?php echo $status_filter === 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                </select>

                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg">
                    Filter
                </button>
            </form>
        </div>

        <!-- Tournaments Table -->
        <div class="overflow-x-auto bg-white rounded-lg shadow">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Organizer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Teams</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($tournaments as $tournament): ?>
                    <tr>
                        <td class="px-6 py-4">
                            <?php echo htmlspecialchars($tournament['tournament_name']); ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php echo htmlspecialchars($tournament['organizer_name']); ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php echo $tournament['team_count']; ?>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs rounded-full 
                                <?php echo getStatusColor($tournament['status']); ?>">
                                <?php echo ucfirst($tournament['status']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <a href="view.php?id=<?php echo $tournament['tournament_id']; ?>" 
                               class="text-blue-600 hover:text-blue-900">View</a>
                            <a href="manage.php?id=<?php echo $tournament['tournament_id']; ?>" 
                               class="ml-3 text-green-600 hover:text-green-900">Manage</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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

require_once '../../includes/admin_footer.php';
?>
