<?php
session_start();
require_once '../../../config/database.php';
require_once '../../../config/config.php';

// Check admin authentication
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}

$payment_id = $_GET['id'] ?? 0;

// Fetch payment details
$stmt = $pdo->prepare("
    SELECT p.*, t.tournament_name, tm.team_name 
    FROM payments p
    LEFT JOIN tournaments t ON p.tournament_id = t.tournament_id
    LEFT JOIN teams tm ON p.team_id = tm.team_id
    WHERE p.payment_id = ?
");
$stmt->execute([$payment_id]);
$payment = $stmt->fetch();

if (!$payment) {
    header("Location: index.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE payments SET payment_status = ? WHERE payment_id = ?");
    $stmt->execute([$status, $payment_id]);
    
    $_SESSION['success'] = "Payment status updated successfully";
    header("Location: index.php");
    exit();
}

require_once '../../includes/admin_header.php';
require_once '../../includes/admin_sidebar.php';
?>

<div class="p-4 sm:ml-64 mt-14">
    <div class="p-4 border-2 border-gray-200 rounded-lg">
        <h1 class="text-2xl font-semibold mb-6">Verify Payment</h1>

        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Payment Details</h2>
            <p><strong>Tournament:</strong> <?php echo htmlspecialchars($payment['tournament_name']); ?></p>
            <p><strong>Team:</strong> <?php echo htmlspecialchars($payment['team_name']); ?></p>
            <p><strong>Amount:</strong> ₹<?php echo number_format($payment['amount'], 2); ?></p>
            <p><strong>Payment Method:</strong> <?php echo ucfirst($payment['payment_method']); ?></p>
            <p><strong>Transaction ID:</strong> <?php echo htmlspecialchars($payment['transaction_id']); ?></p>
            <p><strong>Payment Proof:</strong> <a href="<?php echo BASE_URL . '/uploads/' . $payment['payment_proof']; ?>" target="_blank">View Proof</a></p>
            <p><strong>Status:</strong> <span class="px-2 py-1 text-xs rounded-full <?php echo getPaymentStatusColor($payment['payment_status']); ?>"><?php echo ucfirst($payment['payment_status']); ?></span></p>
        </div>

        <form method="POST" class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">Update Payment Status</h2>
            <div class="mb-4">
                <label class="block text-gray-700 mb-2">Status</label>
                <select name="status" class="p-2 border rounded-lg w-full">
                    <option value="pending" <?php echo $payment['payment_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="submitted" <?php echo $payment['payment_status'] === 'submitted' ? 'selected' : ''; ?>>Submitted</option>
                    <option value="verified" <?php echo $payment['payment_status'] === 'verified' ? 'selected' : ''; ?>>Verified</option>
                    <option value="rejected" <?php echo $payment['payment_status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>
            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-lg">Update Status</button>
        </form>
    </div>
</div>

<?php
require_once '../../includes/admin_footer.php';
?>
