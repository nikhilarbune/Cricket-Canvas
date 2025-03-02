<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/config.php';

if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $team_name = $_POST['team_name'];
    $description = $_POST['description'];
    $captain_id = $_SESSION['user_id'];

    try {
        // Start transaction
        $pdo->beginTransaction();

        // Create team
        $stmt = $pdo->prepare("INSERT INTO teams (team_name, captain_id) VALUES (?, ?)");
        $stmt->execute([$team_name, $captain_id]);
        $team_id = $pdo->lastInsertId();

        // Add captain as team member
        $stmt = $pdo->prepare("INSERT INTO team_members (team_id, user_id, status) VALUES (?, ?, 'approved')");
        $stmt->execute([$team_id, $captain_id]);

        $pdo->commit();
        $_SESSION['success'] = "Team created successfully!";
        header("Location: " . BASE_URL . "/teams/view.php");
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Failed to create team: " . $e->getMessage();
        header("Location: " . BASE_URL . "/teams/create.php");
        exit();
    }
} 