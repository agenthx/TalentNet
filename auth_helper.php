<?php
/*
 * This file checks authentication, authorization and handles session expiry logic
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If the session is expired or the user is not logged in, it redirects to login
function require_login() {
    // Check if user_id exists in session
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }

    // Check for session expiry
    $timeout = 1800; // That's a 30 min in total
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
        // Session expired
        session_unset();
        session_destroy();
        header("Location: login.php?expired=1");
        exit;
    }

    // Update last activity time
    $_SESSION['last_activity'] = time();
}

// Checks the logged in user specific role
function require_role($allowed_roles) {
    require_login();

    $user_role = $_SESSION['user_role'] ?? '';

    $allowed_roles = (array)$allowed_roles;

    if (!in_array($user_role, $allowed_roles)) {
        http_response_code(403);
        include 'header.php';
        echo "<div class='empty-state text-center p-5 my-4'>
                <i class='bi bi-shield-lock display-6 d-block mb-3'></i>
                <h1 class='h4'>Access Denied</h1>
                <p class='mb-4'>You do not have permission to view this page.</p>
                <a class='btn btn-primary' href='index.php'>Return to Home</a>
              </div>";
        include 'footer.php';
        exit;
    }
}

// Helper to check if a user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Helper to get the current user's role
function get_user_role() {
    return $_SESSION['user_role'] ?? 'guest';
}
