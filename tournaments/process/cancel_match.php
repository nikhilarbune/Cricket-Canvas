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
        $match_id = $_POST['match_id'];

        // Verify match belongs to organizer's tournament
        $stmt = $pdo->prepare("
            SELECT m.*, t.organizer_id 
            FROM matches m
            JOIN tournaments t ON m.tournament_id = t.tournament_id
            WHERE m.match_id = ?
        ");
        $stmt->execute([$match_id]);
        $match = $stmt->fetch();

        if (!$match || $match['organizer_id'] !== $_SESSION['user_id']) {
            throw new Exception("Match not found or unauthorized");
        }

        // Cancel the match
        $stmt = $pdo->prepare("
            UPDATE matches 
            SET match_status = 'cancelled' 
            WHERE match_id = ?
        ");
        $stmt->execute([$match_id]);

        $_SESSION['success'] = "Match cancelled successfully";
        header("Location: " . BASE_URL . "/tournaments/matches/schedule.php?id=" . $match['tournament_id']);
        exit();

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: " . BASE_URL . "/tournaments/matches/schedule.php?id=" . $match['tournament_id']);
        exit();
    }
}