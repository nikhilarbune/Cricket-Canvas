<?php
session_start();
require_once '../config/database.php';
require_once '../config/config.php';

// Check if user is logged in and is an organizer
if (!isLoggedIn() || getUserRole() != 'organizer') {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}

// Get tournament ID
$tournament_id = $_GET['id'] ?? null;
if (!$tournament_id) {
    $_SESSION['error'] = "Tournament ID is required";
    header("Location: " . BASE_URL . "/tournaments/manage.php");
    exit();
}

// Verify tournament belongs to this organizer
$stmt = $pdo->prepare("
    SELECT * FROM tournaments 
    WHERE tournament_id = ? AND organizer_id = ?
");
$stmt->execute([$tournament_id, $_SESSION['user_id']]);
$tournament = $stmt->fetch();

if (!$tournament) {
    $_SESSION['error'] = "Tournament not found or access denied";
    header("Location: " . BASE_URL . "/tournaments/manage.php");
    exit();
}

// Get registrations with team and captain info
$status_filter = $_GET['status'] ?? 'all';
$query = "
    SELECT 
        tt.*,
        t.team_name,
        t.captain_id,
        u.username as captain_name,
        u.email as captain_email,
        (SELECT COUNT(*) FROM team_members WHERE team_id = t.team_id AND status = 'approved') as member_count,
        tt.payment_status,
        tt.payment_proof,
        tt.transaction_id,
        tt.registration_date
    FROM tournament_teams tt
    JOIN teams t ON tt.team_id = t.team_id
    JOIN users u ON t.captain_id = u.user_id
    WHERE tt.tournament_id = ?
";

if ($status_filter !== 'all') {
    $query .= " AND tt.status = ?";
}
$query .= " ORDER BY tt.registration_date DESC";

$params = [$tournament_id];
if ($status_filter !== 'all') {
    $params[] = $status_filter;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$registrations = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <a href="<?php echo BASE_URL; ?>/tournaments/manage.php"
            class="text-blue-500 hover:text-blue-700">
            ← Back to Tournament Management
        </a>
    </div>

    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold">Tournament Registrations</h1>
            <p class="text-gray-600 mt-2">
                <?php echo htmlspecialchars($tournament['tournament_name']); ?>
            </p>
        </div>
        <div class="text-sm text-gray-600">
            <p>Min Teams: <?php echo $tournament['min_teams']; ?></p>
            <p>Max Teams: <?php echo $tournament['max_teams']; ?></p>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
            <?php
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <form method="GET" class="flex gap-4 items-end">
            <input type="hidden" name="id" value="<?php echo $tournament_id; ?>">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="status" class="border rounded-lg px-3 py-2" onchange="this.form.submit()">
                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Registrations</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending Review</option>
                    <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>
        </form>
    </div>

    <!-- Registrations List -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <?php if (empty($registrations)): ?>
            <div class="p-6 text-center text-gray-500">
                No registrations found.
            </div>
        <?php else: ?>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Team</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Captain</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Members</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($registrations as $reg): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($reg['team_name']); ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    Registered: <?php echo date('M j, Y', strtotime($reg['registration_date'])); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    <?php echo htmlspecialchars($reg['captain_name']); ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <?php echo htmlspecialchars($reg['captain_email']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $reg['member_count']; ?> members
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php echo getStatusColor($reg['status']); ?>">
                                    <?php echo ucfirst($reg['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($tournament['entry_fee'] > 0): ?>
                                    <?php if ($reg['payment_proof']): ?>
                                        <div class="flex items-center">
                                            <span class="text-green-600">Payment Submitted</span>
                                            <a href="<?php echo BASE_URL; ?>/uploads/payment_proofs/<?php echo $reg['payment_proof']; ?>" 
                                               target="_blank"
                                               class="ml-2 text-blue-600 hover:text-blue-900">
                                                View Proof
                                            </a>
                                        </div>
                                        <?php if ($reg['transaction_id']): ?>
                                            <div class="text-sm text-gray-500 mt-1">
                                                ID: <?php echo htmlspecialchars($reg['transaction_id']); ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-yellow-600">Payment Pending</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-gray-500">Free Entry</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <?php if ($reg['status'] === 'pending'): ?>
                                    <?php if ($tournament['entry_fee'] > 0 && !$reg['payment_proof']): ?>
                                        <span class="text-yellow-600">Awaiting Payment</span>
                                    <?php else: ?>
                                        <form action="<?php echo BASE_URL; ?>/tournaments/process/update_registration.php" 
                                              method="POST" 
                                              class="inline-flex space-x-2">
                                            <input type="hidden" name="tournament_id" value="<?php echo $tournament_id; ?>">
                                            <input type="hidden" name="team_id" value="<?php echo $reg['team_id']; ?>">
                                            <button type="submit" 
                                                    name="action" 
                                                    value="approve"
                                                    class="text-green-600 hover:text-green-900">
                                                Approve
                                            </button>
                                            <button type="submit" 
                                                    name="action" 
                                                    value="reject"
                                                    class="text-red-600 hover:text-red-900">
                                                Reject
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="<?php echo $reg['status'] === 'approved' ? 'text-green-600' : 'text-red-600'; ?>">
                                        <?php echo ucfirst($reg['status']); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Payment Details Modal -->
<div id="paymentModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Payment Details</h3>
            <button onclick="closePaymentModal()" class="text-gray-600 hover:text-gray-900">×</button>
        </div>
        <div id="paymentDetails" class="space-y-4">
            <!-- Payment details will be inserted here -->
        </div>
        <div class="mt-6 flex justify-end space-x-4">
            <button onclick="closePaymentModal()" 
                    class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">
                Close
            </button>
            <form id="verifyPaymentForm" action="<?php echo BASE_URL; ?>/tournaments/process/verify_payment.php" method="POST">
                <input type="hidden" name="tournament_id" id="verify_tournament_id">
                <input type="hidden" name="team_id" id="verify_team_id">
                <button type="submit" 
                        class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600">
                    Verify Payment
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Add JavaScript for modal handling -->
<script>
function viewPaymentDetails(registration) {
    const modal = document.getElementById('paymentModal');
    const details = document.getElementById('paymentDetails');
    
    details.innerHTML = `
        <div>
            <p class="font-medium">Team</p>
            <p class="text-gray-600">${registration.team_name}</p>
        </div>
        <div>
            <p class="font-medium">Transaction ID</p>
            <p class="text-gray-600">${registration.transaction_id}</p>
        </div>
        <div>
            <p class="font-medium">Payment Date</p>
            <p class="text-gray-600">${new Date(registration.payment_date).toLocaleDateString()}</p>
        </div>
        ${registration.payment_proof ? `
            <div>
                <p class="font-medium">Payment Proof</p>
                <a href="${BASE_URL}/uploads/payment_proofs/${registration.payment_proof}" 
                   target="_blank"
                   class="text-blue-500 hover:text-blue-700">
                    View Screenshot
                </a>
            </div>
        ` : ''}
    `;

    document.getElementById('verify_tournament_id').value = registration.tournament_id;
    document.getElementById('verify_team_id').value = registration.team_id;
    
    modal.classList.remove('hidden');
}

function closePaymentModal() {
    document.getElementById('paymentModal').classList.add('hidden');
}
</script>

<?php
function getStatusColor($status)
{
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

function getPaymentStatusColor($status)
{
    switch ($status) {
        case 'pending':
            return 'bg-yellow-100 text-yellow-800';
        case 'submitted':
            return 'bg-green-100 text-green-800';
        case 'rejected':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}
?>