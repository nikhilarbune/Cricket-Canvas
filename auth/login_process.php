<?php
session_start();
require_once '../config/database.php';
require_once '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role'];
        
        // Redirect based on role
        if ($user['role'] == 'admin') {
            header("Location: " . BASE_URL . "/admin/index.php");
        } else if ($user['role'] == 'organizer') {
            header("Location: " . BASE_URL . "/dashboard/organizer_dashboard.php");
        } else {
            header("Location: " . BASE_URL . "/dashboard/player_dashboard.php");
        }
        exit();
    } else {
        $_SESSION['error'] = "Invalid email or password";
        header("Location: login.php");
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}
?> 