<?php

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'inventopro');

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    // Log the error for debugging, but don't expose sensitive info to the user
    error_log("Failed to connect to MySQL: " . $conn->connect_error);
    die("Database connection failed. Please try again later.");
}

// Set character set to UTF-8 for proper handling of various characters
$conn->set_charset("utf8mb4");

// You can optionally add a basic database/table creation check here if this is
// intended to be a setup script, but usually, schema creation is separate.
// For now, we assume the database and tables already exist.

?>