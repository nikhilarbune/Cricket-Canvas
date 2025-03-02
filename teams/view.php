<?php
require_once '../includes/header.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}

// Get teams where user is a member
$stmt = $pdo->prepare("
    SELECT t.*, 
           tm.status as member_status,
           (t.captain_id = ?) as is_captain,
           (SELECT COUNT(*) FROM team_members WHERE team_id = t.team_id AND status = 'approved') as member_count
    FROM teams t
    JOIN team_members tm ON t.team_id = tm.team_id
    WHERE tm.user_id = ?
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
$teams = $stmt->fetchAll();
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold">My Teams</h1>
        <a href="<?php echo BASE_URL; ?>/teams/create.php" 
           class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Create New Team
        </a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
            <?php 
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($teams as $team): ?>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex justify-between items-start mb-4">
                    <h2 class="text-xl font-semibold"><?php echo htmlspecialchars($team['team_name']); ?></h2>
                    <?php if ($team['is_captain']): ?>
                        <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded">Captain</span>
                    <?php endif; ?>
                </div>
                
                <div class="text-gray-600 mb-4">
                    <p>Members: <?php echo $team['member_count']; ?></p>
                    <p>Status: <?php echo ucfirst($team['member_status']); ?></p>
                </div>

                <div class="flex justify-end space-x-2">
                    <?php if ($team['is_captain']): ?>
                        <a href="<?php echo BASE_URL; ?>/teams/manage.php?team_id=<?php echo $team['team_id']; ?>" 
                           class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded text-sm">
                            Manage Team
                        </a>
                    <?php endif; ?>
                    <a href="<?php echo BASE_URL; ?>/teams/details.php?team_id=<?php echo $team['team_id']; ?>" 
                       class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded text-sm">
                        View Details
                    </a>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($teams)): ?>
            <div class="col-span-full text-center py-8">
                <p class="text-gray-500 text-lg">You haven't joined any teams yet.</p>
                <a href="<?php echo BASE_URL; ?>/teams/join.php" 
                   class="text-blue-500 hover:text-blue-700 font-medium">
                    Browse teams to join
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>
