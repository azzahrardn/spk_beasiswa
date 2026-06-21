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

$kriteriaResult = mysqli_query($conn, 'SELECT * FROM kriteria ORDER BY id_kriteria ASC');
$kriteriaList = [];
$totalBobot = 0;
while ($row = mysqli_fetch_assoc($kriteriaResult)) { 
    $kriteriaList[] = $row; 
    $totalBobot += (float)$row['bobot_persen'];
}

$subResult = mysqli_query($conn, 'SELECT * FROM sub_kriteria ORDER BY id_kriteria ASC, id_sub ASC');
$subKriteriaList = [];
while ($row = mysqli_fetch_assoc($subResult)) { $subKriteriaList[] = $row; }

$penResult = mysqli_query($conn, 'SELECT * FROM penilaian');
$penilaianMap = [];
while ($row = mysqli_fetch_assoc($penResult)) { 
    $penilaianMap[$row['id_alternatif']][$row['id_sub']] = (float)$row['nilai_input']; 
}

$altResult = mysqli_query($conn, 'SELECT * FROM alternatif ORDER BY kode_alternatif ASC');
$alternatifList = [];
while ($row = mysqli_fetch_assoc($altResult)) { $alternatifList[] = $row; }

$persenNCF = 60;
$persenNSF = 40;

$hasilAkhir = [];
if ($totalBobot == 100) {
  foreach ($alternatifList as $alt) {
      $nilaiKriteriaArr = [];
      foreach ($kriteriaList as $kriteria) {
          $subForKriteria = array_filter($subKriteriaList, fn($s) => $s['id_kriteria'] == $kriteria['id_kriteria']);
          $ncfSum = 0; $ncfCount = 0; $nsfSum = 0; $nsfCount = 0;
          foreach ($subForKriteria as $sub) {
              $nilaiInput = $penilaianMap[$alt['id_alternatif']][$sub['id_sub']] ?? 0.0;
              $gap = $nilaiInput - $sub['nilai_standar'];
              $bobot = gapToBobot($gap);
              if ($sub['jenis_faktor'] === 'Core Factor') { 
                  $ncfSum += $bobot; 
                  $ncfCount++; 
              } else { 
                  $nsfSum += $bobot; 
                  $nsfCount++; 
              }
          }
          $ncf = $ncfCount > 0 ? $ncfSum / $ncfCount : 0;
          $nsf = $nsfCount > 0 ? $nsfSum / $nsfCount : 0;
          $nilaiKriteria = ($persenNCF / 100 * $ncf) + ($persenNSF / 100 * $nsf);
          $nilaiKriteriaArr[$kriteria['id_kriteria']] = $nilaiKriteria;
      }
      $nilaiAkhir = 0;
      foreach ($kriteriaList as $kriteria) {
          $nilaiAkhir += ($kriteria['bobot_persen'] / 100) * ($nilaiKriteriaArr[$kriteria['id_kriteria']] ?? 0);
      }
      $hasilAkhir[$alt['id_alternatif']] = ['alt' => $alt, 'nilaiKriteria' => $nilaiKriteriaArr, 'nilaiAkhir' => $nilaiAkhir];
  }
}

  // 1. Urutkan seluruh data dari nilai tertinggi ke terendah
  uasort($hasilAkhir, fn($a, $b) => $b['nilaiAkhir'] <=> $a['nilaiAkhir']);
  $ranked = array_values($hasilAkhir);

  // 2. Terapkan fitur pencarian
  $searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
  $filteredRanked = [];

  foreach ($ranked as $idx => $hasil) {
      $hasil['global_rank'] = $idx + 1; // Simpan peringkat aslinya terlebih dahulu
      
      if ($searchQuery === '') {
          $filteredRanked[] = $hasil;
      } else {
          $kode = strtolower($hasil['alt']['kode_alternatif']);
          $instansi = strtolower($hasil['alt']['asal_instansi']);
          $q = strtolower($searchQuery);
          
          // Cek jika kata kunci cocok dengan kode alternatif atau asal instansi
          if (strpos($kode, $q) !== false || strpos($instansi, $q) !== false) {
              $filteredRanked[] = $hasil;
          }
      }
  }

  // 3. Logika Pagination untuk data yang sudah di-filter
  $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
  $perPage = 10;
  $totalAlternatif = count($filteredRanked);
  $totalPages = max(1, ceil($totalAlternatif / $perPage));

  // Cegah page melebihi total halaman (berguna jika sedang di page tinggi lalu melakukan pencarian)
  if ($page > $totalPages) {
      $page = $totalPages;
  }
  $offset = ($page - 1) * $perPage;

  // Ambil data untuk halaman saat ini
  $rankedPaginated = array_slice($filteredRanked, $offset, $perPage);
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
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
      <div>
        <h1 style="font-size:1.5rem;font-weight:700;color:var(--text-800);">Hasil Perankingan</h1>
        <p style="font-size:0.875rem;color:var(--text-600);margin:0;">Nilai akhir dan ranking alternatif dari tertinggi ke terendah.</p>
      </div>
      
      <form method="GET" action="" class="d-flex gap-2 align-items-center">
        <div class="position-relative">
          <input type="text" name="search" class="form-control form-control-sm pe-4" placeholder="Cari Kode atau Instansi..." value="<?= htmlspecialchars($searchQuery) ?>" style="border: 1px solid var(--border-light); border-radius: var(--radius-sm); min-width: 260px; font-size: 0.875rem;">
        </div>
        <button type="submit" class="btn btn-primary btn-sm d-flex align-items-center gap-2" style="border-radius: var(--radius-sm);">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
            <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
          </svg>
          Cari
        </button>
        <?php if ($searchQuery !== ''): ?>
          <a href="?" class="btn btn-outline-secondary btn-sm" style="border-radius: var(--radius-sm);">Reset</a>
        <?php endif; ?>
      </form>
    </div>

    <?php if (empty($kriteriaList) || empty($subKriteriaList)): ?>
      <div class="card"><div class="card-body text-center py-5"><p class="text-muted">Data kriteria belum tersedia.</p></div></div>
    <?php elseif ($totalBobot != 100): ?>
  <div class="card border-danger">
    <div class="card-body text-center py-5">
      <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#ef4444" class="mb-3" viewBox="0 0 16 16">
        <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
      </svg>
      <h5 class="text-danger fw-bold">Total Bobot Kriteria Tidak Valid</h5>
      <p class="text-muted mb-0">Total bobot kriteria saat ini adalah <strong><?= round($totalBobot, 2) ?>%</strong>. <br>Total bobot harus persis <strong>100%</strong> agar sistem dapat melakukan perankingan. Silakan atur ulang bobot kriteria Anda.</p>
    </div>
  </div>
      <?php elseif (empty($alternatifList)): ?>
      <div class="card"><div class="card-body text-center py-5"><p class="text-muted">Belum ada data alternatif untuk dihitung.</p></div></div>
    <?php elseif (empty($filteredRanked)): ?>
      <div class="card"><div class="card-body text-center py-5"><p class="text-muted">Tidak ditemukan hasil untuk pencarian "<strong><?= htmlspecialchars($searchQuery) ?></strong>".</p></div></div>
    <?php else: ?>
      <div class="card">
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:0.8125rem;">
              <thead style="background-color:var(--bg-page);">
                <tr>
                  <th class="text-center" style="width:60px; padding:0.75rem;font-weight:600;color:var(--text-600);">Peringkat</th>
                  <th style="padding:0.75rem;font-weight:600;color:var(--text-600);">Alternatif</th>
                  <th class="text-center" style="padding:0.75rem;font-weight:600;color:var(--text-600);">Nilai Akhir</th>
                  <?php foreach ($kriteriaList as $k): ?>
                    <th class="text-center" style="padding:0.5rem;font-weight:600;color:var(--text-600);max-width:100px;">
                      <div style="max-width:90px;overflow:hidden;text-overflow:ellipsis; white-space:nowrap; margin: 0 auto;"><?= htmlspecialchars($k['nama_kriteria']) ?></div>
                      <div style="font-size:0.65rem;color:var(--text-500);"><?= number_format($k['bobot_persen'],0) ?>%</div>
                    </th>
                  <?php endforeach; ?>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($rankedPaginated as $hasil): 
                  $alt = $hasil['alt']; 
                  $nilaiAkhir = $hasil['nilaiAkhir']; 
                  $rank = $hasil['global_rank']; // Menggunakan global_rank agar peringat tidak berubah saat di-search
                  
                  $rowBg = 'var(--bg-white)'; 
                  if ($rank === 1) $rowBg = '#fef9c3'; 
                  elseif ($rank === 2) $rowBg = '#f1f5f9'; 
                  elseif ($rank === 3) $rowBg = '#fff7ed'; 
                ?>
                  <tr style="background-color:<?= $rowBg ?>;">
                    <td class="text-center" style="padding:0.75rem;">
                      <?php if ($rank === 1): ?>
                        <span class="badge" style="background-color:#fbbf24;color:#78350f;font-size:0.875rem;padding:0.35rem 0.6rem;border-radius:50%;">1</span>
                      <?php elseif ($rank === 2): ?>
                        <span class="badge" style="background-color:#94a3b8;color:#fff;font-size:0.875rem;padding:0.35rem 0.6rem;border-radius:50%;">2</span>
                      <?php elseif ($rank === 3): ?>
                        <span class="badge" style="background-color:#d97706;color:#fff;font-size:0.875rem;padding:0.35rem 0.6rem;border-radius:50%;">3</span>
                      <?php else: ?>
                        <span class="badge" style="background-color:var(--bg-page);color:var(--text-600);font-size:0.875rem;padding:0.35rem 0.5rem;"><?= $rank ?></span>
                      <?php endif; ?>
                    </td>
                    <td style="padding:0.75rem;color:var(--text-800);">
                        <div class="fw-bold" style="font-size:0.85rem;"><?= htmlspecialchars($alt['kode_alternatif']) ?></div>
                        <div style="font-size:0.75rem;color:var(--text-500);"><?= htmlspecialchars($alt['asal_instansi'] ?: '-') ?></div>
                    </td>
                    <td class="text-center" style="padding:0.75rem;">
                      <span class="badge" style="background-color:var(--blue-100);color:var(--blue-600);font-size:0.875rem;font-weight:700;padding:0.35rem 0.6rem;">
                        <?= number_format($nilaiAkhir, 3) ?>
                      </span>
                    </td>
                    <?php foreach ($kriteriaList as $k): ?>
                      <td class="text-center" style="padding:0.5rem;color:var(--text-600);font-size:0.8rem; border-left:1px solid var(--border-light);">
                        <?php 
                          // Hitung nilai yang sudah dikalikan dengan bobot kriteria
                          $nilaiMurni = $hasil['nilaiKriteria'][$k['id_kriteria']] ?? 0;
                          $nilaiBerbobot = ($k['bobot_persen'] / 100) * $nilaiMurni;
                          
                          echo number_format($nilaiBerbobot, 2); 
                        ?>
                      </td>
                    <?php endforeach; ?>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          
          <?php if ($totalPages > 1): ?>
            <?php 
              // Tangkap parameter query (search dll) selain page untuk navigasi
              $queryParams = $_GET;
              unset($queryParams['page']); 
              $queryString = !empty($queryParams) ? '&' . http_build_query($queryParams) : '';
              
              // Hitung data awal dan akhir untuk informasi entri
              $startData = $offset + 1;
              $endData = min($offset + $perPage, $totalAlternatif);
              $adjacents = 2; 
            ?>
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center px-4 py-3 border-top" style="background-color: #f8fafc; border-radius: 0 0 var(--radius-md) var(--radius-md);">
              
              <div class="mb-3 mb-md-0" style="font-size: 0.8125rem; color: var(--text-500);">
                Menampilkan <span style="font-weight: 600; color: var(--text-800);"><?= $startData ?></span> hingga <span style="font-weight: 600; color: var(--text-800);"><?= $endData ?></span> dari <span style="font-weight: 600; color: var(--text-800);"><?= $totalAlternatif ?></span> data hasil pencarian
              </div>
              
              <nav>
                <ul class="pagination pagination-sm mb-0" style="gap: 0.35rem;">
                  <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link d-flex align-items-center justify-content-center" href="<?= ($page > 1) ? '?page=' . ($page - 1) . $queryString : '#' ?>" style="width: 32px; height: 32px; border-radius: 6px; border: 1px solid var(--border-light); color: var(--text-600); background-color: white;">
                      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M11.354 1.646a.5.5 0 0 1 0 .708L5.707 8l5.647 5.646a.5.5 0 0 1-.708.708l-6-6a.5.5 0 0 1 0-.708l6-6a.5.5 0 0 1 .708 0z"/></svg>
                    </a>
                  </li>
                  
                  <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <?php if ($p == 1 || $p == $totalPages || ($p >= $page - $adjacents && $p <= $page + $adjacents)): ?>
                      <li class="page-item <?= ($p === $page) ? 'active' : '' ?>">
                        <a class="page-link d-flex align-items-center justify-content-center fw-medium" href="?page=<?= $p . $queryString ?>" style="width: 32px; height: 32px; border-radius: 6px; border: 1px solid <?= ($p === $page) ? 'transparent' : 'var(--border-light)' ?>; color: <?= ($p === $page) ? 'white' : 'var(--text-600)' ?>; background-color: <?= ($p === $page) ? 'var(--blue-600)' : 'white' ?>; <?= ($p === $page) ? 'box-shadow: 0 2px 4px rgba(37,99,235,0.25);' : '' ?>">
                          <?= $p ?>
                        </a>
                      </li>
                    <?php elseif ($p == $page - $adjacents - 1 || $p == $page + $adjacents + 1): ?>
                      <li class="page-item disabled">
                        <span class="page-link d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; border-radius: 6px; border: none; background-color: transparent; color: var(--text-500);">...</span>
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
      
      <div class="mt-3 px-4 py-3 rounded" style="background-color: var(--blue-50); border: 1px solid var(--blue-100);">
  <strong class="d-block mb-3" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; color: var(--blue-600);">
    Formula Perhitungan
  </strong>
  <div class="d-flex flex-column gap-3" style="font-size: 0.875rem; color: var(--text-800);">
    <div class="d-flex align-items-center flex-wrap gap-2">
      <span class="fw-semibold">Nilai Kolom Kriteria</span>
      <span style="color: var(--blue-400);">=</span>
      <span class="px-2 py-1 bg-white rounded shadow-sm border" style="border-color: var(--border-light) !important;">
        Bobot Kriteria &times; ((<?= $persenNCF ?>% &times; NCF) + (<?= $persenNSF ?>% &times; NSF))
      </span>
    </div>
    <div class="d-flex align-items-center flex-wrap gap-2">
      <span class="fw-semibold">Nilai Akhir</span>
      <span style="color: var(--blue-400);">=</span>
      <span class="fw-bold" style="font-size: 1.1rem; color: var(--blue-600);">&sum;</span>
      <span class="px-2 py-1 bg-white rounded shadow-sm border" style="border-color: var(--border-light) !important;">
        Total Nilai Kolom Kriteria
      </span>
    </div>
  </div>
</div>
    <?php endif; ?>
  </div>
</div>

<?php require_once '../../includes/footer.php'; ?>