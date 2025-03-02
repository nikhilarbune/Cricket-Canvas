<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}

$tournament_id = $_GET['tournament_id'] ?? null;
$team_id = $_GET['team_id'] ?? null;

// Get tournament and registration details
$stmt = $pdo->prepare("
    SELECT 
        t.*, tt.status as registration_status,
        tt.payment_status
    FROM tournaments t
    JOIN tournament_teams tt ON t.tournament_id = tt.tournament_id
    WHERE t.tournament_id = ? AND tt.team_id = ?
");
$stmt->execute([$tournament_id, $team_id]);
$registration = $stmt->fetch();

require_once '../../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <h1 class="text-3xl font-bold mb-8">Submit Payment</h1>

        <!-- Tournament Details -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">Payment Details</h2>
            <div class="space-y-4">
                <div>
                    <p class="font-medium">Tournament</p>
                    <p class="text-gray-600"><?php echo htmlspecialchars($registration['tournament_name']); ?></p>
                </div>
                <div>
                    <p class="font-medium">Amount Due</p>
                    <p class="text-gray-600">₹<?php echo number_format($registration['entry_fee'], 2); ?></p>
                </div>
            </div>
        </div>

        <!-- Payment QR Code -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8 text-center">
            <h3 class="text-lg font-semibold mb-4">Scan QR Code to Pay</h3>
            <img src="<?php echo BASE_URL; ?>/uploads/payment_qr/<?php echo $registration['payment_qr']; ?>"
                alt="Payment QR Code"
                class="mx-auto max-w-xs mb-4">
            <p class="text-sm text-gray-600">
                Scan this QR code using any UPI app to make the payment
            </p>
        </div>

        <!-- Payment Submission Form -->
        <form action="<?php echo BASE_URL; ?>/tournaments/payment/process.php"
            method="POST"
            enctype="multipart/form-data"
            class="bg-white rounded-lg shadow-md p-6">

            <input type="hidden" name="tournament_id" value="<?php echo $tournament_id; ?>">
            <input type="hidden" name="team_id" value="<?php echo $team_id; ?>">

            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Transaction ID</label>
                    <input type="text"
                        name="transaction_id"
                        required
                        class="w-full px-3 py-2 border rounded-lg">
                    <p class="text-sm text-gray-500 mt-1">Enter the UPI transaction ID</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Payment Screenshot</label>
                    <input type="file"
                        name="payment_proof"
                        required
                        accept="image/*"
                        class="w-full px-3 py-2 border rounded-lg">
                    <p class="text-sm text-gray-500 mt-1">Upload screenshot of payment confirmation</p>
                </div>
            </div>

            <div class="mt-6">
                <button type="submit"
                    class="w-full bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded-lg">
                    Submit Payment
                </button>
            </div>
        </form>
    </div>
</div>