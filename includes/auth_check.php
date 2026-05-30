<?php
require_once __DIR__ . '/../config/functions.php';

// Redirect logged-in users away from login/signup
function redirectIfLoggedIn(): void {
    if (isLoggedIn()) {
        redirect('/');
    }
}

// Check if user account is active
function checkUserActive(): void {
    if (isLoggedIn()) {
        $stmt = db()->prepare("SELECT is_active FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if (!$user || !$user['is_active']) {
            logoutUser();
            flash('error', 'Your account has been suspended.');
            redirect('/login.php');
        }
    }
}

checkUserActive();