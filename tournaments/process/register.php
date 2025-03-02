<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/tournaments/list.php");
    exit();
}

try {
    // Get and validate input
    $tournament_id = $_POST['tournament_id'] ?? null;
    $team_id = $_POST['team_id'] ?? null;
    $terms_accepted = isset($_POST['terms_accepted']);

    if (!$tournament_id || !$team_id || !$terms_accepted) {
        throw new Exception("All fields are required");
    }

    // Start transaction
    $pdo->beginTransaction();

    // Get tournament details
    $stmt = $pdo->prepare("
        SELECT t.*, 
               (SELECT COUNT(*) FROM tournament_teams WHERE tournament_id = t.tournament_id AND status = 'approved') as registered_teams
        FROM tournaments t
        WHERE t.tournament_id = ? AND t.status = 'open'
    ");
    $stmt->execute([$tournament_id]);
    $tournament = $stmt->fetch();

    if (!$tournament) {
        throw new Exception("Tournament not found or registration closed");
    }

    // Check if tournament is full
    if ($tournament['registered_teams'] >= $tournament['max_teams']) {
        throw new Exception("Tournament is full");
    }

    // Verify team ownership and eligibility
    $stmt = $pdo->prepare("
        SELECT t.*, 
               (SELECT COUNT(*) FROM team_members WHERE team_id = t.team_id AND status = 'approved') as member_count
        FROM teams t
        WHERE t.team_id = ? AND t.captain_id = ? AND t.team_status = 'active'
    ");
    $stmt->execute([$team_id, $_SESSION['user_id']]);
    $team = $stmt->fetch();

    if (!$team) {
        throw new Exception("Team not found or you're not the captain");
    }

    // Check if team is already registered
    $stmt = $pdo->prepare("
        SELECT * FROM tournament_teams 
        WHERE tournament_id = ? AND team_id = ?
    ");
    $stmt->execute([$tournament_id, $team_id]);
    if ($stmt->fetch()) {
        throw new Exception("Team is already registered for this tournament");
    }

    // Create tournament registration record
    $stmt = $pdo->prepare("
        INSERT INTO tournament_teams (
            tournament_id,
            team_id,
            status,
            payment_status,
            registration_date
        ) VALUES (?, ?, ?, ?, NOW())
    ");

    // If tournament has entry fee, set payment_status as pending
    $status = $tournament['entry_fee'] > 0 ? 'pending' : 'approved';
    $payment_status = $tournament['entry_fee'] > 0 ? 'pending' : 'completed';

    $stmt->execute([
        $tournament_id,
        $team_id,
        $status,
        $payment_status
    ]);

    $registration_id = $pdo->lastInsertId();

    // Commit transaction
    $pdo->commit();

    // Store registration info in session for confirmation page
    $_SESSION['registration'] = [
        'tournament_id' => $tournament_id,
        'team_id' => $team_id,
        'entry_fee' => $tournament['entry_fee'],
        'tournament_name' => $tournament['tournament_name'],
        'team_name' => $team['team_name']
    ];

    // After successful registration
    if ($tournament['entry_fee'] > 0) {
        // Redirect to payment page
        header("Location: " . BASE_URL . "/tournaments/payment/submit.php?tournament_id=" . $tournament_id . "&team_id=" . $team_id);
    } else {
        // Show confirmation page
        header("Location: " . BASE_URL . "/tournaments/confirmation.php?tournament_id=" . $tournament_id . "&team_id=" . $team_id);
    }
    exit();

} catch (Exception $e) {
    // Rollback transaction if error occurs
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $_SESSION['error'] = $e->getMessage();
    header("Location: " . BASE_URL . "/tournaments/register.php?id=" . ($tournament_id ?? ''));
    exit();
} 