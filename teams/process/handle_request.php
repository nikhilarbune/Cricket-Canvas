<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/config.php';

if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $team_id = $_POST['team_id'];
    $user_id = $_POST['user_id'];
    $action = $_POST['action'];

    // Verify user is team captain
    $stmt = $pdo->prepare("SELECT captain_id FROM teams WHERE team_id = ?");
    $stmt->execute([$team_id]);
    $team = $stmt->fetch();

    if ($team['captain_id'] !== $_SESSION['user_id']) {
        $_SESSION['error'] = "You don't have permission to manage this team";
        header("Location: " . BASE_URL . "/teams/view.php");
        exit();
    }

    try {
        $status = $action === 'approve' ? 'approved' : 'rejected';
        $stmt = $pdo->prepare("UPDATE team_members SET status = ? WHERE team_id = ? AND user_id = ?");
        $stmt->execute([$status, $team_id, $user_id]);

        $_SESSION['success'] = "Request " . $status . " successfully";
        header("Location: " . BASE_URL . "/teams/manage.php?team_id=" . $team_id);
        exit();

    } catch (PDOException $e) {
        $_SESSION['error'] = "Failed to process request: " . $e->getMessage();
        header("Location: " . BASE_URL . "/teams/manage.php?team_id=" . $team_id);
        exit();
    }
} 