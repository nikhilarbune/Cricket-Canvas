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
$query = "SELECT p.*, t.tournament_name, tm.team_name 
          FROM payments p
          LEFT JOIN tournaments t ON p.tournament_id = t.tournament_id
          LEFT JOIN teams tm ON p.team_id = tm.team_id";

$where_conditions = [];
$params = [];

if ($status_filter) {
    $where_conditions[] = "p.payment_status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $where_conditions[] = "(t.tournament_name LIKE ? OR tm.team_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($where_conditions)) {
    $query .= " WHERE " . implode(" AND ", $where_conditions);
}

$query .= " ORDER BY p.created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$payments = $stmt->fetchAll();
?>

<div class="p-4 sm:ml-64 mt-14">
    <div class="p-4 border-2 border-gray-200 rounded-lg">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-semibold">Payment Management</h1>
        </div>

        <!-- Filters -->
        <div class="mb-6">
            <form method="GET" class="flex gap-4">
                <input type="text" name="search" placeholder="Search payments..." 
                       value="<?php echo htmlspecialchars($search); ?>"
                       class="p-2 border rounded-lg">
                
                <select name="status" class="p-2 border rounded-lg">
                    <option value="">All Status</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="submitted" <?php echo $status_filter === 'submitted' ? 'selected' : ''; ?>>Submitted</option>
                    <option value="verified" <?php echo $status_filter === 'verified' ? 'selected' : ''; ?>>Verified</option>
                    <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>

                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg">
                    Filter
                </button>
            </form>
        </div>

        <!-- Payments Table -->
        <div class="overflow-x-auto bg-white rounded-lg shadow">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tournament</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Team</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td class="px-6 py-4"><?php echo htmlspecialchars($payment['tournament_name']); ?></td>
                        <td class="px-6 py-4"><?php echo htmlspecialchars($payment['team_name']); ?></td>
                        <td class="px-6 py-4">₹<?php echo number_format($payment['amount'], 2); ?></td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs rounded-full 
                                <?php echo getPaymentStatusColor($payment['payment_status']); ?>">
                                <?php echo ucfirst($payment['payment_status']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <a href="verify.php?id=<?php echo $payment['payment_id']; ?>" 
                               class="text-blue-600 hover:text-blue-900">Verify</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
function getPaymentStatusColor($status) {
    switch ($status) {
        case 'pending':
            return 'bg-yellow-100 text-yellow-800';
        case 'submitted':
            return 'bg-blue-100 text-blue-800';
        case 'verified':
            return 'bg-green-100 text-green-800';
        case 'rejected':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}

require_once '../../includes/admin_footer.php';
?>
