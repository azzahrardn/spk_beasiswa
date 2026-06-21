<?php
require_once '../../config/database.php';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';

[$conn, $dbError] = getDB();
if ($dbError) {
    die('<meta name="viewport" content="width=device-width, initial-scale=1.0"><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"><link href="' . BASE_URL . '/assets/css/app.css" rel="stylesheet"><body style="font-family: Inter, sans-serif;"><div class="text-center mt-5"><h5 class="text-danger">Koneksi Database Gagal: ' . htmlspecialchars($dbError) . '</h5></div></body>');
}

function gapToBobot($gap) {
    $tabel = [
        "0" => 5.0, 
        "1" => 4.5, "-1" => 4.0, 
        "2" => 3.5, "-2" => 3.0, 
        "3" => 2.5, "-3" => 2.0, 
        "4" => 1.5, "-4" => 1.0
    ];
    $gapStr = (string)$gap; 
    return $tabel[$gapStr] ?? 0.0;
}

// Menangkap parameter filter
$filterInstansi = isset($_GET['instansi']) ? trim($_GET['instansi']) : '';

// Mengambil daftar instansi unik untuk dropdown filter
$instansiResult = mysqli_query($conn, "SELECT DISTINCT asal_instansi FROM alternatif WHERE asal_instansi IS NOT NULL AND asal_instansi != '' ORDER BY asal_instansi ASC");
$instansiList = [];
while ($row = mysqli_fetch_assoc($instansiResult)) {
    $instansiList[] = $row['asal_instansi'];
}

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Menyesuaikan Query COUNT berdasarkan filter
if ($filterInstansi !== '') {
    $stmtCount = mysqli_prepare($conn, 'SELECT COUNT(*) as cnt FROM alternatif WHERE asal_instansi = ?');
    mysqli_stmt_bind_param($stmtCount, 's', $filterInstansi);
    mysqli_stmt_execute($stmtCount);
    $totalResult = mysqli_stmt_get_result($stmtCount);
} else {
    $totalResult = mysqli_query($conn, 'SELECT COUNT(*) as cnt FROM alternatif');
}
$totalRow = mysqli_fetch_assoc($totalResult);
$totalAlternatif = $totalRow['cnt'];
$totalPages = ceil($totalAlternatif / $perPage);

$subResult = mysqli_query($conn, 'SELECT * FROM sub_kriteria ORDER BY id_kriteria ASC, id_sub ASC');
$subKriteriaList = [];
while ($row = mysqli_fetch_assoc($subResult)) { $subKriteriaList[] = $row; }

// Menyesuaikan Query SELECT data alternatif berdasarkan filter
if ($filterInstansi !== '') {
    $stmt = mysqli_prepare($conn, 'SELECT * FROM alternatif WHERE asal_instansi = ? ORDER BY kode_alternatif ASC LIMIT ? OFFSET ?');
    mysqli_stmt_bind_param($stmt, 'sii', $filterInstansi, $perPage, $offset);
} else {
    $stmt = mysqli_prepare($conn, 'SELECT * FROM alternatif ORDER BY kode_alternatif ASC LIMIT ? OFFSET ?');
    mysqli_stmt_bind_param($stmt, 'ii', $perPage, $offset);
}

if (!$stmt) { die("Prepare gagal: " . mysqli_error($conn)); }
mysqli_stmt_execute($stmt);
$altResult = mysqli_stmt_get_result($stmt);
$alternatifList = [];
while ($row = mysqli_fetch_assoc($altResult)) { $alternatifList[] = $row; }

$penResult = mysqli_query($conn, 'SELECT * FROM penilaian');
$penilaianMap = [];
while ($row = mysqli_fetch_assoc($penResult)) {
    $penilaianMap[$row['id_alternatif']][$row['id_sub']] = (float)$row['nilai_input'];
}

// String query tambahan untuk link pagination agar filter tidak hilang saat pindah halaman
$queryString = $filterInstansi !== '' ? '&instansi=' . urlencode($filterInstansi) : '';
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
        <h1 style="font-size:1.5rem;font-weight:700;color:var(--text-800);">Perhitungan GAP</h1>
        <p style="font-size:0.875rem;color:var(--text-600);margin:0;">Hitung selisih nilai input dengan nilai standar tiap sub-kriteria.</p>
      </div>
      
      <form method="GET" class="d-flex gap-2">
        <select name="instansi" class="form-select form-select-sm" style="min-width: 200px; border-radius: var(--radius-sm);" onchange="this.form.submit()">
          <option value="">-- Semua Instansi --</option>
          <?php foreach ($instansiList as $instansi): ?>
            <option value="<?= htmlspecialchars($instansi) ?>" <?= $filterInstansi === $instansi ? 'selected' : '' ?>>
              <?= htmlspecialchars($instansi) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if ($filterInstansi !== ''): ?>
          <a href="?" class="btn btn-outline-secondary btn-sm d-flex align-items-center" style="border-radius: var(--radius-sm);" title="Reset Filter">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
              <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
            </svg>
          </a>
        <?php endif; ?>
      </form>
    </div>

    <div class="accordion mb-4" id="accordionBobotGap">
      <div class="accordion-item" style="border:1px solid var(--border-main); border-radius: var(--radius-md); overflow:hidden;">
        <h2 class="accordion-header" id="headingBobot">
          <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBobot" aria-expanded="false" aria-controls="collapseBobot" style="font-size: 0.875rem; font-weight: 600; color: var(--text-800); background-color: var(--bg-white); box-shadow: none;">
            <div class="d-flex align-items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="var(--blue-600)" viewBox="0 0 16 16">
                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                <path d="M5.255 5.786a.237.237 0 0 0 .241.247h.825c.138 0 .248-.113.266-.25.09-.656.54-1.134 1.342-1.134.686 0 1.314.343 1.314 1.168 0 .635-.374.927-.965 1.371-.673.489-1.206 1.06-1.168 1.987l.003.217a.25.25 0 0 0 .25.246h.811a.25.25 0 0 0 .25-.25v-.105c0-.718.273-.927 1.01-1.486.609-.463 1.244-.977 1.244-2.056 0-1.511-1.276-2.241-2.673-2.241-1.267 0-2.655.59-2.75 2.286zm1.557 5.763c0 .533.425.927 1.01.927.609 0 1.028-.394 1.028-.927 0-.552-.42-.94-1.029-.94-.584 0-1.009.388-1.009.94z"/>
              </svg>
              Lihat Tabel Referensi Bobot Nilai GAP
            </div>
          </button>
        </h2>
        <div id="collapseBobot" class="accordion-collapse collapse" aria-labelledby="headingBobot" data-bs-parent="#accordionBobotGap">
          <div class="accordion-body p-0">
            <div class="table-responsive">
              <table class="table table-bordered table-sm mb-0" style="font-size: 0.8125rem;">
                <thead style="background-color: var(--bg-page);">
                  <tr>
                    <th class="text-center py-2" style="width: 50px; color: var(--text-600);">No</th>
                    <th class="text-center py-2" style="width: 100px; color: var(--text-600);">Selisih</th>
                    <th class="text-center py-2" style="width: 120px; color: var(--text-600);">Bobot Nilai</th>
                    <th class="py-2" style="color: var(--text-600);">Keterangan</th>
                  </tr>
                </thead>
                <tbody>
                  <tr><td class="text-center">1</td><td class="text-center fw-bold">0</td><td class="text-center fw-bold text-success">5</td><td>Tidak ada selisih (Kompetensi sesuai dengan yang dibutuhkan)</td></tr>
                  <tr><td class="text-center">2</td><td class="text-center fw-bold">1</td><td class="text-center fw-bold text-primary">4,5</td><td>Kompetensi individu kelebihan 1 tingkat/level</td></tr>
                  <tr><td class="text-center">3</td><td class="text-center fw-bold">-1</td><td class="text-center fw-bold text-primary">4</td><td>Kompetensi individu kekurangan 1 tingkat/level</td></tr>
                  <tr><td class="text-center">4</td><td class="text-center fw-bold">2</td><td class="text-center fw-bold text-primary">3,5</td><td>Kompetensi individu kelebihan 2 tingkat/level</td></tr>
                  <tr><td class="text-center">5</td><td class="text-center fw-bold">-2</td><td class="text-center fw-bold text-primary">3</td><td>Kompetensi individu kekurangan 2 tingkat/level</td></tr>
                  <tr><td class="text-center">6</td><td class="text-center fw-bold">3</td><td class="text-center fw-bold text-warning">2,5</td><td>Kompetensi individu kelebihan 3 tingkat/level</td></tr>
                  <tr><td class="text-center">7</td><td class="text-center fw-bold">-3</td><td class="text-center fw-bold text-warning">2</td><td>Kompetensi individu kekurangan 3 tingkat/level</td></tr>
                  <tr><td class="text-center">8</td><td class="text-center fw-bold">4</td><td class="text-center fw-bold text-danger">1,5</td><td>Kompetensi individu kelebihan 4 tingkat/level</td></tr>
                  <tr><td class="text-center">9</td><td class="text-center fw-bold">-4</td><td class="text-center fw-bold text-danger">1</td><td>Kompetensi individu kekurangan 4 tingkat/level</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php if (empty($subKriteriaList)): ?>
      <div class="card"><div class="card-body text-center py-5"><p class="text-muted">Sub-kriteria belum tersedia.</p></div></div>
    <?php elseif (empty($alternatifList) && $filterInstansi !== ''): ?>
      <div class="card"><div class="card-body text-center py-5"><p class="text-muted">Tidak ada pendaftar dari instansi <strong><?= htmlspecialchars($filterInstansi) ?></strong>.</p></div></div>
    <?php elseif (empty($alternatifList)): ?>
      <div class="card"><div class="card-body text-center py-5"><p class="text-muted">Belum ada data alternatif.</p></div></div>
    <?php else: ?>
      <div class="card">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle mb-0" style="font-size:0.8125rem; min-width: 1200px;">
              <thead style="background-color:var(--bg-page);">
                <tr>
                  <th rowspan="2" class="align-middle text-center" style="width:50px; padding:0.75rem;font-weight:600;color:var(--text-600);">No</th>
                  <th rowspan="2" class="align-middle" style="width:250px; padding:0.75rem;font-weight:600;color:var(--text-600);">Alternatif</th>
                  <th colspan="<?= count($subKriteriaList) ?>" class="text-center" style="padding:0.5rem;font-weight:600;color:var(--text-600);border-bottom:1px solid var(--border-main);">Sub-Kriteria</th>
                </tr>
                <tr>
                  <?php foreach ($subKriteriaList as $sub): ?>
                    <th class="text-center" style="padding:0.4rem;font-size:0.75rem;font-weight:500;color:var(--text-600);background-color:var(--bg-page);">
                      <div style="overflow:hidden;text-overflow:ellipsis; white-space:nowrap;"><?= htmlspecialchars($sub['nama_sub']) ?></div>
                      <div style="font-size:0.65rem;color:var(--text-500);">Std: <?= $sub['nilai_standar'] ?></div>
                    </th>
                  <?php endforeach; ?>
                </tr>
              </thead>
              <tbody>
                <?php $no = $offset + 1; foreach ($alternatifList as $alt): ?>
                  <tr>
                    <td class="text-center" style="padding:0.75rem;color:var(--text-600);"><?= $no++ ?></td>
                    <td style="padding:0.75rem;color:var(--text-800);">
                      <div class="fw-bold" style="font-size:0.85rem;"><?= htmlspecialchars($alt['kode_alternatif']) ?></div>
                      <div style="font-size:0.75rem;color:var(--text-500);"><?= htmlspecialchars($alt['asal_instansi'] ?: '-') ?></div>
                    </td>
                    <?php foreach ($subKriteriaList as $sub): ?>
                      <?php 
                        $nilaiInput = $penilaianMap[$alt['id_alternatif']][$sub['id_sub']] ?? 0.0; 
                        $gap = $nilaiInput - $sub['nilai_standar']; 
                        
                        // Konversi GAP ke bobot (sesuai fungsi di atas)
                        $bobot = gapToBobot($gap); 
                        
                        // Pewarnaan Badge GAP
                        $bgColor = 'var(--bg-white)'; 
                        $textColor = 'var(--text-600)'; 
                        if ($gap < 0) { 
                            $bgColor = '#fee2e2'; 
                            $textColor = '#991b1b'; 
                        } elseif ($gap == 0) { 
                            $bgColor = '#dcfce7'; 
                            $textColor = '#166534'; 
                        } elseif ($gap > 0) { 
                            $bgColor = '#e0f2fe'; 
                            $textColor = '#075985'; 
                        } 
                      ?>
                      <td class="text-center" style="padding:0.5rem; border-left:1px solid var(--border-light);">
                        <div style="font-size:0.7rem;color:var(--text-500);">In: <?= is_numeric($nilaiInput) && floor($nilaiInput) != $nilaiInput ? number_format($nilaiInput, 1) : $nilaiInput ?></div>
                        <span class="badge my-1" style="display:inline-block;min-width:40px;background-color:<?= $bgColor ?>;color:<?= $textColor ?>;font-size:0.75rem;font-weight:600;padding:0.2rem 0.4rem;border-radius:0.25rem;">
                          <?= $gap > 0 ? '+' . (is_numeric($gap) && floor($gap) != $gap ? number_format($gap, 1) : $gap) : (is_numeric($gap) && floor($gap) != $gap ? number_format($gap, 1) : $gap) ?>
                        </span>
                        <div style="font-size:0.65rem;font-weight:600;color:var(--text-800);">B: <?= number_format($bobot, 1) ?></div>
                      </td>
                    <?php endforeach; ?>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          
         <?php if ($totalPages > 1): ?>
            <?php 
              $queryParams = $_GET;
              unset($queryParams['page']); 
              $queryString = !empty($queryParams) ? '&' . http_build_query($queryParams) : '';
              // Hitung data awal dan akhir untuk informasi entri
              $startData = $offset + 1;
              $endData = min($offset + $perPage, $totalAlternatif);
              
              // Tentukan jumlah halaman di samping halaman aktif (kiri & kanan)
              $adjacents = 2; 
            ?>
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center px-4 py-3 border-top" style="background-color: #f8fafc; border-radius: 0 0 var(--radius-md) var(--radius-md);">
              
              <div class="mb-3 mb-md-0" style="font-size: 0.8125rem; color: var(--text-500);">
                Menampilkan <span style="font-weight: 600; color: var(--text-800);"><?= $startData ?></span> hingga <span style="font-weight: 600; color: var(--text-800);"><?= $endData ?></span> dari <span style="font-weight: 600; color: var(--text-800);"><?= $totalAlternatif ?></span> data
              </div>
              
              <nav>
                <ul class="pagination pagination-sm mb-0" style="gap: 0.35rem;">
                  <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link d-flex align-items-center justify-content-center" href="<?= ($page > 1) ? '?page=' . ($page - 1) . $queryString : '#' ?>" style="width: 32px; height: 32px; border-radius: 6px; border: 1px solid var(--border-light); color: var(--text-600); background-color: white;">
                      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0z"/></svg>
                    </a>
                  </li>
                  
                  <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <?php 
                      // Kondisi untuk menampilkan nomor halaman: 
                      // 1. Selalu tampilkan halaman pertama (1)
                      // 2. Selalu tampilkan halaman terakhir ($totalPages)
                      // 3. Tampilkan halaman di sekitar halaman aktif ($page)
                      if ($p == 1 || $p == $totalPages || ($p >= $page - $adjacents && $p <= $page + $adjacents)): 
                    ?>
                      <li class="page-item <?= ($p === $page) ? 'active' : '' ?>">
                        <a class="page-link d-flex align-items-center justify-content-center fw-medium" href="?page=<?= $p . $queryString ?>" style="width: 32px; height: 32px; border-radius: 6px; border: 1px solid <?= ($p === $page) ? 'transparent' : 'var(--border-light)' ?>; color: <?= ($p === $page) ? 'white' : 'var(--text-600)' ?>; background-color: <?= ($p === $page) ? 'var(--blue-600)' : 'white' ?>; <?= ($p === $page) ? 'box-shadow: 0 2px 4px rgba(37,99,235,0.25);' : '' ?>">
                          <?= $p ?>
                        </a>
                      </li>
                    <?php 
                      // Tampilkan elipsis (...) jika halaman berada tepat sebelum atau sesudah blok halaman yang aktif
                      elseif ($p == $page - $adjacents - 1 || $p == $page + $adjacents + 1): 
                    ?>
                      <li class="page-item disabled">
                        <span class="page-link d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; border-radius: 6px; border: none; background-color: transparent; color: var(--text-500);">
                          ...
                        </span>
                      </li>
                    <?php endif; ?>
                  <?php endfor; ?>
                  
                  <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                    <a class="page-link d-flex align-items-center justify-content-center" href="<?= ($page < $totalPages) ? '?page=' . ($page + 1) . $queryString : '#' ?>" style="width: 32px; height: 32px; border-radius: 6px; border: 1px solid var(--border-light); color: var(--text-600); background-color: white;">
                      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/></svg>
                    </a>
                  </li>
                </ul>
              </nav>
            </div>
<?php endif; ?>
          
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once '../../includes/footer.php'; ?>