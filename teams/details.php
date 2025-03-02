<?php
require_once '../includes/header.php';

// Get team ID from URL
$team_id = $_GET['team_id'] ?? null;

if (!$team_id) {
    $_SESSION['error'] = "Team ID is required";
    header("Location: " . BASE_URL . "/teams/view.php");
    exit();
}

// Get team details
$stmt = $pdo->prepare("
    SELECT t.*, 
           u.username as captain_name,
           (SELECT COUNT(*) FROM team_members WHERE team_id = t.team_id AND status = 'approved') as member_count
    FROM teams t
    JOIN users u ON t.captain_id = u.user_id
    WHERE t.team_id = ?
");
$stmt->execute([$team_id]);
$team = $stmt->fetch();

if (!$team) {
    $_SESSION['error'] = "Team not found";
    header("Location: " . BASE_URL . "/teams/view.php");
    exit();
}

// Get team members
$stmt = $pdo->prepare("
    SELECT u.username, u.email, tm.status
    FROM team_members tm
    JOIN users u ON tm.user_id = u.user_id
    WHERE tm.team_id = ? AND tm.status = 'approved'
    ORDER BY u.username
");
$stmt->execute([$team_id]);
$members = $stmt->fetchAll();
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Team Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <div class="flex justify-between items-start mb-4">
                <div class="flex items-center space-x-6">
                    <?php if ($team['logo']): ?>
                        <img src="<?php echo BASE_URL . '/uploads/team_logos/' . $team['logo']; ?>" 
                             alt="<?php echo htmlspecialchars($team['team_name']); ?>" 
                             class="w-24 h-24 rounded-full object-cover">
                    <?php else: ?>
                        <div class="w-24 h-24 rounded-full bg-gray-200 flex items-center justify-center">
                            <span class="text-3xl font-bold text-gray-500">
                                <?php echo strtoupper(substr($team['team_name'], 0, 1)); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    
                    <div>
                        <h1 class="text-3xl font-bold mb-2"><?php echo htmlspecialchars($team['team_name']); ?></h1>
                        <p class="text-gray-600">Captain: <?php echo htmlspecialchars($team['captain_name']); ?></p>
                    </div>
                </div>

                <?php if ($team['captain_id'] == $_SESSION['user_id']): ?>
                    <button id="editTeamBtn" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                        Edit Team
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Edit Team Modal -->
        <?php if ($team['captain_id'] == $_SESSION['user_id']): ?>
        <div id="editTeamModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
            <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold">Edit Team Details</h3>
                    <button id="closeModal" class="text-gray-600 hover:text-gray-900">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>

                <form action="<?php echo BASE_URL; ?>/teams/process/update_team.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="team_id" value="<?php echo $team['team_id']; ?>">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Team Name</label>
                            <input type="text" name="team_name" value="<?php echo htmlspecialchars($team['team_name']); ?>" required
                                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Team Logo</label>
                            <input type="file" name="logo" accept="image/*"
                                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea name="description" rows="3"
                                      class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($team['description']); ?></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Home Ground</label>
                                <input type="text" name="home_ground" value="<?php echo htmlspecialchars($team['home_ground']); ?>"
                                       class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">City</label>
                                <input type="text" name="city" value="<?php echo htmlspecialchars($team['city']); ?>"
                                       class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Contact Email</label>
                                <input type="email" name="contact_email" value="<?php echo htmlspecialchars($team['contact_email']); ?>"
                                       class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Contact Phone</label>
                                <input type="tel" name="contact_phone" value="<?php echo htmlspecialchars($team['contact_phone']); ?>"
                                       class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Maximum Members</label>
                            <input type="number" name="max_members" value="<?php echo $team['max_members']; ?>" min="11" max="25"
                                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-4">
                        <button type="button" id="cancelEdit"
                                class="px-4 py-2 border text-gray-600 rounded-lg hover:bg-gray-100">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('editTeamModal');
            const editBtn = document.getElementById('editTeamBtn');
            const closeBtn = document.getElementById('closeModal');
            const cancelBtn = document.getElementById('cancelEdit');

            function toggleModal() {
                modal.classList.toggle('hidden');
            }

            editBtn.addEventListener('click', toggleModal);
            closeBtn.addEventListener('click', toggleModal);
            cancelBtn.addEventListener('click', toggleModal);

            // Close modal when clicking outside
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    toggleModal();
                }
            });
        });
        </script>
        <?php endif; ?>

        <!-- Team Information -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Team Information</h2>
                <div class="space-y-3">
                    <?php if ($team['description']): ?>
                        <p class="text-gray-600"><?php echo nl2br(htmlspecialchars($team['description'])); ?></p>
                    <?php endif; ?>
                    
                    <?php if ($team['home_ground']): ?>
                        <p class="text-gray-600">
                            <span class="font-medium">Home Ground:</span> 
                            <?php echo htmlspecialchars($team['home_ground']); ?>
                        </p>
                    <?php endif; ?>
                    
                    <?php if ($team['city']): ?>
                        <p class="text-gray-600">
                            <span class="font-medium">City:</span> 
                            <?php echo htmlspecialchars($team['city']); ?>
                        </p>
                    <?php endif; ?>
                    
                    <?php if ($team['established_date']): ?>
                        <p class="text-gray-600">
                            <span class="font-medium">Established:</span> 
                            <?php echo date('F Y', strtotime($team['established_date'])); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Contact Information</h2>
                <div class="space-y-3">
                    <?php if ($team['contact_email']): ?>
                        <p class="text-gray-600">
                            <span class="font-medium">Email:</span> 
                            <?php echo htmlspecialchars($team['contact_email']); ?>
                        </p>
                    <?php endif; ?>
                    
                    <?php if ($team['contact_phone']): ?>
                        <p class="text-gray-600">
                            <span class="font-medium">Phone:</span> 
                            <?php echo htmlspecialchars($team['contact_phone']); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Team Members -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">Team Members (<?php echo count($members); ?>/<?php echo $team['max_members']; ?>)</h2>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <?php foreach ($members as $member): ?>
                    <div class="p-3 border rounded-lg">
                        <p class="font-medium"><?php echo htmlspecialchars($member['username']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div> 