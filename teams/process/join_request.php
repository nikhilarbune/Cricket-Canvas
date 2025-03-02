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
    $user_id = $_SESSION['user_id'];

    try {
        // Check if user already has a pending request
        $stmt = $pdo->prepare("SELECT * FROM team_members WHERE team_id = ? AND user_id = ?");
        $stmt->execute([$team_id, $user_id]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = "You have already requested to join this team";
            header("Location: " . BASE_URL . "/teams/join.php");
            exit();
        }

        // Check if team has space
        $stmt = $pdo->prepare("
            SELECT t.max_members, 
                   (SELECT COUNT(*) FROM team_members WHERE team_id = ? AND status = 'approved') as current_members
            FROM teams t WHERE t.team_id = ?
        ");
        $stmt->execute([$team_id, $team_id]);
        $team = $stmt->fetch();

        if ($team['current_members'] >= $team['max_members']) {
            $_SESSION['error'] = "This team is already full";
            header("Location: " . BASE_URL . "/teams/join.php");
            exit();
        }

        // Create join request
        $stmt = $pdo->prepare("INSERT INTO team_members (team_id, user_id, status) VALUES (?, ?, 'pending')");
        $stmt->execute([$team_id, $user_id]);

        $_SESSION['success'] = "Join request sent successfully";
        header("Location: " . BASE_URL . "/teams/join.php");
        exit();

    } catch (PDOException $e) {
        $_SESSION['error'] = "Failed to send join request: " . $e->getMessage();
        header("Location: " . BASE_URL . "/teams/join.php");
        exit();
    }
} 