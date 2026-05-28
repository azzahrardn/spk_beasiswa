<?php
require_once '../../config/database.php';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';

[$conn, $dbError] = getDB();
if ($dbError) {
    die('<div class="text-center mt-5"><h5 class="text-danger">Koneksi Database Gagal</h5></div>');
}

$idKriteria = isset($_GET['id_kriteria']) ? (int)$_GET['id_kriteria'] : null;

// ========== PROCESS POST (HANYA UNTUK SUB-KRITERIA) ==========
// Logika Kriteria sudah dipindah ke proses.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_sub'])) {
        $nama = trim($_POST['nama_sub_kriteria']);
        $nilaiStandar = (int)$_POST['nilai_standar'];
        $jenis = $_POST['jenis_faktor'];
        $idKriteriaSub = (int)$_POST['id_kriteria'];

        // FIX: nama_sub_kriteria diubah menjadi nama_sub sesuai database
        $stmt = mysqli_prepare($conn, "INSERT INTO sub_kriteria (id_kriteria, nama_sub, nilai_standar, jenis_faktor) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'isis', $idKriteriaSub, $nama, $nilaiStandar, $jenis);
        mysqli_stmt_execute($stmt);
        header("Location: " . $_SERVER['PHP_SELF'] . "?id_kriteria=" . $idKriteriaSub);
        exit;
    }

    if (isset($_POST['update_sub'])) {
        $id = (int)$_POST['id_sub'];
        $nama = trim($_POST['nama_sub_kriteria']);
        $nilaiStandar = (int)$_POST['nilai_standar'];
        $jenis = $_POST['jenis_faktor'];
        $refId = (int)$_POST['id_kriteria'];

        // FIX: nama_sub_kriteria diubah menjadi nama_sub sesuai database
        $stmt = mysqli_prepare($conn, "UPDATE sub_kriteria SET nama_sub = ?, nilai_standar = ?, jenis_faktor = ? WHERE id_sub = ?");
        mysqli_stmt_bind_param($stmt, 'sisi', $nama, $nilaiStandar, $jenis, $id);
        mysqli_stmt_execute($stmt);
        header("Location: " . $_SERVER['PHP_SELF'] . "?id_kriteria=" . $refId);
        exit;
    }

    if (isset($_POST['delete_sub'])) {
        $id = (int)$_POST['id_sub'];
        $refId = (int)$_POST['id_kriteria'];
        $stmt = mysqli_prepare($conn, "DELETE FROM sub_kriteria WHERE id_sub = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        header("Location: " . $_SERVER['PHP_SELF'] . "?id_kriteria=" . $refId);
        exit;
    }
}

// ========== FETCH DATA ==========
$kriteriaList = mysqli_query($conn, "SELECT * FROM kriteria ORDER BY id_kriteria ASC");
$totalBobot = 0;
$kriteriaData = [];
while ($row = mysqli_fetch_assoc($kriteriaList)) {
    $totalBobot += $row['bobot_persen'];
    $kriteriaData[] = $row;
}

$subKriteriaList = null;
$namaKriteriaSelected = '';
if ($idKriteria) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM kriteria WHERE id_kriteria = ?");
    mysqli_stmt_bind_param($stmt, 'i', $idKriteria);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $kriteriaSelected = mysqli_fetch_assoc($result);
    $namaKriteriaSelected = $kriteriaSelected['nama_kriteria'] ?? '';

    $stmt2 = mysqli_prepare($conn, "SELECT * FROM sub_kriteria WHERE id_kriteria = ? ORDER BY id_sub ASC");
    mysqli_stmt_bind_param($stmt2, 'i', $idKriteria);
    mysqli_stmt_execute($stmt2);
    $subKriteriaList = mysqli_stmt_get_result($stmt2);
}

$isTotalValid = abs($totalBobot - 100) < 0.01;
?>

<div class="main-wrapper">
  <div class="top-header">
    <div class="d-flex align-items-center gap-2">
      <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width:36px;height:36px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="white" viewBox="0 0 16 16"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4zm-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664h10z"/></svg>
      </div>
      <div>
        <div class="fw-semibold" style="font-size:0.875rem;color:var(--text-800);">
          <?= isset($_SESSION['user']['username']) ? htmlspecialchars($_SESSION['user']['username']) : 'Pengguna' ?>
        </div>
        <div style="font-size:0.75rem;color:var(--text-500);">
          Tim Seleksi
        </div>
      </div>
    </div>
  </div>

  <div class="content-wrapper p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 style="font-size:1.5rem;font-weight:700;color:var(--text-800);">Data Kriteria</h1>
        <p style="font-size:0.875rem;color:var(--text-600);margin:0;">Kelola kriteria dan sub-kriteria penilaian beasiswa.</p>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-lg-5">
        <div class="card h-100">
          <div class="card-header d-flex justify-content-between align-items-center py-3">
            <h2 style="font-size:1rem;font-weight:600;color:var(--text-800);margin:0;">Daftar Kriteria</h2>
            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddKriteria">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
              Tambah Kriteria
            </button>
          </div>
          <div class="card-body p-0">
            <?php if (empty($kriteriaData)): ?>
              <div class="text-center py-5">
                <p class="mt-3 text-muted" style="font-size:0.875rem;">Belum ada kriteria.<br>Klik tombol di atas untuk menambahkan.</p>
              </div>
            <?php else: ?>
              <div class="list-group list-group-flush">
                <?php foreach ($kriteriaData as $k): ?>
                  <a href="?id_kriteria=<?= $k['id_kriteria'] ?>"
                     class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 <?= ($idKriteria == $k['id_kriteria']) ? 'active' : '' ?>"
                     style="<?= ($idKriteria == $k['id_kriteria']) ? 'background-color:var(--blue-50);border-color:var(--border-main);color:var(--blue-600);' : '' ?>">
                    <div>
                      <div class="fw-medium" style="font-size:0.875rem;color:var(--text-800);"><?= htmlspecialchars($k['nama_kriteria']) ?></div>
                      <div class="d-flex align-items-center" style="font-size:0.75rem;color:var(--text-500);">
                        Bobot: <?= number_format($k['bobot_persen'], 2) ?>%
                        <?php if (isset($k['is_locked']) && $k['is_locked'] == 1): ?>
                          <svg class="ms-2" xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="var(--emerald-600)" viewBox="0 0 16 16" title="Bobot Terkunci">
                            <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/>
                          </svg>
                        <?php endif; ?>
                      </div>
                    </div>
                    <div class="d-flex gap-1">
                      <button type="button" class="btn btn-outline-secondary btn-sm p-1" onclick="event.preventDefault(); editKriteria(<?= $k['id_kriteria'] ?>, '<?= htmlspecialchars($k['nama_kriteria'], ENT_QUOTES) ?>', <?= $k['bobot_persen'] ?>, <?= $k['is_locked'] ?? 0 ?>)" title="Edit">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293l6.5-6.5z"/></svg>
                      </button>
                      <button type="button" class="btn btn-outline-danger btn-sm p-1" onclick="event.preventDefault(); deleteKriteria(<?= $k['id_kriteria'] ?>, '<?= htmlspecialchars($k['nama_kriteria'], ENT_QUOTES) ?>')" title="Hapus">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/><path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/></svg>
                      </button>
                    </div>
                  </a>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
          <div class="card-footer py-3" style="background:transparent;border-top:1px solid var(--border-light);">
            <div class="d-flex justify-content-between align-items-center">
              <span style="font-size:0.875rem;font-weight:500;color:var(--text-600);">Total Bobot</span>
              <span style="font-size:0.875rem;font-weight:600;color:<?= $isTotalValid ? 'var(--emerald-600)' : '#d97706' ?>;">
                <?= number_format($totalBobot, 2) ?>%
              </span>
            </div>
            <?php if (!$isTotalValid): ?>
              <div class="mt-2">
                <div class="alert py-2 px-3 mb-0" style="background-color:#fef3c7;border:1px solid #fcd34d;font-size:0.75rem;color:#92400e;border-radius:var(--radius-sm);">
                  Total bobot tidak sama dengan 100%. Sistem akan menyesuaikannya saat kriteria diubah.
                </div>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="col-lg-7">
        <div class="card h-100">
          <?php if ($idKriteria && $namaKriteriaSelected): ?>
            <div class="card-header d-flex justify-content-between align-items-center py-3">
              <div>
                <h2 style="font-size:1rem;font-weight:600;color:var(--text-800);margin:0;">Sub-Kriteria</h2>
                <span style="font-size:0.75rem;color:var(--text-500);"><?= htmlspecialchars($namaKriteriaSelected) ?></span>
              </div>
              <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddSub">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>
                Tambah Sub-Kriteria
              </button>
            </div>
            <div class="card-body p-0">
              <?php if (mysqli_num_rows($subKriteriaList) === 0): ?>
                <div class="text-center py-5">
                  <p class="mt-3 text-muted" style="font-size:0.875rem;">Belum ada sub-kriteria untuk kriteria ini.</p>
                </div>
              <?php else: ?>
                <div class="table-responsive">
                  <table class="table table-hover align-middle mb-0" style="font-size:0.875rem;">
                    <thead style="background-color:var(--bg-page);">
                      <tr>
                        <th class="text-start" style="padding:0.75rem 1rem;font-weight:600;color:var(--text-600);">No</th>
                        <th class="text-start" style="padding:0.75rem 1rem;font-weight:600;color:var(--text-600);">Nama Sub-Kriteria</th>
                        <th class="text-center" style="padding:0.75rem 1rem;font-weight:600;color:var(--text-600);">Nilai Standar</th>
                        <th class="text-center" style="padding:0.75rem 1rem;font-weight:600;color:var(--text-600);">Jenis Faktor</th>
                        <th class="text-center" style="padding:0.75rem 1rem;font-weight:600;color:var(--text-600);">Aksi</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php $no = 1; while ($sub = mysqli_fetch_assoc($subKriteriaList)): ?>
                        <tr>
                          <td class="text-start" style="padding:0.75rem 1rem;color:var(--text-600);"><?= $no++ ?></td>
                          <td class="text-start" style="padding:0.75rem 1rem;color:var(--text-800);"><?= htmlspecialchars($sub['nama_sub']) ?></td>
                          <td class="text-center" style="padding:0.75rem 1rem;">
                            <span style="display:inline-block;background-color:#f1f5f9;border:1px solid var(--border-main);border-radius:var(--radius-sm);padding:0.25rem 0.5rem;font-size:0.75rem;font-weight:500;color:var(--text-700);">
                              <?= $sub['nilai_standar'] ?>
                            </span>
                          </td>
                          <td class="text-center" style="padding:0.75rem 1rem;">
                            <?php if ($sub['jenis_faktor'] === 'Core Factor'): ?>
                              <span class="badge" style="background-color:var(--indigo-100);color:var(--indigo-600);font-size:0.75rem;font-weight:500;padding:0.25rem 0.5rem;border-radius:var(--radius-sm);">CORE</span>
                            <?php else: ?>
                              <span class="badge" style="background-color:var(--emerald-100);color:var(--emerald-600);font-size:0.75rem;font-weight:500;padding:0.25rem 0.5rem;border-radius:var(--radius-sm);">SECONDARY</span>
                            <?php endif; ?>
                          </td>
                          <td class="text-center" style="padding:0.75rem 1rem;">
                            <button type="button" class="btn btn-outline-secondary btn-sm p-1" onclick="editSub(<?= $sub['id_sub'] ?>, '<?= htmlspecialchars($sub['nama_sub'], ENT_QUOTES) ?>', <?= $sub['nilai_standar'] ?>, '<?= $sub['jenis_faktor'] ?>')" title="Edit">
                              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293l6.5-6.5z"/></svg>
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm p-1" onclick="deleteSub(<?= $sub['id_sub'] ?>, '<?= htmlspecialchars($sub['nama_sub'], ENT_QUOTES) ?>')" title="Hapus">
                              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/><path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/></svg>
                            </button>
                          </td>
                        </tr>
                      <?php endwhile; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            </div>
          <?php else: ?>
            <div class="card-body d-flex flex-column align-items-center justify-content-center py-5">
              <p class="mt-3 text-muted" style="font-size:0.875rem;text-align:center;">Pilih kriteria di panel kiri<br>untuk melihat sub-kriterianya.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalAddKriteria" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title" style="font-size:1rem;font-weight:600;color:var(--text-800);">Tambah Kriteria</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="proses.php">
        <div class="modal-body pt-3">
          <div class="mb-3">
            <label class="form-label" style="font-size:0.875rem;font-weight:500;color:var(--text-700);">Nama Kriteria</label>
            <input type="text" name="nama_kriteria" class="form-control" required style="border:1px solid var(--border-main);border-radius:var(--radius-sm);">
          </div>
          <div class="p-3 rounded" style="background-color:var(--blue-50);border:1px solid var(--blue-100);">
            <div style="font-size:0.75rem;color:var(--blue-600);">
              <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" viewBox="0 0 16 16" class="me-1"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/><path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/></svg>
              <strong>Info:</strong> Bobot kriteria baru ini akan dihitung dan dibagi rata secara otomatis oleh sistem.
            </div>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" name="add_kriteria" class="btn btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalEditKriteria" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title" style="font-size:1rem;font-weight:600;color:var(--text-800);">Edit Kriteria</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="proses.php">
        <input type="hidden" name="id_kriteria" id="edit_id_kriteria">
        <div class="modal-body pt-3">
          <div class="mb-3">
            <label class="form-label" style="font-size:0.875rem;font-weight:500;color:var(--text-700);">Nama Kriteria</label>
            <input type="text" name="nama_kriteria" id="edit_nama_kriteria" class="form-control" required style="border:1px solid var(--border-main);border-radius:var(--radius-sm);">
          </div>
          <div class="mb-3">
            <label class="form-label" style="font-size:0.875rem;font-weight:500;color:var(--text-700);">Bobot (%)</label>
            <input type="number" name="bobot" id="edit_bobot" step="0.01" min="0" max="100" class="form-control" required style="border:1px solid var(--border-main);border-radius:var(--radius-sm);">
          </div>
          <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" name="is_locked" id="edit_is_locked" value="1">
            <label class="form-check-label fw-medium" for="edit_is_locked" style="font-size:0.875rem;color:var(--text-800);">
              Kunci Bobot (Lock)
            </label>
          </div>
          <div style="font-size:0.75rem;color:var(--text-500);line-height:1.4;">
            Jika dikunci, bobot kriteria ini akan absolut (tidak akan diubah oleh sistem saat pembagian rata bobot).
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" name="update_kriteria" class="btn btn-primary">Update</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalDeleteKriteria" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title" style="font-size:1rem;font-weight:600;color:var(--text-800);">Hapus Kriteria</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="proses.php">
        <input type="hidden" name="id_kriteria" id="delete_id_kriteria">
        <div class="modal-body pt-3">
          <p style="font-size:0.875rem;color:var(--text-600);">Apakah Anda yakin ingin menghapus kriteria <strong id="delete_nama_kriteria"></strong>?</p>
          <div class="p-3 rounded" style="background-color:#fef3c7;border:1px solid #fcd34d;">
            <div style="font-size:0.75rem;color:#92400e;">
              Bobot kriteria yang dihapus akan didistribusikan merata ke kriteria tersisa yang tidak di-lock.
            </div>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" name="delete_kriteria" class="btn btn-danger">Hapus</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalAddSub" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title" style="font-size:1rem;font-weight:600;color:var(--text-800);">Tambah Sub-Kriteria</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST">
        <input type="hidden" name="id_kriteria" value="<?= $idKriteria ?>">
        <div class="modal-body pt-3">
          <div class="mb-3">
            <label class="form-label" style="font-size:0.875rem;font-weight:500;color:var(--text-700);">Nama Sub-Kriteria</label>
            <input type="text" name="nama_sub_kriteria" class="form-control" required style="border:1px solid var(--border-main);border-radius:var(--radius-sm);">
          </div>
          <div class="mb-3">
            <label class="form-label" style="font-size:0.875rem;font-weight:500;color:var(--text-700);">Nilai Standar (1-5)</label>
            <input type="number" name="nilai_standar" min="1" max="5" class="form-control" required style="border:1px solid var(--border-main);border-radius:var(--radius-sm);">
          </div>
          <div class="mb-3">
            <label class="form-label" style="font-size:0.875rem;font-weight:500;color:var(--text-700);">Jenis Faktor</label>
            <select name="jenis_faktor" class="form-select" required style="border:1px solid var(--border-main);border-radius:var(--radius-sm);">
              <option value="Core Factor">Core Factor</option>
              <option value="Secondary Factor">Secondary Factor</option>
            </select>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" name="add_sub" class="btn btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalEditSub" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title" style="font-size:1rem;font-weight:600;color:var(--text-800);">Edit Sub-Kriteria</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST">
        <input type="hidden" name="id_sub" id="edit_id_sub">
        <input type="hidden" name="id_kriteria" value="<?= $idKriteria ?>">
        <div class="modal-body pt-3">
          <div class="mb-3">
            <label class="form-label" style="font-size:0.875rem;font-weight:500;color:var(--text-700);">Nama Sub-Kriteria</label>
            <input type="text" name="nama_sub_kriteria" id="edit_nama_sub_kriteria" class="form-control" required style="border:1px solid var(--border-main);border-radius:var(--radius-sm);">
          </div>
          <div class="mb-3">
            <label class="form-label" style="font-size:0.875rem;font-weight:500;color:var(--text-700);">Nilai Standar (1-5)</label>
            <input type="number" name="nilai_standar" id="edit_nilai_standar" min="1" max="5" class="form-control" required style="border:1px solid var(--border-main);border-radius:var(--radius-sm);">
          </div>
          <div class="mb-3">
            <label class="form-label" style="font-size:0.875rem;font-weight:500;color:var(--text-700);">Jenis Faktor</label>
            <select name="jenis_faktor" id="edit_jenis_faktor" class="form-select" required style="border:1px solid var(--border-main);border-radius:var(--radius-sm);">
              <option value="Core Factor">Core Factor</option>
              <option value="Secondary Factor">Secondary Factor</option>
            </select>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" name="update_sub" class="btn btn-primary">Update</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalDeleteSub" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title" style="font-size:1rem;font-weight:600;color:var(--text-800);">Hapus Sub-Kriteria</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST">
        <input type="hidden" name="id_sub" id="delete_id_sub">
        <input type="hidden" name="id_kriteria" value="<?= $idKriteria ?>">
        <div class="modal-body pt-3">
          <p style="font-size:0.875rem;color:var(--text-600);">Apakah Anda yakin ingin menghapus sub-kriteria <strong id="delete_nama_sub"></strong>?</p>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" name="delete_sub" class="btn btn-danger">Hapus</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function editKriteria(id, nama, bobot, isLocked) {
  document.getElementById('edit_id_kriteria').value = id;
  document.getElementById('edit_nama_kriteria').value = nama;
  document.getElementById('edit_bobot').value = bobot;
  document.getElementById('edit_is_locked').checked = (isLocked == 1);
  new bootstrap.Modal(document.getElementById('modalEditKriteria')).show();
}

function deleteKriteria(id, nama) {
  document.getElementById('delete_id_kriteria').value = id;
  document.getElementById('delete_nama_kriteria').textContent = nama;
  new bootstrap.Modal(document.getElementById('modalDeleteKriteria')).show();
}

function editSub(id, nama, nilai, jenis) {
  document.getElementById('edit_id_sub').value = id;
  document.getElementById('edit_nama_sub_kriteria').value = nama;
  document.getElementById('edit_nilai_standar').value = nilai;
  document.getElementById('edit_jenis_faktor').value = jenis;
  new bootstrap.Modal(document.getElementById('modalEditSub')).show();
}

function deleteSub(id, nama) {
  document.getElementById('delete_id_sub').value = id;
  // FIX: Sesuaikan dengan ID di HTML
  document.getElementById('delete_nama_sub').textContent = nama;
  new bootstrap.Modal(document.getElementById('modalDeleteSub')).show();
}
</script>

<?php require_once '../../includes/footer.php'; ?>