<?php
require_once __DIR__ . '/connection.php';
mysqli_select_db($conn, $dbName);

/* ================= Parameter ================= */
$cari    = trim($_GET['cari'] ?? '');
$huruf   = trim($_GET['huruf'] ?? '');
$sort    = ($_GET['sort'] ?? '') === 'asc' ? 'asc' : 'desc';
$perPage = isset($_GET['per']) ? max(5, min(50, (int) $_GET['per'])) : 10;
$page    = max(1, (int) ($_GET['page'] ?? 1));

/* ================= WHERE dinamis ================= */
$where = []; $params = []; $types = '';
if ($cari !== '') { $where[] = "nama_lengkap LIKE ?"; $params[] = "%$cari%"; $types .= 's'; }
if ($huruf !== '') { $where[] = "nilai_huruf = ?";  $params[] = $huruf;      $types .= 's'; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

/* ================= COUNT ================= */
$stmt = $conn->prepare("SELECT COUNT(*) FROM nilai_mhsw $whereSql");
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$total = (int) $stmt->get_result()->fetch_row()[0];
$stmt->close();

$pages  = max(1, (int) ceil($total / $perPage));
$page   = min($page, $pages);
$offset = ($page - 1) * $perPage;

/* ================= DATA ================= */
$stmt = $conn->prepare("SELECT nim, nama_lengkap, nilai_akhir, nilai_huruf, predikat, foto, created_at, modified_at
        FROM nilai_mhsw $whereSql
        ORDER BY nilai_akhir " . ($sort === 'asc' ? 'ASC' : 'DESC') . "
        LIMIT ? OFFSET ?");
$stmt->bind_param($types . 'ii', ...array_merge($params, [$perPage, $offset]));
$stmt->execute();
$result = $stmt->get_result();

$flash  = $_GET['msg'] ?? '';
$ferror = $_GET['err'] ?? '';

function qs(array $o) { return '?' . http_build_query(array_merge($_GET, $o)); }
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Praktikum PHP | Data Nilai</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#222529;          /* halaman abu-abu gelap */
  --card:#1d1f24;        /* kartu lebih gelap dari halaman */
  --card2:#25282d;       /* strip header tabel */
  --line:#2f3237;
  --text:#e9eaec;
  --muted:#8f97a1;
  --blue:#3b82f6;
  --blue-d:#2563eb;
  --red:#ef4444;
}
body{
  background:var(--bg); color:var(--text);
  font-family:"Segoe UI",Tahoma,system-ui,sans-serif;
  min-height:100vh; display:flex; flex-direction:column;
  font-size:14px; line-height:1.4;
}
.wrap{width:100%; padding:0 16px; display:flex; flex-direction:column; flex:1; min-height:100vh}

/* ===== Header atas ===== */
.page-head{display:flex; align-items:center; justify-content:space-between; padding:25px 8px 25px}
.page-head h1{font-size:26px; font-weight:400; color:#eeeef0}
.page-head a{color:var(--muted); font-size:14px; text-decoration:none}
.page-head a:hover{color:#c9cdd2}

/* ===== Kartu ===== */
.card{background:var(--card); border:1px solid #2c2f34; border-radius:8px; overflow:hidden}

/* ===== Head kartu ===== */
.card-head{display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;
  padding:17px 18px; border-bottom:1px solid var(--line)}
.card-head h3{font-size:15px; font-weight:400; color:#eff0f2}
.toolbar{display:flex; align-items:center; gap:8px; flex-wrap:wrap}

.search{position:relative}
.search input{width:162px; background:#16181c; border:1px solid #35393f; border-radius:7px;
  color:var(--text); padding:8px 12px 8px 33px; font-size:13px; font-family:inherit}
.search input::placeholder{color:#7a828c}
.search input:focus{outline:none; border-color:var(--blue)}
.search svg{position:absolute; left:11px; top:50%; transform:translateY(-50%); width:15px; height:15px; stroke:#9aa2ab; pointer-events:none}

select{background:#16181c; border:1px solid #35393f; border-radius:7px; color:#c6ccd4;
  padding:8px 11px; font-size:13px; font-family:inherit; cursor:pointer}
select:focus{outline:none; border-color:var(--blue)}

.btn{border:none; border-radius:7px; padding:8px 15px; font-size:13px; font-weight:500; cursor:pointer;
  display:inline-flex; align-items:center; gap:7px; text-decoration:none; font-family:inherit}
.btn-cari{background:#3e424a; color:#e8eaec}
.btn-cari:hover{background:#474c55}
.btn-tambah{background:var(--blue); color:#fff}
.btn-tambah:hover{background:var(--blue-d)}
.btn-tambah .plus{font-size:15px}

/* ===== Tabel ===== */
table{width:100%; border-collapse:collapse}
thead th{padding:15px 14px; text-align:left; font-size:13.5px; font-weight:700; color:#f2f3f5;
  border-bottom:0; background:var(--card2); white-space:nowrap}
thead th:first-child{padding-left:22px}
thead th:last-child{text-align:right; padding-right:22px}
th a{color:var(--blue); text-decoration:none; font-weight:700; display:inline-flex; align-items:center; gap:6px}
th a .updown{font-size:14px}

tbody td{padding:14px; font-size:14px; color:#e5e7ea; white-space:nowrap}
tbody tr + tr td{border-top:1px solid #292d33}
tbody tr:hover{background:rgba(255,255,255,.02)}
tbody td:first-child{padding-left:22px}
tbody td:last-child{padding-right:22px; text-align:right}

.user{display:flex; align-items:center; gap:10px}
.avatar{width:28px; height:28px; border-radius:50%; object-fit:cover; display:block; flex:none}
.avatar-init{width:28px; height:28px; border-radius:50%; background:linear-gradient(135deg,#4f7ef7,#8b5cf6);
  display:flex; align-items:center; justify-content:center; font-size:10px; font-weight:700; color:#fff; flex:none}

.nilai{font-variant-numeric:tabular-nums}

.aksi{display:flex; gap:7px; justify-content:flex-end}
.aksi a{width:28px; height:28px; border-radius:6px; display:inline-flex; align-items:center; justify-content:center; text-decoration:none; cursor:pointer}
.a-edit{background:#17191d; border:1px solid var(--blue)}
.a-edit svg{width:12px; height:12px; fill:#5b9bff}
.a-del{background:#17191d; border:1px solid var(--red)}
.a-del svg{width:12px; height:12px; fill:#ff6b6b}

.empty-row{padding:34px 18px !important; text-align:center; color:var(--muted)}

.card-foot{display:flex; align-items:center; justify-content:space-between; padding:13px 18px;
  border-top:1px solid var(--line); color:#c9cdd3; font-size:12.5px}
.card-foot .pg a{color:#c6ccd4; text-decoration:none; padding:0 5px}
.card-foot .pg a.on{color:#fff; font-weight:600}

.flash{background:#1c3324; border:1px solid #2f5c3f; color:#7ddc9b; border-radius:6px; padding:10px 16px; font-size:13.5px; margin-bottom:16px}
.flash.err{background:#3a1f22; border-color:#7a3b40; color:#ff9a9e}

.foot{margin-top:auto; background:var(--card); border-top:1px solid var(--line);
  padding:14px 16px; display:flex; align-items:center; justify-content:space-between;
  color:#c3c7cc; font-size:14px}

/* ============ MODAL ============ */
.overlay{position:fixed; inset:0; background:rgba(8,9,11,.65); backdrop-filter:blur(2px);
  display:none; align-items:center; justify-content:center; z-index:50; padding:20px}
.overlay.show{display:flex}
.modal{width:100%; max-width:460px; background:var(--card); border:1px solid #2e3138;
  border-radius:10px; box-shadow:0 20px 60px rgba(0,0,0,.5); animation:pop .18s ease}
@keyframes pop{from{transform:translateY(14px) scale(.98); opacity:0} to{transform:none; opacity:1}}
.modal-head{display:flex; align-items:center; justify-content:space-between; padding:16px 20px; border-bottom:1px solid var(--line)}
.modal-head h3{font-size:15.5px; font-weight:600}
.modal-close{background:none; border:none; color:var(--muted); font-size:20px; cursor:pointer; line-height:1}
.modal-close:hover{color:var(--text)}
.modal-body{padding:20px}
.modal-body label{display:block; font-size:12.5px; color:#aab2bb; margin:12px 0 5px}
.modal-body label:first-child{margin-top:0}
.modal-body input[type=text],.modal-body input[type=number],.modal-body select{width:100%;
  background:#17191d; border:1px solid #3a3f46; border-radius:7px; color:var(--text); padding:9px 12px; font-size:13.5px; font-family:inherit}
.modal-body input:focus,.modal-body select:focus{outline:none; border-color:var(--blue)}
.row2{display:grid; grid-template-columns:1fr 1fr; gap:12px}
.preview{display:flex; align-items:center; gap:12px; margin-top:6px}
.preview img{width:46px; height:46px; border-radius:50%; object-fit:cover}
.file-btn{position:relative; overflow:hidden; flex:1}
.file-btn .chip{background:#17191d; border:1px dashed #3a3f46; border-radius:7px; padding:9px 12px; font-size:12.5px; color:#8f97a1; text-align:center}
.file-btn input[type=file]{position:absolute; inset:0; opacity:0; cursor:pointer; width:100%}
.hint{font-size:11.5px; color:#6f7885; margin-top:6px}
.modal-actions{display:flex; gap:10px; margin-top:20px}
.btn-back{background:#31353c; color:#e8eaec; flex:none}
.btn-save{background:var(--blue); color:#fff; flex:1}
.btn-save:hover{background:var(--blue-d)}
</style>
</head>
<body>
<div class="wrap">

  <div class="page-head">
    <h1>Data Nilai</h1>
    <a href="index.php">Data Nilai</a>
  </div>

  <?php if ($flash): ?><div class="flash"><?= htmlspecialchars($flash) ?></div><?php endif; ?>
  <?php if ($ferror): ?><div class="flash err"><?= htmlspecialchars($ferror) ?></div><?php endif; ?>

  <div class="card">
    <div class="card-head">
      <h3>Daftar Nilai Mahasiswa</h3>
      <form class="toolbar" method="get" action="index.php">
        <input type="hidden" name="sort" value="<?= $sort ?>" />
        <div class="search">
          <svg viewBox="0 0 24 24" fill="none" stroke-width="2.2"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
          <input type="text" name="cari" placeholder="Cari nama lengkap..." value="<?= htmlspecialchars($cari) ?>" />
        </div>
        <select name="huruf">
          <option value="" >Semua nilai huruf</option>
          <?php foreach (['A','B','C','D','E'] as $h): ?>
            <option value="<?= $h ?>" <?= $huruf === $h ? 'selected' : '' ?>><?= $h ?></option>
          <?php endforeach; ?>
        </select>
        <select name="per">
          <?php foreach ([5,10,25,50] as $n): ?>
            <option value="<?= $n ?>" <?= $perPage === $n ? 'selected' : '' ?>><?= $n ?> / halaman</option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn-cari" type="submit">Cari</button>
        <button class="btn btn-tambah" type="button" onclick="openModal()"><span class="plus">+</span> Tambah Data</button>
      </form>
    </div>

    <table>
      <thead>
        <tr>
          <th>NIM</th>
          <th>Nama Lengkap</th>
          <th><a href="<?= qs(['sort' => $sort === 'asc' ? 'desc' : 'asc']) ?>">Nilai Akhir <span class="updown">&#8597;</span></a></th>
          <th>Nilai Huruf</th>
          <th>Predikat</th>
          <th>Tgl dibuat</th>
          <th>Tgl diubah</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
      <?php if ($result->num_rows === 0): ?>
        <tr><td colspan="8" class="empty-row">Tidak ada data.</td></tr>
      <?php endif; ?>
      <?php while ($row = $result->fetch_assoc()): ?>
        <?php
          $t1 = strtotime($row['created_at']) ?: time();
          $t2 = strtotime($row['modified_at']) ?: time();
          $fotoFile = $row['foto'] ?? '';
          $fotoPath = ($fotoFile && is_file(__DIR__ . '/uploads/' . $fotoFile)) ? 'uploads/' . $fotoFile : '';
        ?>
        <tr>
          <td><?= htmlspecialchars($row['nim']) ?></td>
          <td>
            <span class="user">
              <?php if ($fotoPath): ?>
                <img class="avatar" src="<?= htmlspecialchars($fotoPath) ?>" alt="" />
              <?php else: ?>
                <span class="avatar-init"><?= htmlspecialchars(strtoupper(mb_substr($row['nama_lengkap'],0,2))) ?></span>
              <?php endif; ?>
              <?= htmlspecialchars($row['nama_lengkap']) ?>
            </span>
          </td>
          <td class="nilai"><?= number_format((float)$row['nilai_akhir'], 2) ?></td>
          <td><?= htmlspecialchars($row['nilai_huruf'] ?? '') ?></td>
          <td><?= htmlspecialchars($row['predikat'] ?? '') ?></td>
          <td><?= date('Y-m-d H:i:s', $t1) ?></td>
          <td><?= date('Y-m-d H:i:s', $t2) ?></td>
          <td>
            <div class="aksi">
              <!-- Data-baris dibawa di attribute data-*, dipakai modal edit -->
              <a class="a-edit" title="Edit"
                 data-nim       ="<?= htmlspecialchars($row['nim']) ?>"
                 data-nama      ="<?= htmlspecialchars($row['nama_lengkap']) ?>"
                 data-nilai     ="<?= htmlspecialchars($row['nilai_akhir']) ?>"
                 data-huruf     ="<?= htmlspecialchars($row['nilai_huruf'] ?? '') ?>"
                 data-predikat  ="<?= htmlspecialchars($row['predikat'] ?? '') ?>"
                 data-foto      ="<?= htmlspecialchars($fotoPath) ?>"
                 onclick="openModal(true, this)">
                <svg viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.8 9.94l-3.75-3.75L3 17.25zM20.7 7.04a.996.996 0 0 0 0-1.41l-2.34-2.34a.996.996 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
              </a>
              <a class="a-del" href="delete_data.php?nim=<?= urlencode($row['nim']) ?>"
                 onclick="return confirm('Hapus data NIM <?= htmlspecialchars($row['nim']) ?>?')" title="Hapus">
                <svg viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8a2 2 0 0 0 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
              </a>
            </div>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>

    <div class="card-foot">
      <?php if ($total > 0): ?>
        Menampilkan <?= $offset + 1 ?>&ndash;<?= min($offset + $perPage, $total) ?> dari <?= $total ?> data
      <?php else: ?>
        Menampilkan 0 dari 0 data
      <?php endif; ?>
      <div class="pg">
        <?php if ($page > 1): ?><a href="<?= qs(['page' => $page-1]) ?>">&lsaquo;</a><?php endif; ?>
        <?php for ($i = max(1, $page-2); $i <= min($pages, $page+2); $i++): ?>
          <a class="<?= $i === $page ? 'on' : '' ?>" href="<?= qs(['page' => $i]) ?>"><?= $i ?></a>
        <?php endfor; ?>
        <?php if ($page < $pages): ?><a href="<?= qs(['page' => $page+1]) ?>">&rsaquo;</a><?php endif; ?>
      </div>
    </div>
  </div>

  <div class="foot">
    <div style="padding-left:14px">&copy; <?= date('Y') ?> UTS. All rights reserved.</div>
    <div style="padding-right:14px">UTS-RAFI NUR FATTAH-3202416058</div>
  </div>
</div>

<!-- ================= MODAL TAMBAH / EDIT ================= -->
<div class="overlay" id="overlay" onclick="if(event.target===this)closeModal()">
  <div class="modal">
    <div class="modal-head">
      <h3 id="modalTitle">Tambah Data</h3>
      <button class="modal-close" onclick="closeModal()">&times;</button>
    </div>
    <div class="modal-body">
      <form id="f" action="insert_data.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="mode_edit" id="mode_edit" value="0" />
        <input type="hidden" name="foto_lama" id="foto_lama" value="" />

        <label for="nim">NIM</label>
        <input type="text" name="nim" id="nim" maxlength="10" required />

        <label for="nama_lengkap">Nama Lengkap</label>
        <input type="text" name="nama_lengkap" id="nama_lengkap" maxlength="50" required />

        <label for="nilai_akhir">Nilai Akhir (0 - 100)</label>
        <input type="number" name="nilai_akhir" id="nilai_akhir" step="0.01" min="0" max="100" required />

        <div class="row2">
          <div>
            <label for="nilai_huruf">Nilai Huruf</label>
            <select name="nilai_huruf" id="nilai_huruf">
              <option value="">- pilih -</option>
              <option>A</option><option>B</option><option>C</option><option>D</option><option>E</option>
            </select>
          </div>
          <div>
            <label for="predikat">Predikat</label>
            <input type="text" name="predikat" id="predikat" maxlength="20" />
          </div>
        </div>

        <label>Foto (avatar)</label>
        <div class="preview">
          <img id="pv" src="" style="display:none" alt="" />
          <div class="file-btn" style="flex:1">
            <div class="chip" id="chip">Pilih gambar (JPG / PNG, maks 2MB)</div>
            <input type="file" name="foto" id="foto" accept="image/png,image/jpeg,image/webp" />
          </div>
        </div>
        <div class="hint">Klik kotak untuk memilih gambar. Kosongkan bila tidak ingin mengganti.</div>

        <div class="modal-actions">
          <button class="btn btn-back" type="button" onclick="closeModal()">Batal</button>
          <button class="btn btn-save" type="submit">SAVE</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
const overlay = document.getElementById('overlay');
const f = document.getElementById('f');
const pv = document.getElementById('pv');
const chip = document.getElementById('chip');

function openModal(edit = false, src = null){
  // reset
  f.reset();
  pv.style.display = 'none';
  pv.removeAttribute('src');
  chip.textContent = 'Pilih gambar (JPG / PNG, maks 2MB)';

  if (edit && src){
    // ---- mode EDIT: isi dari data baris ----
    document.getElementById('modalTitle').textContent = 'Edit Data';
    document.getElementById('mode_edit').value = '1';
    document.getElementById('nim').value = src.dataset.nim;
    document.getElementById('nim').readOnly = true;
    document.getElementById('nim').style.opacity = .6;
    document.getElementById('nama_lengkap').value = src.dataset.nama;
    document.getElementById('nilai_akhir').value = src.dataset.nilai;
    document.getElementById('nilai_huruf').value = src.dataset.huruf || '';
    document.getElementById('predikat').value = src.dataset.predikat || '';
    document.getElementById('foto_lama').value = src.dataset.foto.replace(/^uploads\//, '');
    if (src.dataset.foto){
      pv.src = src.dataset.foto;
      pv.style.display = '';
      chip.textContent = 'Klik untuk ganti foto';
    }
  } else {
    // ---- mode TAMBAH ----
    document.getElementById('modalTitle').textContent = 'Tambah Data';
    document.getElementById('mode_edit').value = '0';
    document.getElementById('nim').readOnly = false;
    document.getElementById('nim').style.opacity = 1;
    document.getElementById('foto_lama').value = '';
  }
  overlay.classList.add('show');
}
function closeModal(){ overlay.classList.remove('show'); }

// preview saat file dipilih
document.getElementById('foto').addEventListener('change', e => {
  if (e.target.files[0]){
    pv.src = URL.createObjectURL(e.target.files[0]);
    pv.style.display = '';
    chip.textContent = e.target.files[0].name;
  }
});

// tutup dengan ESC
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });
</script>
</body>
</html>
<?php $conn->close(); ?>
