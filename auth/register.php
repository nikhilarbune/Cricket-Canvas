<?php
require_once '../includes/header.php';
?>

<div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-6">
    <h2 class="text-2xl font-bold mb-6">Register</h2>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
            <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="register_process.php">
        <div class="mb-4">
            <label class="block text-gray-700 mb-2">Username</label>
            <input type="text" name="username" required 
                   class="w-full px-3 py-2 border rounded-lg">
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 mb-2">Email</label>
            <input type="email" name="email" required 
                   class="w-full px-3 py-2 border rounded-lg">
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 mb-2">Password</label>
            <input type="password" name="password" required 
                   class="w-full px-3 py-2 border rounded-lg">
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 mb-2">Role</label>
            <select name="role" required class="w-full px-3 py-2 border rounded-lg">
                <option value="player">Player</option>
                <option value="organizer">Organizer</option>
            </select>
        </div>
        <button type="submit" 
                class="w-full bg-blue-500 text-white py-2 rounded-lg hover:bg-blue-600">
            Register
        </button>
    </form>
</div>
