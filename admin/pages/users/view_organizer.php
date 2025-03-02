<?php
session_start();
require_once '../../../config/database.php';
require_once '../../../config/config.php';

// Check admin authentication
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}

$user_id = $_GET['id'] ?? 0;

// Fetch organizer details
$stmt = $pdo->prepare("
    SELECT * FROM users 
    WHERE user_id = ? AND role = 'organizer'
");
$stmt->execute([$user_id]);
$organizer = $stmt->fetch();

if (!$organizer) {
    header("Location: organizers.php");
    exit();
}

// Fetch organizer's tournaments
$stmt = $pdo->prepare("
    SELECT * FROM tournaments 
    WHERE organizer_id = ?
");
$stmt->execute([$user_id]);
$tournaments = $stmt->fetchAll();

require_once '../../includes/admin_header.php';
require_once '../../includes/admin_sidebar.php';
?>

<div class="p-4 sm:ml-64 mt-14">
    <div class="p-4 border-2 border-gray-200 rounded-lg">
        <!-- Organizer Profile -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-semibold">Organizer Profile</h1>
                <span class="px-3 py-1 rounded-full <?php echo $organizer['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                    <?php echo $organizer['is_active'] ? 'Active' : 'Inactive'; ?>
                </span>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-gray-600">Username: <?php echo htmlspecialchars($organizer['username']); ?></p>
                    <p class="text-gray-600">Email: <?php echo htmlspecialchars($organizer['email']); ?></p>
                    <p class="text-gray-600">Joined: <?php echo date('d M Y', strtotime($organizer['created_at'])); ?></p>
                </div>
            </div>
        </div>

        <!-- Tournaments Section -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">Tournaments</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tournament Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Start Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">End Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($tournaments as $tournament): ?>
                        <tr>
                            <td class="px-6 py-4"><?php echo htmlspecialchars($tournament['tournament_name']); ?></td>
                            <td class="px-6 py-4"><?php echo date('d M Y', strtotime($tournament['start_date'])); ?></td>
                            <td class="px-6 py-4"><?php echo date('d M Y', strtotime($tournament['end_date'])); ?></td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded-full 
                                    <?php echo getStatusColor($tournament['status']); ?>">
                                    <?php echo ucfirst($tournament['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
require_once '../../includes/admin_footer.php';
?>
