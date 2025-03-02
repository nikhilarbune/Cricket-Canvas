<?php
// Base URL configuration
define('BASE_URL', 'http://localhost/cricketcanvas');

// Common functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUserRole() {
    return $_SESSION['role'] ?? null;
}
?> 