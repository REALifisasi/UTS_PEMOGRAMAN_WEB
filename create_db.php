<?php
require_once __DIR__ . '/connection.php';

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS `$dbName`";
if ($conn->query($sql) === TRUE) {
  echo "Database created successfully";
} else {
  echo "Error creating database: " . $conn->error;
}

// Close connection
$conn->close();
?>
