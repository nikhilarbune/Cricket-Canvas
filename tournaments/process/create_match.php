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
        $team1_id = $_POST['team1_id'];
        $team2_id = $_POST['team2_id'];
        $match_date = $_POST['match_date'];
        $venue = $_POST['venue'];

        // Validate inputs
        if ($team1_id === $team2_id) {
            throw new Exception("Cannot schedule a match between the same team");
        }

        // Verify tournament ownership and status
        $stmt = $pdo->prepare("
            SELECT * FROM tournaments 
            WHERE tournament_id = ? 
            AND organizer_id = ?
            AND status IN ('registration_closed', 'ongoing')
        ");
        $stmt->execute([$tournament_id, $_SESSION['user_id']]);
        $tournament = $stmt->fetch();

        if (!$tournament) {
            throw new Exception("Invalid tournament or unauthorized access");
        }

        // Verify both teams are registered and approved
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM tournament_teams 
            WHERE tournament_id = ? 
            AND team_id IN (?, ?)
            AND status = 'approved'
        ");
        $stmt->execute([$tournament_id, $team1_id, $team2_id]);
        if ($stmt->fetchColumn() !== 2) {
            throw new Exception("One or both teams are not approved for this tournament");
        }

        // Check for existing matches at the same time
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM matches 
            WHERE tournament_id = ? 
            AND match_date = ?
            AND match_status != 'cancelled'
        ");
        $stmt->execute([$tournament_id, $match_date]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception("Another match is already scheduled at this time");
        }

        // Create the match
        $stmt = $pdo->prepare("
            INSERT INTO matches (
                tournament_id,
                team1_id,
                team2_id,
                match_date,
                venue,
                match_status
            ) VALUES (?, ?, ?, ?, ?, 'scheduled')
        ");

        $stmt->execute([
            $tournament_id,
            $team1_id,
            $team2_id,
            $match_date,
            $venue
        ]);

        $_SESSION['success'] = "Match scheduled successfully";
        header("Location: " . BASE_URL . "/tournaments/matches/schedule.php?id=" . $tournament_id);
        exit();

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: " . BASE_URL . "/tournaments/matches/schedule.php?id=" . $tournament_id);
        exit();
    }
} 