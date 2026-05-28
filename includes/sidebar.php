<div class="sidebar">
  <div class="p-4 border-bottom">
    <div class="d-flex align-items-center gap-2">
      <div class="d-flex align-items-center justify-content-center" style="width:36px;height:36px;">
        <img src="<?= BASE_URL ?>/assets/images/logo_sby.png" alt="Logo Surabaya" style="width:100%; height:auto; object-fit:contain;">
      </div>
      <div>
        <div class="fw-bold text-900" style="font-size:0.9rem;line-height:1.2;">SPK Beasiswa</div>
        <div class="text-muted" style="font-size:0.7rem;">Profile Matching</div>
      </div>
    </div>
  </div>

  <nav class="flex-grow-1 py-3">
    <?php
    $currentUri = $_SERVER['REQUEST_URI'];
    $navItems = [
      BASE_URL . '/index.php'                        => 'Dashboard',
      BASE_URL . '/modules/kriteria/index.php'       => 'Data Kriteria',
      BASE_URL . '/modules/alternatif/index.php'     => 'Data Calon Penerima',
      BASE_URL . '/modules/perhitungan/gap.php'      => 'Perhitungan GAP',
      BASE_URL . '/modules/perhitungan/ranking.php'  => 'Hasil Perankingan',
    ];
    ?>
    <?php foreach ($navItems as $uri => $label): ?>
      <a href="<?= $uri ?>"
         class="nav-item<?= (strpos($currentUri, $uri) !== false) ? ' active' : '' ?>">
        <?= $label ?>
      </a>
    <?php endforeach; ?>
  </nav>

  <div class="p-3 border-top">
    <a href="<?= BASE_URL ?>/logout.php" class="nav-item logout">Logout</a>
  </div>
</div>