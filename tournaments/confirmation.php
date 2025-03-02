<?php
session_start();
require_once '../config/database.php';
require_once '../config/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}

$tournament_id = $_GET['tournament_id'] ?? null;
$team_id = $_GET['team_id'] ?? null;

// Get registration details
$stmt = $pdo->prepare("
    SELECT 
        tt.*,
        t.tournament_name,
        t.entry_fee,
        tm.team_name
    FROM tournament_teams tt
    JOIN tournaments t ON tt.tournament_id = t.tournament_id
    JOIN teams tm ON tt.team_id = tm.team_id
    WHERE tt.tournament_id = ? AND tt.team_id = ?
");
$stmt->execute([$tournament_id, $team_id]);
$registration = $stmt->fetch();

require_once '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow-md p-8 text-center">
            <!-- For Free Tournaments -->
            <?php if ($registration['entry_fee'] == 0): ?>
                <div class="mb-6">
                    <svg class="w-16 h-16 text-green-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold mb-4">Registration Submitted Successfully!</h1>
                <p class="text-gray-600 mb-8">
                    Your team <span class="font-semibold"><?php echo htmlspecialchars($registration['team_name']); ?></span>
                    has been registered for
                    <span class="font-semibold"><?php echo htmlspecialchars($registration['tournament_name']); ?></span>.
                    <br>
                    Please wait for organizer approval.
                </p>
            <?php else: ?>
                <!-- For Paid Tournaments -->
                <div class="mb-6">
                    <svg class="w-16 h-16 text-yellow-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold mb-4">One More Step!</h1>
                <p class="text-gray-600 mb-4">
                    Your registration requires payment to be completed.
                </p>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-8">
                    <p class="text-yellow-800">
                        Entry Fee: ₹<?php echo number_format($registration['entry_fee'], 2); ?>
                    </p>
                </div>
                <a href="<?php echo BASE_URL; ?>/tournaments/payment/submit.php?tournament_id=<?php echo $tournament_id; ?>&team_id=<?php echo $team_id; ?>" 
                   class="inline-block bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold">
                    Proceed to Payment
                </a>
            <?php endif; ?>

            <div class="mt-8 pt-6 border-t">
                <a href="<?php echo BASE_URL; ?>/dashboard/player_dashboard.php" 
                   class="text-blue-500 hover:text-blue-700">
                    Return to Dashboard
                </a>
            </div>
        </div>
    </div>
</div>