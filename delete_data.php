<?php
require_once __DIR__ . '/connection.php';
mysqli_select_db($conn, $dbName);

$nim = trim($_GET['nim'] ?? '');
if ($nim === '') {
  header("Location: index.php");
  exit;
}

// Ambil nama foto agar ikut dihapus dari folder uploads
$foto = null;
$stmt = $conn->prepare("SELECT foto FROM nilai_mhsw WHERE nim = ?");
$stmt->bind_param("s", $nim);
$stmt->execute();
$foto = $stmt->get_result()->fetch_row()[0] ?? null;
$stmt->close();

$stmt = $conn->prepare("DELETE FROM nilai_mhsw WHERE nim = ?");
$stmt->bind_param("s", $nim);

if ($stmt->execute()) {
  $msg = "Data NIM $nim berhasil dihapus.";
} else {
  $msg = "Gagal menghapus data: " . $stmt->error;
}
$stmt->close();
$conn->close();

if ($foto && is_file(__DIR__ . '/uploads/' . $foto)) {
  @unlink(__DIR__ . '/uploads/' . $foto);
}

header("Location: index.php?msg=" . urlencode($msg));
exit;
