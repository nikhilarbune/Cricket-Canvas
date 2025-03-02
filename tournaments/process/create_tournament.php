<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/config.php';

// Check if user is logged in and is an organizer
if (!isLoggedIn() || getUserRole() != 'organizer') {
    header("Location: " . BASE_URL . "/auth/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Validate dates
        $start_date = new DateTime($_POST['start_date']);
        $end_date = new DateTime($_POST['end_date']);
        $registration_deadline = new DateTime($_POST['registration_deadline']);
        $today = new DateTime();

        // Date validation checks
        if ($registration_deadline < $today) {
            throw new Exception("Registration deadline cannot be in the past");
        }
        if ($start_date < $registration_deadline) {
            throw new Exception("Start date must be after registration deadline");
        }
        if ($end_date < $start_date) {
            throw new Exception("End date must be after start date");
        }

        // Validate team limits
        $min_teams = (int)$_POST['min_teams'];
        $max_teams = (int)$_POST['max_teams'];
        
        if ($min_teams < 4 || $min_teams > 32) {
            throw new Exception("Minimum teams must be between 4 and 32");
        }
        if ($max_teams < $min_teams) {
            throw new Exception("Maximum teams must be greater than minimum teams");
        }
        if ($max_teams > 32) {
            throw new Exception("Maximum teams cannot exceed 32");
        }

        // Validate amounts
        $entry_fee = (float)$_POST['entry_fee'];
        $prize_pool = (float)$_POST['prize_pool'];
        
        if ($entry_fee < 0 || $prize_pool < 0) {
            throw new Exception("Fees and prize pool cannot be negative");
        }

        // Insert tournament
        $stmt = $pdo->prepare("
            INSERT INTO tournaments (
                organizer_id,
                tournament_name,
                description,
                start_date,
                end_date,
                registration_deadline,
                max_teams,
                min_teams,
                format,
                status,
                venue,
                city,
                entry_fee,
                prize_pool,
                rules
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $result = $stmt->execute([
            $_SESSION['user_id'],
            $_POST['tournament_name'],
            $_POST['description'],
            $_POST['start_date'],
            $_POST['end_date'],
            $_POST['registration_deadline'],
            $max_teams,
            $min_teams,
            $_POST['format'],
            $_POST['status'], // 'draft' or 'open' from form
            $_POST['venue'],
            $_POST['city'],
            $entry_fee,
            $prize_pool,
            $_POST['rules']
        ]);

        if ($result) {
            $_SESSION['success'] = "Tournament " . ($_POST['status'] === 'draft' ? "saved as draft" : "published") . " successfully!";
            header("Location: " . BASE_URL . "/tournaments/manage.php");
            exit();
        } else {
            throw new Exception("Failed to create tournament");
        }

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: " . BASE_URL . "/tournaments/create.php");
        exit();
    }
} else {
    header("Location: " . BASE_URL . "/tournaments/create.php");
    exit();
} 