<?php
session_start();

// =========== JIKA USER BELUM LOGIN ===========
// Tampilkan halaman Landing Page sederhana dengan 2 tombol
if (!isset($_SESSION['user'])) {
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Selamat Datang di SPK Beasiswa</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background-color: #f8fafc; /* Warna bg-page */
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
    }
    .landing-card {
      width: 100%;
      max-width: 500px;
      background: white;
      border-radius: 12px;
      border: 1px solid #e2e8f0;
      padding: 3rem 2.5rem;
      text-align: center;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }
  </style>
</head>
<body>
  <div class="landing-card">
    <div class="mb-4">
      <img src="assets/images/logo_sby.png" alt="Logo Pemuda Tangguh Surabaya" style="height: 90px; width: auto; object-fit: contain;">
    </div>
    
    <h2 class="fw-bold mb-2" style="color: #1e293b;">Sistem Pendukung Keputusan</h2>
    <h4 class="mb-4" style="color: #2563eb; font-weight: 600;">Beasiswa Pemuda Tangguh</h4>
    <p class="text-muted mb-5" style="font-size: 0.95rem; line-height: 1.6;">
      Selamat datang di portal seleksi otomatis menggunakan metode Profile Matching. Silakan masuk untuk mengelola data atau daftarkan akun baru jika Anda adalah panitia.
    </p>
    
    <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
      <a href="login.php" class="btn btn-primary px-5 py-2" style="font-weight: 500;">
        Login
      </a>
      <a href="register.php" class="btn btn-outline-secondary px-5 py-2" style="font-weight: 500;">
        Register
      </a>
    </div>
  </div>
</body>
</html>
<?php
    exit; // Berhenti eksekusi kode di sini, sehingga halaman dashboard di bawahnya tidak termuat.
}

// =========== JIKA USER SUDAH LOGIN ===========
// Lanjutkan memuat antarmuka Dashboard
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

[$conn, $dbError] = getDB();

if ($dbError !== null) {
    include __DIR__ . '/includes/footer.php';
    exit;
}

$jumlahKriteria = $conn->query("SELECT COUNT(*) as c FROM kriteria")->fetch_assoc()['c'];
$jumlahSubKriteria = $conn->query("SELECT COUNT(*) as c FROM sub_kriteria")->fetch_assoc()['c'];
$jumlahAlternatif = $conn->query("SELECT COUNT(*) as c FROM alternatif")->fetch_assoc()['c'];
?>

<style>
.wrapper {
    margin-left: 256px;
    padding-top: 64px;
    min-height: 100vh;
}
</style>

<div class="wrapper">
  <main class="p-4">
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
    <h1 class="mb-4" style="font-size:1.5rem;font-weight:700;color:var(--text-800);">Dashboard</h1>

    <div class="row g-4 mb-4">
      <div class="col-4">
        <div class="card rounded p-3" style="border:1px solid var(--border-main);">
          <div class="text-muted mb-1" style="font-size:0.75rem;font-weight:400;">Total Kriteria</div>
          <div class="fw-bold" style="font-size:2rem;color:var(--text-800);"><?= $jumlahKriteria ?></div>
        </div>
      </div>
      <div class="col-4">
        <div class="card rounded p-3" style="border:1px solid var(--border-main);">
          <div class="text-muted mb-1" style="font-size:0.75rem;font-weight:400;">Total Sub-Kriteria</div>
          <div class="fw-bold" style="font-size:2rem;color:var(--text-800);"><?= $jumlahSubKriteria ?></div>
        </div>
      </div>
      <div class="col-4">
        <div class="card rounded p-3" style="border:1px solid var(--border-main);">
          <div class="text-muted mb-1" style="font-size:0.75rem;font-weight:400;">Total Pendaftar</div>
          <div class="fw-bold" style="font-size:2rem;color:var(--text-800);"><?= $jumlahAlternatif ?></div>
        </div>
      </div>
    </div>

    <div class="card rounded p-4" style="border:1px solid var(--border-main); background-color: var(--bg-white);">
  <div class="row gx-5">
    
    <div class="col-12 col-lg-6 mb-4 mb-lg-0">
      <h2 class="mb-3 d-flex align-items-center gap-2" style="font-size:1.125rem; font-weight:600; color:var(--text-800);">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="var(--blue-600)" viewBox="0 0 16 16">
          <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
          <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
        </svg>
        Metode Profile Matching
      </h2>
      <p class="mb-4 text-muted" style="font-size:0.875rem; line-height:1.6;">
        Sistem ini bekerja dengan mencocokkan kualifikasi pendaftar dengan kriteria ideal (standar) yang ditetapkan instansi. Berikut adalah cara sistem menilainya:
      </p>

      <div class="d-flex flex-column gap-3">
        <div class="d-flex align-items-start gap-3 p-3 rounded" style="background-color: var(--bg-page); border: 1px solid var(--border-light);">
          <div class="fw-bold text-center rounded bg-white text-primary" style="width:24px; height:24px; line-height:24px; font-size:0.75rem; border:1px solid var(--border-main);">1</div>
          <div>
            <div class="fw-bold mb-1" style="font-size: 0.875rem; color: var(--text-800);">Pencocokan Nilai (GAP)</div>
            <div class="text-muted" style="font-size: 0.8rem; line-height: 1.5;">Sistem otomatis menghitung selisih antara nilai asli pendaftar dengan nilai kriteria ideal. Selisih ini kemudian diubah menjadi nilai bobot skala 1 hingga 5.</div>
          </div>
        </div>
        <div class="d-flex align-items-start gap-3 p-3 rounded" style="background-color: var(--bg-page); border: 1px solid var(--border-light);">
          <div class="fw-bold text-center rounded bg-white text-primary" style="width:24px; height:24px; line-height:24px; font-size:0.75rem; border:1px solid var(--border-main);">2</div>
          <div>
            <div class="fw-bold mb-1" style="font-size: 0.875rem; color: var(--text-800);">Pengelompokan Faktor</div>
            <div class="text-muted" style="font-size: 0.8rem; line-height: 1.5;">Nilai dibagi menjadi dua: <strong>Faktor Utama</strong> (syarat mutlak dengan bobot lebih besar) dan <strong>Faktor Pendukung</strong> (syarat tambahan).</div>
          </div>
        </div>
        <div class="d-flex align-items-start gap-3 p-3 rounded" style="background-color: var(--bg-page); border: 1px solid var(--border-light);">
          <div class="fw-bold text-center rounded bg-white text-primary" style="width:24px; height:24px; line-height:24px; font-size:0.75rem; border:1px solid var(--border-main);">3</div>
          <div>
            <div class="fw-bold mb-1" style="font-size: 0.875rem; color: var(--text-800);">Hasil & Perankingan</div>
            <div class="text-muted" style="font-size: 0.8rem; line-height: 1.5;">Seluruh nilai diakumulasikan. Pendaftar dengan skor tertinggi adalah kandidat yang profilnya paling mendekati standar beasiswa.</div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-6">
      <h2 class="mb-3 d-flex align-items-center gap-2" style="font-size:1.125rem; font-weight:600; color:var(--text-800);">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="var(--blue-600)" viewBox="0 0 16 16">
          <path d="M11 2a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v12h.5a.5.5 0 0 1 0 1H.5a.5.5 0 0 1 0-1H1v-3a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3h1V7a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v7h1V2zm1 12h2V2h-2v12zm-3 0V7H7v7h2zm-5 0v-3H2v3h2z"/>
        </svg>
        Panduan Penggunaan Sistem
      </h2>
      <p class="mb-4 text-muted" style="font-size:0.875rem; line-height:1.6;">
        Ikuti 4 langkah mudah di bawah ini untuk memulai proses penyeleksian beasiswa dari awal hingga akhir:
      </p>

      <div style="border-left: 2px dashed var(--border-main); margin-left: 12px; padding-left: 20px;">
        
        <div class="mb-4" style="position: relative;">
          <div style="position: absolute; left: -29px; top: 0; background: var(--blue-600); width: 16px; height: 16px; border-radius: 50%; border: 3px solid white;"></div>
          <div class="fw-bold mb-1" style="font-size: 0.875rem; color: var(--text-800);">Tahap 1: Cek Kriteria (Menu Kriteria)</div>
          <div class="text-muted" style="font-size: 0.8rem; line-height: 1.5;">Pastikan bobot persentase Kriteria sudah genap 100%. Atur juga nilai standar (nilai ideal) pada masing-masing Sub-Kriteria sesuai pedoman.</div>
        </div>

        <div class="mb-4" style="position: relative;">
          <div style="position: absolute; left: -29px; top: 0; background: var(--blue-600); width: 16px; height: 16px; border-radius: 50%; border: 3px solid white;"></div>
          <div class="fw-bold mb-1" style="font-size: 0.875rem; color: var(--text-800);">Tahap 2: Input Data (Menu Pendaftar)</div>
          <div class="text-muted" style="font-size: 0.8rem; line-height: 1.5;">Klik tombol <strong>Download Template</strong>. Isi data kandidat di Excel menggunakan angka skala bulat (1-5), lalu unggah kembali melalui tombol <strong>Import Excel</strong>.</div>
        </div>

        <div class="mb-4" style="position: relative;">
          <div style="position: absolute; left: -29px; top: 0; background: var(--blue-600); width: 16px; height: 16px; border-radius: 50%; border: 3px solid white;"></div>
          <div class="fw-bold mb-1" style="font-size: 0.875rem; color: var(--text-800);">Tahap 3: Proses Sistem (Menu Perhitungan)</div>
          <div class="text-muted" style="font-size: 0.8rem; line-height: 1.5;">Buka menu ini untuk melihat transparansi proses sistem dalam mengkalkulasi selisih nilai dan konversi faktor secara otomatis.</div>
        </div>

        <div style="position: relative;">
          <div style="position: absolute; left: -29px; top: 0; background: #16a34a; width: 16px; height: 16px; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 0 2px #dcfce7;"></div>
          <div class="fw-bold mb-1" style="font-size: 0.875rem; color: #16a34a;">Tahap 4: Keputusan (Menu Hasil Akhir)</div>
          <div class="text-muted" style="font-size: 0.8rem; line-height: 1.5;">Tabel akan langsung menampilkan peringkat pendaftar dari skor tertinggi hingga terendah untuk mempermudah pengambilan keputusan Anda.</div>
        </div>

      </div>
    </div>
    
  </div>
</div>
  </main>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>