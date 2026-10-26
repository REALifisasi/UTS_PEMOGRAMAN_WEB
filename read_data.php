<?php
require_once __DIR__ . '/connection.php';
mysqli_select_db($conn, $dbName);

$sql = "SELECT nim, nama_lengkap, nilai_akhir FROM nilai_mhsw ORDER BY nim DESC";
$result = mysqli_query($conn, $sql);

if (!$result) {
  die("Query failed: " . mysqli_error($conn));
}

if (mysqli_num_rows($result) > 0) {
  while ($row = mysqli_fetch_assoc($result)) {
    echo "NIM: " . htmlspecialchars($row["nim"]) . " - Nama: " . htmlspecialchars($row["nama_lengkap"]) . " - Nilai Akhir: " . htmlspecialchars($row["nilai_akhir"]) . "<br>";
  }
} else {
  echo "0 results";
}

mysqli_close($conn);
?>
