<?php
/*
 * Nothing to say about it LOL :). Just a logout cleaner.
 */

session_start();

// Unset all stored session variables and destroy the session cookie
$_SESSION = [];


if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// redirect to home page
header("Location: index.php");
exit;
