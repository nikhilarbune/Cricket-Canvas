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
        $team_id = $_POST['team_id'];
        $action = $_POST['action'];

        $pdo->beginTransaction();

        if ($action === 'approve') {
            // Update both registration status and payment status
            $stmt = $pdo->prepare("
                UPDATE tournament_teams 
                SET 
                    status = 'approved',
                    payment_status = 'completed'  -- Set payment as completed when approving
                WHERE tournament_id = ? AND team_id = ?
            ");
            $stmt->execute([$tournament_id, $team_id]);

            $_SESSION['success'] = "Team approved successfully and payment marked as completed.";
        } else {
            // Handle rejection
            $stmt = $pdo->prepare("
                UPDATE tournament_teams 
                SET status = 'rejected'
                WHERE tournament_id = ? AND team_id = ?
            ");
            $stmt->execute([$tournament_id, $team_id]);

            $_SESSION['success'] = "Team rejected successfully";
        }

        $pdo->commit();
        header("Location: " . BASE_URL . "/tournaments/registrations.php?id=" . $tournament_id);
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error: " . $e->getMessage();
        header("Location: " . BASE_URL . "/tournaments/registrations.php?id=" . $tournament_id);
        exit();
    }
}

function createNotification($user_id, $type, $message)
{
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, type, message)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$user_id, $type, $message]);
}
