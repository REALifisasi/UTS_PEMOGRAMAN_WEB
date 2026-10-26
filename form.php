<?php
require_once __DIR__ . '/connection.php';
mysqli_select_db($conn, $dbName);

// Mode edit jika ada ?nim=...
$edit = null;
$nimEdit = trim($_GET['nim'] ?? '');
if ($nimEdit !== '') {
  $stmt = $conn->prepare("SELECT nim, nama_lengkap, nilai_akhir, nilai_huruf, predikat, foto FROM nilai_mhsw WHERE nim = ?");
  $stmt->bind_param("s", $nimEdit);
  $stmt->execute();
  $edit = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  if (!$edit) die("Data tidak ditemukan. <a href='index.php'>Kembali</a>");
}
$fotoPath = ($edit && $edit['foto'] && is_file(__DIR__ . '/uploads/' . $edit['foto']))
  ? 'uploads/' . $edit['foto'] : '';
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title><?= $edit ? 'Edit' : 'Tambah' ?> Data Nilai</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#26282e; --panel:#1e2025; --panel2:#17191d; --line:#32363d;
  --text:#e8eaed; --muted:#8b939e; --blue:#3b82f6; --blue-d:#2563eb; --red:#ef4444;
}
body{background:var(--bg);color:var(--text);font-family:'Inter',system-ui,sans-serif;min-height:100vh;display:flex;flex-direction:column;font-size:14px}
.wrap{width:100%;max-width:1345px;margin:0 auto;padding:0 19px;display:flex;flex-direction:column;flex:1}
.page-head{display:flex;align-items:center;justify-content:space-between;padding:26px 0 24px}
.page-head h1{font-size:26px;font-weight:400;color:#f1f2f4}
.page-head a{color:var(--muted);text-decoration:none;font-size:14px}
.box{max-width:520px}
.card{background:var(--panel);border:1px solid #262b32;border-radius:10px;padding:24px}
h2{font-size:16px;font-weight:600;margin-bottom:18px}
label{display:block;font-size:12.5px;color:#aab2bb;margin:14px 0 5px}
input[type=text],input[type=number],select{width:100%;background:var(--panel2);border:1px solid #333941;border-radius:8px;color:var(--text);padding:9px 12px;font-size:13.5px;font-family:inherit}
input:focus,select:focus{outline:none;border-color:var(--blue)}
.preview{display:flex;align-items:center;gap:12px;margin-top:6px}
.preview img{width:52px;height:52px;border-radius:50%;object-fit:cover}
.hint{font-size:11.5px;color:#6f7885;margin-top:4px}
.file-btn{position:relative;overflow:hidden;flex:1}
.file-btn input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%}
.chip{background:var(--panel2);border:1px solid #333941;border-radius:8px;padding:8px 14px;font-size:13px;color:#b9c0ca;text-align:center}
.chip.picked{border-color:var(--blue);color:#cfe0ff}
.actions{display:flex;gap:10px;margin-top:22px}
.btn{border:none;border-radius:8px;padding:10px 18px;font-size:13.5px;font-weight:500;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:6px;text-decoration:none;font-family:inherit}
.btn-back{background:var(--panel2);border:1px solid var(--line);color:#c9cfd8}
.btn-save{background:var(--blue);color:#fff;flex:1}
.btn-save:hover{background:var(--blue-d)}
.foot{margin-top:auto;padding:22px 0 20px;display:flex;justify-content:space-between;color:#7d8590;font-size:13px}
</style>
</head>
<body>
<div class="wrap">

  <div class="page-head">
    <h1><?= $edit ? 'Edit Data' : 'Tambah Data' ?></h1>
    <a href="index.php">Data Nilai</a>
  </div>

  <div class="box" style="padding-bottom:8px">
    <div class="card">
      <h2><?= $edit ? 'Edit Daftar Nilai' : 'Daftar Nilai Mahasiswa' ?></h2>
      <form action="insert_data.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="mode_edit" value="<?= $edit ? '1' : '0' ?>" />
        <input type="hidden" name="foto_lama" value="<?= htmlspecialchars($edit['foto'] ?? '') ?>" />

        <label for="nim">NIM</label>
        <input type="text" name="nim" id="nim" maxlength="10" required
               value="<?= $edit ? htmlspecialchars($edit['nim']) : '' ?>" <?= $edit ? 'readonly' : '' ?> />

        <label for="nama_lengkap">Nama Lengkap</label>
        <input type="text" name="nama_lengkap" id="nama_lengkap" maxlength="50" required
               value="<?= $edit ? htmlspecialchars($edit['nama_lengkap']) : '' ?>" />

        <label for="nilai_akhir">Nilai Akhir (0 â€“ 100)</label>
        <input type="number" name="nilai_akhir" id="nilai_akhir" step="0.01" min="0" max="100" required
               value="<?= $edit ? htmlspecialchars($edit['nilai_akhir']) : '' ?>" />

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div>
            <label for="nilai_huruf">Nilai Huruf</label>
            <select name="nilai_huruf" id="nilai_huruf">
              <option value="">â€“ pilih â€“</option>
              <?php foreach (['A','B','C','D','E'] as $h): ?>
                <option value="<?= $h ?>" <?= ($edit && $edit['nilai_huruf'] === $h) ? 'selected' : '' ?>><?= $h ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label for="predikat">Predikat</label>
            <input type="text" name="predikat" id="predikat" maxlength="20"
                   value="<?= $edit ? htmlspecialchars($edit['predikat'] ?? '') : '' ?>" />
          </div>
        </div>

        <label>Foto (avatar)</label>
        <div class="preview">
          <img id="pv" src="<?= $fotoPath ? htmlspecialchars($fotoPath) : '' ?>"
               style="<?= $fotoPath ? '' : 'display:none' ?>" alt="" />
          <div class="file-btn" style="flex:1">
            <div class="chip" id="chip"><?= $edit && $edit['foto'] ? htmlspecialchars($edit['foto']) : 'Pilih gambar (JPG / PNG, maks 2MB)' ?></div>
            <input type="file" name="foto" id="foto" accept="image/png,image/jpeg,image/webp"
                   onchange="pv.src=URL.createObjectURL(this.files[0]); pv.style.display=''; chip.textContent=this.files[0].name; chip.classList.add('picked')" />
          </div>
        </div>
        <div class="hint">Klik kotak di atas untuk memilih gambar. Kosongkan bila tidak ingin mengganti.</div>

        <div class="actions">
          <a class="btn btn-back" href="index.php">Batal</a>
          <button class="btn btn-save" type="submit">SAVE</button>
        </div>
      </form>
    </div>
  </div>

  <div class="foot">
    <div>&copy; <?= date('Y') ?> UTS. All rights reserved.</div>
    <div>UTS-RAFI NUR FATTAH-3202416058</div>
  </div>
</div>

<script>
const chip = document.getElementById('chip');
const cls = chip.className;
</script>
</body>
</html>
<?php $conn->close(); ?>

