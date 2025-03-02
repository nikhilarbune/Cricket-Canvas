<?php
session_start();
require_once '../config/database.php';
require_once '../config/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}

// Get tournament ID
$tournament_id = $_GET['id'] ?? null;
if (!$tournament_id) {
    $_SESSION['error'] = "Tournament ID is required";
    header("Location: " . BASE_URL . "/tournaments/list.php");
    exit();
}

// Get tournament details
$stmt = $pdo->prepare("
    SELECT t.*, 
           (SELECT COUNT(*) FROM tournament_teams WHERE tournament_id = t.tournament_id AND status = 'approved') as registered_teams
    FROM tournaments t
    WHERE t.tournament_id = ? AND t.status = 'open'
");
$stmt->execute([$tournament_id]);
$tournament = $stmt->fetch();

if (!$tournament) {
    $_SESSION['error'] = "Tournament not found or registration closed";
    header("Location: " . BASE_URL . "/tournaments/list.php");
    exit();
}

// Get user's teams where they are captain
$stmt = $pdo->prepare("
    SELECT t.*, 
           (SELECT COUNT(*) FROM team_members WHERE team_id = t.team_id AND status = 'approved') as member_count
    FROM teams t
    WHERE t.captain_id = ? AND t.team_status = 'active'
");
$stmt->execute([$_SESSION['user_id']]);
$teams = $stmt->fetchAll();

// Check if any teams are already registered
$registeredTeamIds = [];
if (!empty($teams)) {
    $teamIds = array_column($teams, 'team_id');
    $stmt = $pdo->prepare("
        SELECT team_id 
        FROM tournament_teams 
        WHERE tournament_id = ? AND team_id IN (" . implode(',', $teamIds) . ")
    ");
    $stmt->execute([$tournament_id]);
    $registeredTeamIds = array_column($stmt->fetchAll(), 'team_id');
}

require_once '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <a href="<?php echo BASE_URL; ?>/tournaments/details.php?id=<?php echo $tournament_id; ?>" 
           class="text-blue-500 hover:text-blue-700">
            ← Back to Tournament Details
        </a>
    </div>

    <div class="max-w-3xl mx-auto">
        <h1 class="text-3xl font-bold mb-8">Register for Tournament</h1>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Tournament Summary -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4"><?php echo htmlspecialchars($tournament['tournament_name']); ?></h2>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="font-medium">Entry Fee</p>
                    <p class="text-gray-600">₹<?php echo number_format($tournament['entry_fee'], 2); ?></p>
                </div>
                <div>
                    <p class="font-medium">Registration Deadline</p>
                    <p class="text-gray-600">
                        <?php echo date('F j, Y', strtotime($tournament['registration_deadline'])); ?>
                    </p>
                </div>
                <div>
                    <p class="font-medium">Teams Registered</p>
                    <p class="text-gray-600">
                        <?php echo $tournament['registered_teams']; ?> / <?php echo $tournament['max_teams']; ?>
                    </p>
                </div>
            </div>
        </div>

        <?php if (empty($teams)): ?>
            <div class="bg-yellow-100 text-yellow-800 p-4 rounded-lg">
                <p>You don't have any teams to register. You need to be a team captain to register for tournaments.</p>
                <a href="<?php echo BASE_URL; ?>/teams/create.php" 
                   class="text-blue-500 hover:text-blue-700 font-medium">
                    Create a team
                </a>
            </div>
        <?php else: ?>
            <form action="<?php echo BASE_URL; ?>/tournaments/process/register.php" method="POST" 
                  class="bg-white rounded-lg shadow-md p-6">
                <input type="hidden" name="tournament_id" value="<?php echo $tournament_id; ?>">
                
                <!-- Team Selection -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Team</label>
                    <div class="space-y-4">
                        <?php foreach ($teams as $team): ?>
                            <div class="relative flex items-center">
                                <input type="radio" 
                                       name="team_id" 
                                       value="<?php echo $team['team_id']; ?>"
                                       id="team_<?php echo $team['team_id']; ?>"
                                       <?php echo in_array($team['team_id'], $registeredTeamIds) ? 'disabled' : ''; ?>
                                       class="mr-3"
                                       required>
                                <label for="team_<?php echo $team['team_id']; ?>" 
                                       class="<?php echo in_array($team['team_id'], $registeredTeamIds) ? 'text-gray-400' : ''; ?>">
                                    <span class="font-medium"><?php echo htmlspecialchars($team['team_name']); ?></span>
                                    <span class="text-sm text-gray-500">
                                        (<?php echo $team['member_count']; ?> members)
                                    </span>
                                    <?php if (in_array($team['team_id'], $registeredTeamIds)): ?>
                                        <span class="ml-2 text-sm text-yellow-600">Already registered</span>
                                    <?php endif; ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Payment Information -->
                <?php if ($tournament['entry_fee'] > 0): ?>
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold mb-4">Payment Information</h3>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-gray-600 mb-2">Entry Fee: ₹<?php echo number_format($tournament['entry_fee'], 2); ?></p>
                            <p class="text-sm text-gray-500">Payment will be handled after registration confirmation.</p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Terms and Conditions -->
                <div class="mb-6">
                    <div class="flex items-start">
                        <input type="checkbox" 
                               name="terms_accepted" 
                               id="terms_accepted"
                               required
                               class="mt-1 mr-3">
                        <label for="terms_accepted" class="text-sm text-gray-600">
                            I agree to the tournament rules and confirm that my team meets all eligibility requirements. 
                            I understand that registration is subject to approval and payment of entry fee 
                            <?php if ($tournament['entry_fee'] > 0): ?>
                                of ₹<?php echo number_format($tournament['entry_fee'], 2); ?>
                            <?php endif; ?>.
                        </label>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" 
                            class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold">
                        Submit Registration
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
