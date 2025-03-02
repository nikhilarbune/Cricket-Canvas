<?php
session_start();
require_once '../config/database.php';
require_once '../config/config.php';

// Check if user is logged in and is an organizer
if (!isLoggedIn() || getUserRole() != 'organizer') {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}

// Get all tournaments created by this organizer with registration counts
$stmt = $pdo->prepare("
    SELECT 
        t.*,
        (SELECT COUNT(*) FROM tournament_teams WHERE tournament_id = t.tournament_id) as total_registrations,
        (SELECT COUNT(*) FROM tournament_teams WHERE tournament_id = t.tournament_id AND status = 'pending') as pending_registrations,
        (SELECT COUNT(*) FROM tournament_teams WHERE tournament_id = t.tournament_id AND status = 'approved') as approved_teams
    FROM tournaments t
    WHERE t.organizer_id = ?
    ORDER BY t.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$tournaments = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <!-- header and buttons -->
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold">Manage Tournaments</h1>
        <div class="flex gap-4">
            <a href="<?php echo BASE_URL; ?>/tournaments/create.php"
                class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
                Create Tournament
            </a>
    
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
            <?php
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (empty($tournaments)): ?>
        <div class="bg-white rounded-lg shadow-md p-6 text-center">
            <p class="text-gray-500 text-lg">You haven't created any tournaments yet.</p>
            <a href="<?php echo BASE_URL; ?>/tournaments/create.php"
                class="text-blue-500 hover:text-blue-700 font-medium">
                Create your first tournament
            </a>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 gap-6">
            <?php foreach ($tournaments as $tournament): ?>
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex justify-between items-start">
                        <div>
                            <h2 class="text-2xl font-semibold mb-2">
                                <?php echo htmlspecialchars($tournament['tournament_name']); ?>
                            </h2>
                            <div class="space-y-1 text-sm text-gray-600">
                                <p>
                                    <span class="font-medium">Format:</span>
                                    <?php echo ucfirst($tournament['format']); ?>
                                </p>
                                <p>
                                    <span class="font-medium">Status:</span>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php echo getStatusColor($tournament['status']); ?>">
                                        <?php echo ucfirst($tournament['status']); ?>
                                    </span>
                                </p>
                                <p>
                                    <span class="font-medium">Teams:</span>
                                    <?php echo $tournament['approved_teams']; ?> / <?php echo $tournament['max_teams']; ?> approved
                                    <?php if ($tournament['pending_registrations'] > 0): ?>
                                        <span class="text-yellow-600">
                                            (<?php echo $tournament['pending_registrations']; ?> pending)
                                        </span>
                                    <?php endif; ?>
                                </p>
                                <p>
                                    <span class="font-medium">Registration Deadline:</span>
                                    <?php echo date('F j, Y', strtotime($tournament['registration_deadline'])); ?>
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-col space-y-2">
                            <?php if ($tournament['status'] === 'draft'): ?>
                                <form action="<?php echo BASE_URL; ?>/tournaments/process/update_status.php" method="POST" class="inline">
                                    <input type="hidden" name="tournament_id" value="<?php echo $tournament['tournament_id']; ?>">
                                    <input type="hidden" name="status" value="open">
                                    <button type="submit" class="w-full bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg text-sm">
                                        Publish Tournament
                                    </button>
                                </form>
                            <?php else: ?>
                                <?php if ($tournament['pending_registrations'] > 0): ?>
                                    <a href="<?php echo BASE_URL; ?>/tournaments/registrations.php?id=<?php echo $tournament['tournament_id']; ?>"
                                        class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg text-sm text-center">
                                        Review Registrations
                                        <span class="bg-yellow-600 text-white px-2 py-1 rounded-full text-xs">
                                            <?php echo $tournament['pending_registrations']; ?>
                                        </span>
                                    </a>
                                <?php elseif ($tournament['approved_teams'] > 0): ?>
                                    <a href="<?php echo BASE_URL; ?>/tournaments/registrations.php?id=<?php echo $tournament['tournament_id']; ?>"
                                        class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm text-center">
                                        View Teams (<?php echo $tournament['approved_teams']; ?>)
                                    </a>
                                <?php endif; ?>

                                <?php if ($tournament['status'] === 'open'): ?>
                                    <?php if ($tournament['approved_teams'] >= $tournament['min_teams']): ?>
                                        <form action="<?php echo BASE_URL; ?>/tournaments/process/update_status.php" method="POST" class="inline">
                                            <input type="hidden" name="tournament_id" value="<?php echo $tournament['tournament_id']; ?>">
                                            <input type="hidden" name="status" value="registration_closed">
                                            <button type="submit"
                                                class="w-full bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg text-sm">
                                                Close Registration
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php if ($tournament['status'] === 'registration_closed' || $tournament['status'] === 'ongoing'): ?>
                                    <a href="<?php echo BASE_URL; ?>/tournaments/matches/schedule.php?id=<?php echo $tournament['tournament_id']; ?>"
                                       class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm text-center">
                                        Schedule Matches
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
function getStatusColor($status)
{
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