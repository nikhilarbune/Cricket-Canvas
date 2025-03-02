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
$role_filter = $_GET['role'] ?? '';
$search = $_GET['search'] ?? '';

// Build query based on filters
$query = "SELECT u.*, 
                 COUNT(DISTINCT tm.team_id) as team_count,
                 COUNT(DISTINCT t.tournament_id) as tournament_count
          FROM users u
          LEFT JOIN team_members tm ON u.user_id = tm.user_id
          LEFT JOIN tournaments t ON u.user_id = t.organizer_id";

$where_conditions = [];
$params = [];

if ($role_filter) {
    $where_conditions[] = "u.role = ?";
    $params[] = $role_filter;
}

if ($search) {
    $where_conditions[] = "(u.username LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($where_conditions)) {
    $query .= " WHERE " . implode(" AND ", $where_conditions);
}

$query .= " GROUP BY u.user_id ORDER BY u.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>

<div class="p-4 sm:ml-64 mt-14">
    <div class="p-4 border-2 border-gray-200 rounded-lg">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-semibold">User Activity Report</h1>
        </div>

        <!-- Filters -->
        <div class="mb-6">
            <form method="GET" class="flex gap-4">
                <input type="text" name="search" placeholder="Search users..." 
                       value="<?php echo htmlspecialchars($search); ?>"
                       class="p-2 border rounded-lg">
                
                <select name="role" class="p-2 border rounded-lg">
                    <option value="">All Roles</option>
                    <option value="player" <?php echo $role_filter === 'player' ? 'selected' : ''; ?>>Player</option>
                    <option value="organizer" <?php echo $role_filter === 'organizer' ? 'selected' : ''; ?>>Organizer</option>
                </select>

                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg">
                    Filter
                </button>
            </form>
        </div>

        <!-- Users Table -->
        <div class="overflow-x-auto bg-white rounded-lg shadow">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Username</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Teams</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tournaments</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Joined Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td class="px-6 py-4"><?php echo htmlspecialchars($user['username']); ?></td>
                        <td class="px-6 py-4"><?php echo htmlspecialchars($user['email']); ?></td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs rounded-full 
                                <?php echo $user['role'] === 'player' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'; ?>">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4"><?php echo $user['team_count']; ?></td>
                        <td class="px-6 py-4"><?php echo $user['tournament_count']; ?></td>
                        <td class="px-6 py-4"><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs rounded-full 
                                <?php echo $user['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
require_once '../../includes/admin_footer.php';
?>
