<?php
require_once __DIR__ . '/connection.php';
mysqli_select_db($conn, $dbName);

$nim      = trim($_POST['nim'] ?? '');
$nama     = trim($_POST['nama_lengkap'] ?? '');
$nilai    = $_POST['nilai_akhir'] ?? '';
$huruf    = trim($_POST['nilai_huruf'] ?? '') ?: null;
$predikat = trim($_POST['predikat'] ?? '') ?: null;
$modeEdit = ($_POST['mode_edit'] ?? '0') === '1';
$fotoLama = trim($_POST['foto_lama'] ?? '');

function back($msg, $err = false)
{
  header("Location: index.php?" . ($err ? 'err' : 'msg') . "=" . urlencode($msg));
  exit;
}

if ($nim === '' || strlen($nim) > 10 || $nama === '' || !is_numeric($nilai)) {
  back('Input tidak valid. Periksa NIM, nama, dan nilai.', true);
}
$nilai = (float) $nilai;

/* ================= Upload foto ================= */
$fotoName = null;
$hapusLama = false;

if (!empty($_FILES['foto']['name']) && is_uploaded_file($_FILES['foto']['tmp_name'] ?? '')) {
  $f   = $_FILES['foto'];
  $ok  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
  $info = @getimagesize($f['tmp_name']);

  if (!$info || !isset($ok[$info['mime']])) {
    back('Format gambar harus JPG / PNG / WEBP.', true);
  }
  if ($f['size'] > 2 * 1024 * 1024) {
    back('Ukuran gambar maksimal 2MB.', true);
  }

  if (!is_dir(__DIR__ . '/uploads')) {
    mkdir(__DIR__ . '/uploads', 0777, true);
  }
  $ext      = $ok[$info['mime']];
  $fotoName = 'foto_' . $nim . '_' . time() . '.' . $ext;

  if (!move_uploaded_file($f['tmp_name'], __DIR__ . '/uploads/' . $fotoName)) {
    back('Gagal menyimpan gambar.', true);
  }
  $hapusLama = true; // foto lama diganti
}

if ($modeEdit) {
  if ($fotoName !== null) {
    $sql  = "UPDATE nilai_mhsw SET nama_lengkap=?, nilai_akhir=?, nilai_huruf=?, predikat=?, foto=?, modified_at=NOW() WHERE nim=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdssss", $nama, $nilai, $huruf, $predikat, $fotoName, $nim);
  } else {
    $sql  = "UPDATE nilai_mhsw SET nama_lengkap=?, nilai_akhir=?, nilai_huruf=?, predikat=?, modified_at=NOW() WHERE nim=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sdsss", $nama, $nilai, $huruf, $predikat, $nim);
  }
} else {
  $sql  = "INSERT INTO nilai_mhsw (nim, nama_lengkap, nilai_akhir, nilai_huruf, predikat, foto) VALUES (?, ?, ?, ?, ?, ?)";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ssdsss", $nim, $nama, $nilai, $huruf, $predikat, $fotoName);
}

if ($stmt->execute()) {
  $stmt->close();
  $conn->close();
  // hapus foto lama kalau diganti
  if ($hapusLama && $fotoLama !== '' && is_file(__DIR__ . '/uploads/' . $fotoLama)) {
    @unlink(__DIR__ . '/uploads/' . $fotoLama);
  }
  back($modeEdit ? 'Data berhasil diperbarui.' : 'Data berhasil ditambahkan.');
}

$err = $stmt->error;
$stmt->close();
$conn->close();
back('Error: ' . $err, true);
