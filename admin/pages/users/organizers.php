<?php
session_start();
require_once '../../../config/database.php';
require_once '../../../config/config.php';

// Check admin authentication
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $user_id = $_POST['user_id'] ?? 0;
    $status = $_POST['status'] ?? '';
    
    if ($status === 'approve' || $status === 'reject') {
        $is_active = $status === 'approve' ? 1 : 0;
        $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE user_id = ? AND role = 'organizer'");
        $stmt->execute([$is_active, $user_id]);
        
        $_SESSION['success'] = "Organizer status updated successfully";
        header("Location: organizers.php");
        exit();
    }
}

// Fetch organizers with their tournament count and latest activity
$query = "
    SELECT u.*, 
           COUNT(DISTINCT t.tournament_id) as tournament_count,
           MAX(t.created_at) as last_activity
    FROM users u
    LEFT JOIN tournaments t ON u.user_id = t.organizer_id
    WHERE u.role = 'organizer'
    GROUP BY u.user_id
    ORDER BY u.created_at DESC
";
$organizers = $pdo->query($query)->fetchAll();

require_once '../../includes/admin_header.php';
require_once '../../includes/admin_sidebar.php';
?>

<div class="p-4 sm:ml-64 mt-14">
    <div class="p-4 border-2 border-gray-200 rounded-lg">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-semibold">Organizer Management</h1>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Organizers Table -->
        <div class="overflow-x-auto bg-white rounded-lg shadow">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Username</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tournaments</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Joined Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($organizers as $organizer): ?>
                    <tr>
                        <td class="px-6 py-4">
                            <?php echo htmlspecialchars($organizer['username']); ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php echo htmlspecialchars($organizer['email']); ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php echo $organizer['tournament_count']; ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php echo date('d M Y', strtotime($organizer['created_at'])); ?>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs rounded-full 
                                <?php echo $organizer['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo $organizer['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <a href="view_organizer.php?id=<?php echo $organizer['user_id']; ?>" 
                               class="text-blue-600 hover:text-blue-900 mr-3">View</a>
                            
                            <form method="POST" class="inline">
                                <input type="hidden" name="user_id" value="<?php echo $organizer['user_id']; ?>">
                                <?php if ($organizer['is_active']): ?>
                                    <button type="submit" name="action" value="reject"
                                            class="text-red-600 hover:text-red-900"
                                            onclick="return confirm('Are you sure you want to reject this organizer?')">
                                        Reject
                                    </button>
                                <?php else: ?>
                                    <button type="submit" name="action" value="approve"
                                            class="text-green-600 hover:text-green-900">
                                        Approve
                                    </button>
                                <?php endif; ?>
                            </form>
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
