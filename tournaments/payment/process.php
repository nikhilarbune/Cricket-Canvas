<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/dashboard/player_dashboard.php");
    exit();
}

try {
    $tournament_id = $_POST['tournament_id'];
    $team_id = $_POST['team_id'];
    $transaction_id = $_POST['transaction_id'];

    // Handle file upload
    if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Payment proof is required");
    }

    $upload_dir = '../../uploads/payment_proofs/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_extension = pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION);
    $proof_filename = uniqid() . '.' . $file_extension;

    if (!move_uploaded_file($_FILES['payment_proof']['tmp_name'], $upload_dir . $proof_filename)) {
        throw new Exception("Failed to upload payment proof");
    }

    // Update payment status
    $stmt = $pdo->prepare("
        UPDATE tournament_teams 
        SET 
            payment_status = 'submitted',
            transaction_id = ?,
            payment_proof = ?
        WHERE tournament_id = ? AND team_id = ?
    ");

    $stmt->execute([
        $transaction_id,
        $proof_filename,
        $tournament_id,
        $team_id
    ]);

    $_SESSION['success'] = "Payment submitted successfully! Awaiting verification.";
    header("Location: " . BASE_URL . "/dashboard/player_dashboard.php");
    exit();

} catch (Exception $e) {
    $_SESSION['error'] = "Failed to process payment: " . $e->getMessage();
    header("Location: " . BASE_URL . "/tournaments/payment/submit.php?tournament_id=" . $tournament_id . "&team_id=" . $team_id);
    exit();
}