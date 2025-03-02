<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/config.php';

if (!isLoggedIn() || getUserRole() != 'organizer') {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $tournament_id = $_POST['tournament_id'];
        $new_status = $_POST['status'];

        // Verify tournament belongs to this organizer
        $stmt = $pdo->prepare("
            SELECT * FROM tournaments 
            WHERE tournament_id = ? AND organizer_id = ?
        ");
        $stmt->execute([$tournament_id, $_SESSION['user_id']]);
        $tournament = $stmt->fetch();

        if (!$tournament) {
            throw new Exception("Tournament not found or access denied");
        }

        // Validate status transition
        $valid_transitions = [
            'draft' => ['open'],
            'open' => ['registration_closed'],
            'registration_closed' => ['ongoing'],
            'ongoing' => ['completed']
        ];

        if (!isset($valid_transitions[$tournament['status']]) || 
            !in_array($new_status, $valid_transitions[$tournament['status']])) {
            throw new Exception("Invalid status transition");
        }

        // Update tournament status
        $stmt = $pdo->prepare("
            UPDATE tournaments 
            SET status = ? 
            WHERE tournament_id = ?
        ");
        $stmt->execute([$new_status, $tournament_id]);

        $_SESSION['success'] = "Tournament status updated successfully";
        header("Location: " . BASE_URL . "/tournaments/manage.php");
        exit();

    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
        header("Location: " . BASE_URL . "/tournaments/manage.php");
        exit();
    }
} 