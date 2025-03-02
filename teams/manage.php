<?php
require_once '../includes/header.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}

// Get team ID from URL
$team_id = $_GET['team_id'] ?? null;

if (!$team_id) {
    $_SESSION['error'] = "Team ID is required";
    header("Location: " . BASE_URL . "/teams/view.php");
    exit();
}

// Check if user is team captain
$stmt = $pdo->prepare("
    SELECT t.*, u.username as captain_name 
    FROM teams t
    JOIN users u ON t.captain_id = u.user_id
    WHERE t.team_id = ? AND t.captain_id = ?
");
$stmt->execute([$team_id, $_SESSION['user_id']]);
$team = $stmt->fetch();

if (!$team) {
    $_SESSION['error'] = "You don't have permission to manage this team";
    header("Location: " . BASE_URL . "/teams/view.php");
    exit();
}

// Get team members
$stmt = $pdo->prepare("
    SELECT tm.*, u.username, u.email
    FROM team_members tm
    JOIN users u ON tm.user_id = u.user_id
    WHERE tm.team_id = ?
");
$stmt->execute([$team_id]);
$members = $stmt->fetchAll();

// Get pending join requests
$stmt = $pdo->prepare("
    SELECT tm.*, u.username, u.email
    FROM team_members tm
    JOIN users u ON tm.user_id = u.user_id
    WHERE tm.team_id = ? AND tm.status = 'pending'
");
$stmt->execute([$team_id]);
$pending_requests = $stmt->fetchAll();
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold">Manage Team: <?php echo htmlspecialchars($team['team_name']); ?></h1>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Team Members Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">Team Members</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Member</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($members as $member): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($member['username']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($member['email']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php echo $member['status'] === 'approved' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                        <?php echo ucfirst($member['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <?php if ($member['user_id'] !== $_SESSION['user_id']): ?>
                                        <form action="<?php echo BASE_URL; ?>/teams/process/remove_member.php" method="POST" class="inline">
                                            <input type="hidden" name="team_id" value="<?php echo $team_id; ?>">
                                            <input type="hidden" name="user_id" value="<?php echo $member['user_id']; ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-900">Remove</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pending Requests Section -->
        <?php if (!empty($pending_requests)): ?>
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Pending Join Requests</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Player</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($pending_requests as $request): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($request['username']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($request['email']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <form action="<?php echo BASE_URL; ?>/teams/process/handle_request.php" method="POST" class="inline">
                                            <input type="hidden" name="team_id" value="<?php echo $team_id; ?>">
                                            <input type="hidden" name="user_id" value="<?php echo $request['user_id']; ?>">
                                            <button type="submit" name="action" value="approve" class="text-green-600 hover:text-green-900 mr-4">Approve</button>
                                            <button type="submit" name="action" value="reject" class="text-red-600 hover:text-red-900">Reject</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
