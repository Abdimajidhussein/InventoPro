<?php
$servername = "localhost"; // XAMPP default server
$username = "root"; // Default XAMPP username
$password = ""; // Default XAMPP password (empty)
$database = "inventopro"; // Change to your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>