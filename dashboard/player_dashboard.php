<?php
require_once '../includes/header.php';

// Get player's information
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$player = $stmt->fetch();

// Get player's team information
$stmt = $pdo->prepare("
    SELECT t.* FROM teams t 
    JOIN team_members tm ON t.team_id = tm.team_id 
    WHERE tm.user_id = ? AND tm.status = 'approved'
");
$stmt->execute([$_SESSION['user_id']]);
$teams = $stmt->fetchAll();

// Add to existing query
$stmt = $pdo->prepare("
    SELECT tt.*, t.tournament_name, t.entry_fee, p.payment_status
    FROM tournament_teams tt
    JOIN tournaments t ON tt.tournament_id = t.tournament_id
    LEFT JOIN payments p ON tt.tournament_id = p.tournament_id AND tt.team_id = p.team_id
    WHERE tt.team_id IN (SELECT team_id FROM teams WHERE captain_id = ?)
    ORDER BY tt.registration_date DESC
");
$stmt->execute([$_SESSION['user_id']]);
$registrations = $stmt->fetchAll();

// Get recent tournament registrations and payments
$stmt = $pdo->prepare("
    SELECT DISTINCT
        tt.*,
        t.tournament_name,
        t.entry_fee,
        tm.team_name,
        CASE 
            WHEN tt.payment_status = 'completed' THEN 'completed'
            WHEN t.entry_fee > 0 AND tt.status = 'approved' THEN 'pending'
            ELSE NULL 
        END as payment_status,
        tt.registration_date
    FROM tournament_teams tt
    JOIN tournaments t ON tt.tournament_id = t.tournament_id
    JOIN teams tm ON tt.team_id = tm.team_id
    WHERE tt.team_id IN (
        SELECT team_id 
        FROM teams 
        WHERE captain_id = ?
    )
    ORDER BY 
        t.tournament_name,
        tt.registration_date DESC
");

// 
$stmt->execute([$_SESSION['user_id']]);
$recent_activities = $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">Player Dashboard</h1>

    <!-- Welcome Section -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-2xl font-semibold mb-4">Welcome, <?php echo htmlspecialchars($player['username']); ?>!</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-blue-50 p-4 rounded-lg">
                <h3 class="font-semibold mb-2">My Teams</h3>
                <p class="text-2xl"><?php echo count($teams); ?></p>
            </div>
            <!-- Add more stats as needed -->
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <a href="<?php echo BASE_URL; ?>/teams/create.php"
            class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow">
            <h3 class="font-semibold mb-2">Create New Team</h3>
            <p class="text-gray-600">Start your own cricket team</p>
        </a>
        <a href="<?php echo BASE_URL; ?>/teams/join.php"
            class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow">
            <h3 class="font-semibold mb-2">Join Team</h3>
            <p class="text-gray-600">Find and join existing teams</p>
        </a>
        <a href="<?php echo BASE_URL; ?>/tournaments/list.php"
            class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow">
            <h3 class="font-semibold mb-2">Browse Tournaments</h3>
            <p class="text-gray-600">Find tournaments to participate in</p>
        </a>
    </div>

    <!-- Recent Activity -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-semibold mb-4">Recent Activity</h2>
        <?php if (empty($recent_activities)): ?>
            <p class="text-gray-500">No recent activity</p>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($recent_activities as $tournament_name => $activities): ?>
                    <div class="border-l-4 
                        <?php echo getActivityBorderColor($activities[0]['status'], $activities[0]['payment_status']); ?> 
                        pl-4 py-2">
                        <p class="font-medium">
                            Tournament Registration: <?php echo htmlspecialchars($tournament_name); ?>
                        </p>
                        <div class="text-sm text-gray-600">
                            <p>Status: <?php echo ucfirst($activities[0]['status']); ?></p>

                            <?php if ($activities[0]['entry_fee'] > 0): ?>
                                <?php if ($activities[0]['status'] === 'approved'): ?>
                                    <?php if ($activities[0]['payment_status'] === 'pending'): ?>
                                        <p class="text-red-600 font-medium mt-1">
                                            Payment Due: ₹<?php echo number_format($activities[0]['entry_fee'], 2); ?>
                                            <a href="<?php echo BASE_URL; ?>/tournaments/payment/submit.php?tournament_id=<?php echo $activities[0]['tournament_id']; ?>&team_id=<?php echo $activities[0]['team_id']; ?>"
                                                class="ml-2 text-blue-500 hover:text-blue-700">
                                                Pay Now
                                            </a>
                                        </p>
                                    <?php else: ?>
                                        <p class="mt-1">
                                            <span class="<?php echo getPaymentStatusColor($activities[0]['payment_status']); ?>">
                                                Payment Status: <?php echo ucfirst($activities[0]['payment_status']); ?>
                                            </span>
                                        </p>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            <?php echo timeAgo($activities[0]['registration_date']); ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php

// this function is used to get the color of the border of the activity
function getActivityBorderColor($status, $payment_status)
{
    if ($status === 'pending') {
        return 'border-yellow-500';
    } elseif ($status === 'approved') {
        if ($payment_status === null || $payment_status === 'pending') {
            return 'border-red-500';  // Payment required
        } elseif ($payment_status === 'submitted') {
            return 'border-blue-500';  // Payment under review
        } elseif ($payment_status === 'verified') {
            return 'border-green-500';  // All good
        }
    } elseif ($status === 'rejected') {
        return 'border-red-500';
    }
    return 'border-gray-500';
}

// this function is used to get the color of the payment status
function getPaymentStatusColor($status)
{
    switch ($status) {
        case 'pending':
            return 'text-yellow-600';
        case 'submitted':
            return 'text-blue-600';
        case 'verified':
            return 'text-green-600';
        case 'rejected':
            return 'text-red-600';
        default:
            return 'text-gray-600';
    }
}

// time ago function this help to show the time ago in the recent activity section
function timeAgo($date)
{
    $timestamp = strtotime($date);
    $difference = time() - $timestamp;

    if ($difference < 60) {
        return "Just now";
    } elseif ($difference < 3600) {
        return floor($difference / 60) . " minutes ago";
    } elseif ($difference < 86400) {
        return floor($difference / 3600) . " hours ago";
    } elseif ($difference < 604800) {
        return floor($difference / 86400) . " days ago";
    } else {
        return date('F j, Y', $timestamp);
    }
}
?>