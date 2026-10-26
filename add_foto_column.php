<?php
require_once __DIR__ . '/connection.php';
mysqli_select_db($conn, $dbName);

// Cek dulu apakah kolom foto sudah ada (MariaDB tidak dukung ADD COLUMN IF NOT EXISTS)
$ada = false;
$res = $conn->query("SHOW COLUMNS FROM nilai_mhsw LIKE 'foto'");
if ($res && $res->num_rows > 0) {
  $ada = true;
}

if ($ada) {
  echo "Kolom 'foto' sudah ada. <a href='index.php'>Kembali</a>";
} else {
  $sql = "ALTER TABLE nilai_mhsw ADD COLUMN foto VARCHAR(255) DEFAULT NULL AFTER predikat";
  if ($conn->query($sql) === TRUE) {
    echo "Kolom 'foto' berhasil ditambahkan. <a href='index.php'>Kembali</a>";
  } else {
    echo "Info: " . $conn->error . "<br><a href='index.php'>Kembali</a>";
  }
}
$conn->close();
