<?php
require_once __DIR__ . '/connection.php';
mysqli_select_db($conn, $dbName);

// sql to create table
$sql = "CREATE TABLE IF NOT EXISTS nilai_mhsw (
nim CHAR(10) PRIMARY KEY,
nama_lengkap VARCHAR(50) NOT NULL,
nilai_akhir DECIMAL(3,2) NOT NULL,
nilai_huruf CHAR(1),
predikat VARCHAR(20),
foto VARCHAR(255) DEFAULT NULL,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
modified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
  echo "Table created successfully";
} else {
  echo "Error creating table: " . $conn->error;
}

$conn->close();
?>
