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

// Get tournament details with organizer info
$stmt = $pdo->prepare("
    SELECT t.*, 
           u.username as organizer_name,
           (SELECT COUNT(*) FROM tournament_teams WHERE tournament_id = t.tournament_id AND status = 'approved') as registered_teams
    FROM tournaments t
    JOIN users u ON t.organizer_id = u.user_id
    WHERE t.tournament_id = ?
");
$stmt->execute([$tournament_id]);
$tournament = $stmt->fetch();

if (!$tournament) {
    $_SESSION['error'] = "Tournament not found";
    header("Location: " . BASE_URL . "/tournaments/list.php");
    exit();
}

// Get registered teams
$stmt = $pdo->prepare("
    SELECT t.*, u.username as captain_name
    FROM teams t
    JOIN tournament_teams tt ON t.team_id = tt.team_id
    JOIN users u ON t.captain_id = u.user_id
    WHERE tt.tournament_id = ? AND tt.status = 'approved'
    ORDER BY tt.registration_date ASC
");
$stmt->execute([$tournament_id]);
$registered_teams = $stmt->fetchAll();

// Check if user is a team captain
$stmt = $pdo->prepare("SELECT team_id FROM teams WHERE captain_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$captainTeamIds = array_column($stmt->fetchAll(), 'team_id');

// Check if user's team is already registered
$canRegister = false;
if (!empty($captainTeamIds)) {
    $stmt = $pdo->prepare("
        SELECT * FROM tournament_teams 
        WHERE tournament_id = ? AND team_id IN (" . implode(',', $captainTeamIds) . ")
    ");
    $stmt->execute([$tournament_id]);
    $existingRegistration = $stmt->fetch();
    $canRegister = !$existingRegistration && $tournament['status'] === 'open';
}

require_once '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <a href="<?php echo BASE_URL; ?>/tournaments/list.php" 
           class="text-blue-500 hover:text-blue-700">
            ← Back to Tournaments
        </a>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
            <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

    <!-- Tournament Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-3xl font-bold mb-4">
                    <?php echo htmlspecialchars($tournament['tournament_name']); ?>
                </h1>
                <div class="space-y-2 text-gray-600">
                    <p>
                        <span class="font-medium">Organized by:</span>
                        <?php echo htmlspecialchars($tournament['organizer_name']); ?>
                    </p>
                    <p>
                        <span class="font-medium">Status:</span>
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            <?php echo getStatusColor($tournament['status']); ?>">
                            <?php echo ucfirst($tournament['status']); ?>
                        </span>
                    </p>
                </div>
            </div>

            <?php if ($canRegister): ?>
                <a href="<?php echo BASE_URL; ?>/tournaments/register.php?id=<?php echo $tournament['tournament_id']; ?>" 
                   class="bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded-lg font-semibold">
                    Register Team
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Tournament Details -->
        <div class="lg:col-span-2 space-y-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Tournament Details</h2>
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="font-medium">Format</p>
                            <p class="text-gray-600"><?php echo ucfirst($tournament['format']); ?></p>
                        </div>
                        <div>
                            <p class="font-medium">Location</p>
                            <p class="text-gray-600">
                                <?php echo htmlspecialchars($tournament['venue']); ?>, 
                                <?php echo htmlspecialchars($tournament['city']); ?>
                            </p>
                        </div>
                        <div>
                            <p class="font-medium">Start Date</p>
                            <p class="text-gray-600">
                                <?php echo date('F j, Y', strtotime($tournament['start_date'])); ?>
                            </p>
                        </div>
                        <div>
                            <p class="font-medium">End Date</p>
                            <p class="text-gray-600">
                                <?php echo date('F j, Y', strtotime($tournament['end_date'])); ?>
                            </p>
                        </div>
                        <div>
                            <p class="font-medium">Entry Fee</p>
                            <p class="text-gray-600">₹<?php echo number_format($tournament['entry_fee'], 2); ?></p>
                        </div>
                        <div>
                            <p class="font-medium">Prize Pool</p>
                            <p class="text-gray-600">₹<?php echo number_format($tournament['prize_pool'], 2); ?></p>
                        </div>
                    </div>

                    <div>
                        <p class="font-medium mb-2">Description</p>
                        <p class="text-gray-600"><?php echo nl2br(htmlspecialchars($tournament['description'])); ?></p>
                    </div>

                    <?php if ($tournament['rules']): ?>
                        <div>
                            <p class="font-medium mb-2">Rules</p>
                            <div class="text-gray-600 prose max-w-none">
                                <?php echo nl2br(htmlspecialchars($tournament['rules'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Registered Teams -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">
                    Registered Teams (<?php echo count($registered_teams); ?>/<?php echo $tournament['max_teams']; ?>)
                </h2>
                <?php if (empty($registered_teams)): ?>
                    <p class="text-gray-500">No teams registered yet.</p>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($registered_teams as $team): ?>
                            <div class="border rounded-lg p-4">
                                <h3 class="font-semibold"><?php echo htmlspecialchars($team['team_name']); ?></h3>
                                <p class="text-sm text-gray-600">
                                    Captain: <?php echo htmlspecialchars($team['captain_name']); ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Registration Info -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-md p-6 sticky top-4">
                <h2 class="text-xl font-semibold mb-4">Registration Information</h2>
                <div class="space-y-4">
                    <div>
                        <p class="font-medium">Registration Deadline</p>
                        <p class="text-gray-600">
                            <?php echo date('F j, Y', strtotime($tournament['registration_deadline'])); ?>
                        </p>
                    </div>
                    <div>
                        <p class="font-medium">Teams</p>
                        <p class="text-gray-600">
                            <?php echo $tournament['registered_teams']; ?> / <?php echo $tournament['max_teams']; ?> registered
                        </p>
                        <p class="text-gray-600">
                            Minimum required: <?php echo $tournament['min_teams']; ?>
                        </p>
                    </div>
                    <?php if ($tournament['status'] === 'open'): ?>
                        <?php if ($canRegister): ?>
                            <a href="<?php echo BASE_URL; ?>/tournaments/register.php?id=<?php echo $tournament['tournament_id']; ?>" 
                               class="block w-full bg-green-500 hover:bg-green-600 text-white text-center px-6 py-3 rounded-lg font-semibold">
                                Register Team
                            </a>
                        <?php elseif (empty($captainTeamIds)): ?>
                            <div class="bg-gray-100 text-gray-600 px-4 py-3 rounded-lg text-center">
                                Must be team captain to register
                            </div>
                        <?php else: ?>
                            <div class="bg-gray-100 text-gray-600 px-4 py-3 rounded-lg text-center">
                                Already registered
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="bg-yellow-100 text-yellow-800 px-4 py-3 rounded-lg text-center">
                            Registration Closed
                        </div>
                    <?php endif; ?>
                </div>
            </div>
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
            return 'bg-gray-100 text-gray-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}
?> 