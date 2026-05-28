<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';

[$conn, $dbError] = getDB();
if ($dbError) {
    die('<meta name="viewport" content="width=device-width, initial-scale=1.0"><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"><link href="' . BASE_URL . '/assets/css/app.css" rel="stylesheet"><body style="font-family: Inter, sans-serif;"><div class="text-center mt-5"><h5 class="text-danger">Koneksi Database Gagal: ' . htmlspecialchars($dbError) . '</h5></div></body>');
}

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$totalResult = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM alternatif");
$totalRow = mysqli_fetch_assoc($totalResult);
$totalAlternatif = $totalRow['cnt'];
$totalPages = ceil($totalAlternatif / $perPage);

$stmt = mysqli_prepare($conn, "SELECT * FROM alternatif ORDER BY kode_alternatif ASC LIMIT ? OFFSET ?");
mysqli_stmt_bind_param($stmt, 'ii', $perPage, $offset);
mysqli_stmt_execute($stmt);
$alternatifResult = mysqli_stmt_get_result($stmt);

$alternatifList = [];
while ($row = mysqli_fetch_assoc($alternatifResult)) { 
    $alternatifList[] = $row; 
}

$importError = $_SESSION['import_error'] ?? null;
$importSuccess = $_SESSION['import_success'] ?? null;
unset($_SESSION['import_error'], $_SESSION['import_success']);
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
        <h1 style="font-size:1.5rem;font-weight:700;color:var(--text-800);">Data Calon Penerima</h1>
        <p style="font-size:0.875rem;color:var(--text-600);margin:0;">Kelola data pendaftar beasiswa melalui fitur import Excel.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="download_template.php" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
            <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
            <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
          </svg>
          Download Template
        </a>
        <button type="button" class="btn btn-primary btn-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalImport">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M14 4.5V14a2 2 0 0 1-2 2h-1v-1h1a1 1 0 0 0 1-1V4.5h-2A1.5 1.5 0 0 1 9.5 3V1H4a1 1 0 0 0-1 1v9H2V2a2 2 0 0 1 2-2h5.5L14 4.5ZM1.928 15.849v-3.337h1.136v-.662H0v.662h1.134v3.337h.794Zm4.689-3.999h-.894L4.9 13.289h-.035l-.832-1.439h-.931l1.227 1.983-1.239 2.016h.861l.853-1.415h.035l.85 1.415h.908L5.405 13.85l1.212-2z"/>
          </svg>
          Import Excel
        </button>
        <?php if (!empty($alternatifList)): ?>
          <button type="button" class="btn btn-danger btn-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalDeleteAll">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/><path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/></svg>
            Kosongkan Data
          </button>
        <?php endif; ?>
      </div>
    </div>

    <div class="alert mb-4" style="background-color: var(--blue-50); border: 1px solid var(--blue-100); border-radius: var(--radius-md);">
      <div class="d-flex gap-3">
        <div style="color: var(--blue-600);">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
            <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
          </svg>
        </div>
        <div>
          <h6 style="color: var(--blue-800); font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Langkah Pengisian Data Alternatif:</h6>
          <ol style="color: var(--blue-700); font-size: 0.8125rem; margin-bottom: 0; padding-left: 1rem; line-height: 1.6;">
            <li>Klik tombol <strong>Download Template</strong> untuk mendapatkan format Excel terbaru yang sesuai dengan kriteria saat ini.</li>
            <li>Isi kolom <strong>A (Kode)</strong> dan <strong>B (Asal Instansi)</strong> sesuai data pendaftar.</li>
            <li><strong>SANGAT PENTING:</strong> Konversikan data asli pendaftar menjadi angka <strong>Skala Bulat (1 sampai 5)</strong>. Dilarang memasukkan angka desimal atau data mentah.<br><em>Contoh: Jika IPK pendaftar adalah 3.8 dan di aturan bernilai skala 5, maka cukup ketik angka <strong>5</strong> di Excel.</em></li>
            <li>Simpan file, lalu klik <strong>Import Excel</strong> untuk memasukkan data ke dalam sistem.</li>
          </ol>
        </div>
      </div>
    </div>

    <?php if ($importError): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius:var(--radius-sm);font-size:0.875rem;">
        <?= htmlspecialchars($importError) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>
    <?php if ($importSuccess): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius:var(--radius-sm);font-size:0.875rem;">
        <?= htmlspecialchars($importSuccess) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-body p-0">
        <?php if (empty($alternatifList)): ?>
          <div class="text-center py-5">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="var(--text-400)" viewBox="0 0 16 16" style="opacity:0.5">
              <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4zm-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664h10z"/>
            </svg>
            <p class="mt-3 text-muted" style="font-size:0.875rem;">Belum ada data alternatif.<br>Silakan Download Template, isi datanya, lalu Import File Excel.</p>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:0.875rem;">
              <thead style="background-color:var(--bg-page);">
                <tr>
                  <th style="padding:0.75rem 1rem;font-weight:600;color:var(--text-600);">No</th>
                  <th style="padding:0.75rem 1rem;font-weight:600;color:var(--text-600);">Kode Alternatif</th>
                  <th style="padding:0.75rem 1rem;font-weight:600;color:var(--text-600);">Asal Instansi</th>
                  <th style="padding:0.75rem 1rem;font-weight:600;color:var(--text-600);text-align:center;">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php $no = $offset + 1; foreach ($alternatifList as $alt): ?>
                  <tr>
                    <td style="padding:0.75rem 1rem;color:var(--text-600);"><?= $no++ ?></td>
                    <td style="padding:0.75rem 1rem;color:var(--text-800);font-weight:600;"><?= htmlspecialchars($alt['kode_alternatif']) ?></td>
                    <td style="padding:0.75rem 1rem;color:var(--text-600);"><?= htmlspecialchars($alt['asal_instansi']) ?></td>
                    <td style="padding:0.75rem 1rem;text-align:center;">
                      <button type="button" class="btn btn-outline-secondary btn-sm p-1" onclick="editAlt(<?= $alt['id_alternatif'] ?>, '<?= htmlspecialchars($alt['asal_instansi'], ENT_QUOTES) ?>')" title="Edit Instansi">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293l6.5-6.5z"/></svg>
                      </button>
                      <button type="button" class="btn btn-outline-danger btn-sm p-1" onclick="deleteAlt(<?= $alt['id_alternatif'] ?>, '<?= htmlspecialchars($alt['kode_alternatif'], ENT_QUOTES) ?>')" title="Hapus">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/><path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/></svg>
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php if ($totalPages > 1): ?>
            <?php 
              $startData = $offset + 1;
              $endData = min($offset + $perPage, $totalAlternatif);
            ?>
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center px-4 py-3 border-top" style="background-color: #f8fafc; border-radius: 0 0 var(--radius-md) var(--radius-md);">
              <div class="mb-3 mb-md-0" style="font-size: 0.8125rem; color: var(--text-500);">
                Menampilkan <span style="font-weight: 600; color: var(--text-800);"><?= $startData ?></span> hingga <span style="font-weight: 600; color: var(--text-800);"><?= $endData ?></span> dari <span style="font-weight: 600; color: var(--text-800);"><?= $totalAlternatif ?></span> data
              </div>
              <nav>
                <ul class="pagination pagination-sm mb-0" style="gap: 0.35rem;">
                  <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link d-flex align-items-center justify-content-center" href="<?= ($page > 1) ? '?page=' . ($page - 1) : '#' ?>" style="width: 32px; height: 32px; border-radius: 6px; border: 1px solid var(--border-light); color: var(--text-600); background-color: white;">
                      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0z"/></svg>
                    </a>
                  </li>
                  <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <li class="page-item <?= ($p === $page) ? 'active' : '' ?>">
                      <a class="page-link d-flex align-items-center justify-content-center fw-medium" href="?page=<?= $p ?>" style="width: 32px; height: 32px; border-radius: 6px; border: 1px solid <?= ($p === $page) ? 'transparent' : 'var(--border-light)' ?>; color: <?= ($p === $page) ? 'white' : 'var(--text-600)' ?>; background-color: <?= ($p === $page) ? 'var(--blue-600)' : 'white' ?>; <?= ($p === $page) ? 'box-shadow: 0 2px 4px rgba(37,99,235,0.25);' : '' ?>">
                        <?= $p ?>
                      </a>
                    </li>
                  <?php endfor; ?>
                  <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                    <a class="page-link d-flex align-items-center justify-content-center" href="<?= ($page < $totalPages) ? '?page=' . ($page + 1) : '#' ?>" style="width: 32px; height: 32px; border-radius: 6px; border: 1px solid var(--border-light); color: var(--text-600); background-color: white;">
                      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/></svg>
                    </a>
                  </li>
                </ul>
              </nav>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title" style="font-size:1rem;font-weight:600;color:var(--text-800);">Edit Instansi Alternatif</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="proses.php">
        <input type="hidden" name="page" value="<?= $page ?>">
        <input type="hidden" name="id_alternatif" id="edit_id">
        <div class="modal-body pt-3">
          <div class="mb-3">
            <label class="form-label" style="font-size:0.875rem;font-weight:500;color:var(--text-700);">Asal Instansi</label>
            <input type="text" name="asal_instansi" id="edit_instansi" class="form-control" required style="border:1px solid var(--border-main);border-radius:var(--radius-sm);">
          </div>
          <div style="font-size:0.75rem;color:var(--text-500);">
            *Catatan: Nilai sub-kriteria hanya dapat diubah melalui upload ulang Template Excel.
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" name="update_alternatif" class="btn btn-primary">Update</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalDelete" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title" style="font-size:1rem;font-weight:600;color:var(--text-800);">Hapus Alternatif</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="proses.php">
        <input type="hidden" name="page" value="<?= $page ?>">
        <input type="hidden" name="id_alternatif" id="delete_id">
        <div class="modal-body pt-3">
          <p style="font-size:0.875rem;color:var(--text-600);">Apakah Anda yakin ingin menghapus data calon penerima <strong id="delete_kode"></strong>?</p>
          <div style="font-size:0.75rem;color:#dc2626;">*Semua nilai kriteria milik kandidat ini juga akan terhapus.</div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" name="delete_alternatif" class="btn btn-danger">Hapus</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalDeleteAll" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title" style="font-size:1rem;font-weight:600;color:var(--text-800);">Hapus Seluruh Data</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="proses.php">
        <input type="hidden" name="page" value="1">
        <div class="modal-body pt-3">
          <p style="font-size:0.875rem;color:var(--text-600);">Apakah Anda yakin ingin mengosongkan <strong>seluruh data calon penerima</strong>?</p>
          <div class="p-3 rounded" style="background-color:#fee2e2;border:1px solid #f87171;">
            <div style="font-size:0.75rem;color:#991b1b;">
              <strong>Peringatan:</strong> Tindakan ini tidak dapat dibatalkan. Semua data alternatif dan data penilaian akan terhapus permanen dari sistem.
            </div>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" name="delete_all_alternatif" class="btn btn-danger">Ya, Kosongkan Data</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalImport" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title" style="font-size:1rem;font-weight:600;color:var(--text-800);">Import Data Pendaftar</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="import.php" enctype="multipart/form-data">
        <div class="modal-body pt-3">
          <div class="mb-3">
            <label class="form-label" style="font-size:0.875rem;font-weight:500;color:var(--text-700);">Pilih File Template (.xlsx / .xls)</label>
            <input type="file" name="file_excel" accept=".xlsx,.xls" class="form-control" required style="border:1px solid var(--border-main);border-radius:var(--radius-sm);">
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Mulai Import</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function editAlt(id, instansi) {
  document.getElementById('edit_id').value = id;
  document.getElementById('edit_instansi').value = instansi;
  new bootstrap.Modal(document.getElementById('modalEdit')).show();
}

function deleteAlt(id, kode) {
  document.getElementById('delete_id').value = id;
  document.getElementById('delete_kode').textContent = kode;
  new bootstrap.Modal(document.getElementById('modalDelete')).show();
}
</script>

<?php require_once '../../includes/footer.php'; ?>