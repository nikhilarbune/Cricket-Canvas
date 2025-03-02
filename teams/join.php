<?php
require_once '../includes/header.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}

// Get all teams that the user hasn't joined or requested to join
$stmt = $pdo->prepare("
    SELECT t.*, 
           u.username as captain_name,
           (SELECT COUNT(*) FROM team_members WHERE team_id = t.team_id AND status = 'approved') as member_count
    FROM teams t
    JOIN users u ON t.captain_id = u.user_id
    WHERE t.team_status = 'active'
    AND t.team_id NOT IN (
        SELECT team_id 
        FROM team_members 
        WHERE user_id = ?
    )
    ORDER BY t.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$available_teams = $stmt->fetchAll();
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <h1 class="text-3xl font-bold mb-8">Join a Team</h1>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Search and Filter Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                    <input type="text" name="search" value="<?php echo $_GET['search'] ?? ''; ?>"
                           class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Search teams...">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">City</label>
                    <select name="city" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Cities</option>
                        <!-- Add city options dynamically -->
                    </select>
                </div>
                <div class="md:self-end">
                    <button type="submit" class="w-full bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
                        Search Teams
                    </button>
                </div>
            </form>
        </div>

        <!-- Teams Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($available_teams as $team): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <?php if ($team['logo']): ?>
                                <img src="<?php echo BASE_URL . '/uploads/team_logos/' . $team['logo']; ?>" 
                                     alt="<?php echo htmlspecialchars($team['team_name']); ?>" 
                                     class="w-16 h-16 rounded-full object-cover">
                            <?php else: ?>
                                <div class="w-16 h-16 rounded-full bg-gray-200 flex items-center justify-center">
                                    <span class="text-2xl font-bold text-gray-500">
                                        <?php echo strtoupper(substr($team['team_name'], 0, 1)); ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            <div class="ml-4 flex-1">
                                <h2 class="text-xl font-semibold"><?php echo htmlspecialchars($team['team_name']); ?></h2>
                                <p class="text-gray-600 text-sm">Captain: <?php echo htmlspecialchars($team['captain_name']); ?></p>
                            </div>
                        </div>

                        <div class="space-y-2 mb-4">
                            <?php if ($team['city']): ?>
                                <p class="text-gray-600 text-sm">
                                    <i class="fas fa-map-marker-alt mr-2"></i>
                                    <?php echo htmlspecialchars($team['city']); ?>
                                </p>
                            <?php endif; ?>
                            <p class="text-gray-600 text-sm">
                                <i class="fas fa-users mr-2"></i>
                                Members: <?php echo $team['member_count']; ?>/<?php echo $team['max_members']; ?>
                            </p>
                        </div>

                        <div class="mt-4 flex justify-between items-center">
                            <a href="<?php echo BASE_URL; ?>/teams/details.php?team_id=<?php echo $team['team_id']; ?>" 
                               class="text-blue-500 hover:text-blue-700">
                                View Details
                            </a>
                            <form action="<?php echo BASE_URL; ?>/teams/process/join_request.php" method="POST">
                                <input type="hidden" name="team_id" value="<?php echo $team['team_id']; ?>">
                                <button type="submit" 
                                        class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg text-sm"
                                        <?php echo ($team['member_count'] >= $team['max_members']) ? 'disabled' : ''; ?>>
                                    Send Join Request
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($available_teams)): ?>
                <div class="col-span-full text-center py-8">
                    <p class="text-gray-500 text-lg">No teams available to join at the moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
