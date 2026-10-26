<?php
require_once __DIR__ . '/connection.php';
mysqli_select_db($conn, $dbName);
$result = $conn->query("SELECT nim, nama_lengkap, nilai_akhir, nilai_huruf, predikat, created_at, modified_at FROM nilai_mhsw");
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Data Nilai</title>
<style>
body{font-family:sans-serif;max-width:800px;margin:2rem auto;padding:0 1rem}
table{width:100%;border-collapse:collapse}
th,td{border:1px solid #ccc;padding:.4rem;text-align:left}
</style>
</head>
<body>
<h3>Data Nilai</h3>
<p><a href="form.php">+ Tambah Data</a></p>
<table>
<tr><th>NIM</th><th>Nama</th><th>Akhir</th><th>Huruf</th><th>Predikat</th><th>Created</th><th>Modified</th></tr>
<?php while ($row = $result->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($row['nim']) ?></td>
<td><?= htmlspecialchars($row['nama_lengkap']) ?></td>
<td><?= htmlspecialchars($row['nilai_akhir']) ?></td>
<td><?= htmlspecialchars($row['nilai_huruf']) ?></td>
<td><?= htmlspecialchars($row['predikat']) ?></td>
<td><?= htmlspecialchars($row['created_at']) ?></td>
<td><?= htmlspecialchars($row['modified_at']) ?></td>
</tr>
<?php endwhile; ?>
</table>
</body>
</html>
<?php $conn->close(); ?>
