<?php
// Database configuration
$host = '127.0.0.1'; // Your local XAMPP server
$db   = 'dbProj_job_portal'; // The exact database name from M1's script
$user = 'root'; // Default XAMPP MySQL username
$pass = ''; // Default XAMPP MySQL password is blank

// Data Source Name (DSN) - Tells PDO where to connect
$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

// Configure PDO options for better error handling and fetching
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw errors if something breaks
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch data as an associative array
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Enforce strict prepared statements
];

try {
    // Attempt to create the connection
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // If it fails, stop the page and show the error
    die("Database connection failed: " . $e->getMessage());
}
?>