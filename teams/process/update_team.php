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
    
    // Verify user is team captain
    $stmt = $pdo->prepare("SELECT captain_id FROM teams WHERE team_id = ?");
    $stmt->execute([$team_id]);
    $team = $stmt->fetch();

    if ($team['captain_id'] !== $_SESSION['user_id']) {
        $_SESSION['error'] = "You don't have permission to edit this team";
        header("Location: " . BASE_URL . "/teams/details.php?team_id=" . $team_id);
        exit();
    }

    try {
        // Handle logo upload if provided
        $logo = null;
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../../uploads/team_logos/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_extension = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png'];

            if (!in_array($file_extension, $allowed_extensions)) {
                throw new Exception("Invalid file type. Only JPG, JPEG, and PNG files are allowed.");
            }

            $logo = uniqid() . '.' . $file_extension;
            move_uploaded_file($_FILES['logo']['tmp_name'], $upload_dir . $logo);
        }

        // Update team information
        $stmt = $pdo->prepare("
            UPDATE teams 
            SET team_name = ?, 
                description = ?,
                home_ground = ?,
                city = ?,
                contact_email = ?,
                contact_phone = ?,
                max_members = ?
                " . ($logo ? ", logo = ?" : "") . "
            WHERE team_id = ?
        ");

        $params = [
            $_POST['team_name'],
            $_POST['description'],
            $_POST['home_ground'],
            $_POST['city'],
            $_POST['contact_email'],
            $_POST['contact_phone'],
            $_POST['max_members'],
        ];

        if ($logo) {
            $params[] = $logo;
        }
        $params[] = $team_id;

        $stmt->execute($params);

        $_SESSION['success'] = "Team details updated successfully";
        header("Location: " . BASE_URL . "/teams/details.php?team_id=" . $team_id);
        exit();

    } catch (Exception $e) {
        $_SESSION['error'] = "Failed to update team: " . $e->getMessage();
        header("Location: " . BASE_URL . "/teams/details.php?team_id=" . $team_id);
        exit();
    }
} 