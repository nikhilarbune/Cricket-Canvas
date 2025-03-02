<?php
session_start();
require_once '../config/database.php';
require_once '../config/config.php';

// Check if user is logged in and is an organizer
if (!isLoggedIn() || getUserRole() != 'organizer') {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $tournament_name = $_POST['tournament_name'];
        $format = $_POST['format'];
        $entry_fee = $_POST['entry_fee'] ?? 0;
        $min_teams = $_POST['min_teams'];
        $max_teams = $_POST['max_teams'];
        $prize_pool = $_POST['prize_pool'] ?? 0;
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $registration_deadline = $_POST['registration_deadline'];
        $venue = $_POST['venue'];
        $city = $_POST['city'];
        $description = $_POST['description'];
        $rules = $_POST['rules'];

        // Validate QR code if entry fee is set
        if ($entry_fee > 0) {
            if (!isset($_FILES['payment_qr']) || $_FILES['payment_qr']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Payment QR code is required when entry fee is set");
            }

            // Handle QR code upload
            $upload_dir = '../uploads/payment_qr/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_extension = pathinfo($_FILES['payment_qr']['name'], PATHINFO_EXTENSION);
            $qr_filename = uniqid() . '.' . $file_extension;
            $qr_path = $upload_dir . $qr_filename;

            if (!move_uploaded_file($_FILES['payment_qr']['tmp_name'], $qr_path)) {
                throw new Exception("Failed to upload QR code");
            }
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO tournaments (
                tournament_name, format, entry_fee, min_teams, max_teams,
                prize_pool, start_date, end_date, registration_deadline,
                venue, city, description, rules, payment_qr, organizer_id, status
            ) VALUES (
                ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, 
                ?, ?, ?, ?, ?, ?, 'draft'
            )
        ");

        $stmt->execute([
            $tournament_name,
            $format,
            $entry_fee,
            $min_teams,
            $max_teams,
            $prize_pool,
            $start_date,
            $end_date,
            $registration_deadline,
            $venue,
            $city,
            $description,
            $rules,
            ($entry_fee > 0 ? $qr_filename : null),
            $_SESSION['user_id']
        ]);

        $pdo->commit();
        $_SESSION['success'] = "Tournament created successfully!";
        header("Location: " . BASE_URL . "/tournaments/manage.php");
        exit();
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
}

require_once '../includes/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <div class="max-w-3xl mx-auto">
        <div class="mb-8">
            <a href="<?php echo BASE_URL; ?>/tournaments/manage.php"
                class="text-blue-500 hover:text-blue-700">
                ← Back to Tournament Management
            </a>
        </div>

        <h1 class="text-3xl font-bold mb-8">Create Tournament</h1>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                <?php
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data" class="bg-white rounded-lg shadow-md p-6">
            <!-- Basic Details -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Tournament Name</label>
                <input type="text"
                    name="tournament_name"
                    required
                    class="w-full px-3 py-2 border rounded-lg">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Format</label>
                    <select name="format" required class="w-full px-3 py-2 border rounded-lg">
                        <option value="knockout">Knockout</option>
                        <option value="league">League</option>
                        <option value="group_stage">Group Stage</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">City</label>
                    <input type="text"
                        name="city"
                        required
                        class="w-full px-3 py-2 border rounded-lg">
                </div>
            </div>

            <!-- Team & Prize Details -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Minimum Teams</label>
                    <input type="number"
                        name="min_teams"
                        required
                        min="2"
                        class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Maximum Teams</label>
                    <input type="number"
                        name="max_teams"
                        required
                        min="2"
                        class="w-full px-3 py-2 border rounded-lg">
                </div>
            </div>

            <!-- Payment Details -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Entry Fee (₹)</label>
                    <input type="number"
                        name="entry_fee"
                        min="0"
                        class="w-full px-3 py-2 border rounded-lg"
                        onchange="toggleQRUpload(this.value)">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Prize Pool (₹)</label>
                    <input type="number"
                        name="prize_pool"
                        min="0"
                        class="w-full px-3 py-2 border rounded-lg">
                </div>
            </div>

            <!-- QR Code Upload -->
            <div id="qr_upload_section" class="mb-6" style="display: none;">
                <label class="block text-sm font-medium text-gray-700 mb-2">Payment QR Code</label>
                <input type="file"
                    name="payment_qr"
                    accept="image/*"
                    class="w-full px-3 py-2 border rounded-lg">
                <p class="text-sm text-gray-500 mt-1">
                    Upload QR code for payments (UPI/Bank). Required if entry fee is greater than 0.
                </p>
            </div>

            <!-- Dates -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                    <input type="date"
                        name="start_date"
                        required
                        class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                    <input type="date"
                        name="end_date"
                        required
                        class="w-full px-3 py-2 border rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Registration Deadline</label>
                    <input type="date"
                        name="registration_deadline"
                        required
                        class="w-full px-3 py-2 border rounded-lg">
                </div>
            </div>

            <!-- Venue -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Venue</label>
                <input type="text"
                    name="venue"
                    required
                    class="w-full px-3 py-2 border rounded-lg">
            </div>

            <!-- Description & Rules -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea name="description"
                    rows="4"
                    required
                    class="w-full px-3 py-2 border rounded-lg"></textarea>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Rules</label>
                <textarea name="rules"
                    rows="4"
                    required
                    class="w-full px-3 py-2 border rounded-lg"></textarea>
            </div>

            <div class="flex justify-end">
                <button type="submit"
                    class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">
                    Create Tournament
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleQRUpload(value) {
        const qrSection = document.getElementById('qr_upload_section');
        const qrInput = qrSection.querySelector('input[type="file"]');

        if (parseInt(value) > 0) {
            qrSection.style.display = 'block';
            qrInput.required = true;
        } else {
            qrSection.style.display = 'none';
            qrInput.required = false;
        }
    }
</script>