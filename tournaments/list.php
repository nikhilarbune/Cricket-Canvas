<?php
session_start();
require_once '../config/database.php';
require_once '../config/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}

require_once '../includes/header.php';

// Get user's team IDs where they are captain
$stmt = $pdo->prepare("
    SELECT team_id 
    FROM teams 
    WHERE captain_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$captainTeamIds = array_column($stmt->fetchAll(), 'team_id');

// Build the query based on filters
$query = "
    SELECT t.*, 
           u.username as organizer_name,
           (SELECT COUNT(*) FROM tournament_teams WHERE tournament_id = t.tournament_id AND status = 'approved') as registered_teams
    FROM tournaments t
    JOIN users u ON t.organizer_id = u.user_id
    WHERE t.status IN ('open', 'registration_closed')
";

$params = [];

// Apply filters if set
if (isset($_GET['city']) && !empty($_GET['city'])) {
    $query .= " AND t.city LIKE ?";
    $params[] = '%' . $_GET['city'] . '%';
}

if (isset($_GET['format']) && !empty($_GET['format'])) {
    $query .= " AND t.format = ?";
    $params[] = $_GET['format'];
}

$query .= " ORDER BY t.registration_deadline ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tournaments = $stmt->fetchAll();

// Get cities for filter
$stmt = $pdo->query("SELECT DISTINCT city FROM tournaments WHERE city IS NOT NULL ORDER BY city");
$cities = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">Available Tournaments</h1>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">City</label>
                <select name="city" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Cities</option>
                    <?php foreach ($cities as $city): ?>
                        <option value="<?php echo $city; ?>" <?php echo (isset($_GET['city']) && $_GET['city'] == $city) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($city); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Format</label>
                <select name="format" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Formats</option>
                    <option value="knockout" <?php echo (isset($_GET['format']) && $_GET['format'] == 'knockout') ? 'selected' : ''; ?>>Knockout</option>
                    <option value="league" <?php echo (isset($_GET['format']) && $_GET['format'] == 'league') ? 'selected' : ''; ?>>League</option>
                    <option value="group_stage" <?php echo (isset($_GET['format']) && $_GET['format'] == 'group_stage') ? 'selected' : ''; ?>>Group Stage</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                    Apply Filters
                </button>
            </div>
        </form>
    </div>

    <?php if (empty($tournaments)): ?>
        <div class="bg-white rounded-lg shadow-md p-6 text-center">
            <p class="text-gray-500 text-lg">No tournaments available at the moment.</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 gap-6">
            <?php foreach ($tournaments as $tournament): ?>
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <h2 class="text-2xl font-semibold mb-2">
                                <a href="<?php echo BASE_URL; ?>/tournaments/details.php?id=<?php echo $tournament['tournament_id']; ?>"
                                    class="hover:text-blue-500">
                                    <?php echo htmlspecialchars($tournament['tournament_name']); ?>
                                </a>
                            </h2>
                            <div class="space-y-2 text-sm text-gray-600">
                                <p>
                                    <span class="font-medium">Organizer:</span>
                                    <?php echo htmlspecialchars($tournament['organizer_name']); ?>
                                </p>
                                <p>
                                    <span class="font-medium">Format:</span>
                                    <?php echo ucfirst($tournament['format']); ?>
                                </p>
                                <p>
                                    <span class="font-medium">Location:</span>
                                    <?php echo htmlspecialchars($tournament['city']); ?>
                                </p>
                                <p>
                                    <span class="font-medium">Teams:</span>
                                    <?php echo $tournament['registered_teams']; ?> / <?php echo $tournament['max_teams']; ?> registered
                                </p>
                                <p>
                                    <span class="font-medium">Entry Fee:</span>
                                    ₹<?php echo number_format($tournament['entry_fee'], 2); ?>
                                </p>
                                <p>
                                    <span class="font-medium">Prize Pool:</span>
                                    ₹<?php echo number_format($tournament['prize_pool'], 2); ?>
                                </p>
                                <p>
                                    <span class="font-medium">Registration Deadline:</span>
                                    <?php echo date('F j, Y', strtotime($tournament['registration_deadline'])); ?>
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-col space-y-2">
                            <?php if ($tournament['status'] === 'open'): ?>
                                <?php if (!empty($captainTeamIds)): ?>
                                    <a href="<?php echo BASE_URL; ?>/tournaments/register.php?id=<?php echo $tournament['tournament_id']; ?>"
                                        class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg text-sm text-center">
                                        Register Team
                                    </a>
                                <?php else: ?>
                                    <span class="bg-gray-100 text-gray-600 px-4 py-2 rounded-lg text-sm text-center">
                                        Must be team captain to register
                                    </span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="bg-yellow-100 text-yellow-800 px-4 py-2 rounded-lg text-sm text-center">
                                    Registration Closed
                                </span>
                            <?php endif; ?>

                            <a href="<?php echo BASE_URL; ?>/tournaments/details.php?id=<?php echo $tournament['tournament_id']; ?>"
                                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm text-center">
                                View Details
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>