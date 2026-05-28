<?php
session_start();
require_once __DIR__ . '/config/database.php';

[$conn, $dbError] = getDB();

$successMsg = '';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $errorMsg = 'Username dan password wajib diisi.';
    } else {
        // Pengecekan apakah username sudah ada di database
        $checkStmt = mysqli_prepare($conn, "SELECT id_user FROM users WHERE username = ?");
        mysqli_stmt_bind_param($checkStmt, 's', $username);
        mysqli_stmt_execute($checkStmt);
        mysqli_stmt_store_result($checkStmt);

        if (mysqli_stmt_num_rows($checkStmt) > 0) {
            $errorMsg = 'Username sudah terdaftar, silakan gunakan username lain.';
        } else {
            // Jika belum ada, proses insert data
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            
            $stmt = mysqli_prepare($conn, "INSERT INTO users (username, password) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmt, 'ss', $username, $hashedPassword);

            if (mysqli_stmt_execute($stmt)) {
                $successMsg = 'Akun berhasil dibuat! Silakan login.';
            } else {
                $errorMsg = 'Terjadi kesalahan sistem: ' . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
        mysqli_stmt_close($checkStmt);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Daftar Akun — SPK Beasiswa</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="assets/css/app.css" rel="stylesheet">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background-color: var(--bg-page);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .register-card {
      width: 100%;
      max-width: 420px;
      background: var(--bg-white);
      border-radius: var(--radius-lg);
      border: 1px solid var(--border-main);
      padding: 2.5rem;
    }
    .form-control:focus, .form-select:focus {
      border-color: var(--blue-600);
      box-shadow: 0 0 0 3px rgba(37,99,235,0.15);
    }
  </style>
</head>
<body>

<div class="register-card shadow-sm">
  <div class="text-center mb-4">
    <div class="d-inline-flex align-items-center justify-content-center bg-primary rounded mb-3" style="width:48px;height:48px;">
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="white" viewBox="0 0 16 16">
        <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7Zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm-5.784 6A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216ZM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z"/>
      </svg>
    </div>
    <h5 class="fw-bold mb-1" style="color:var(--text-800);">Buat Akun Baru</h5>
    <p class="text-muted mb-0" style="font-size:0.875rem;">Sistem Pendukung Keputusan Beasiswa</p>
  </div>

  <?php if ($errorMsg): ?>
    <div class="alert alert-danger py-2" style="font-size:0.875rem; border-radius:var(--radius-sm);">
        <?= htmlspecialchars($errorMsg) ?>
    </div>
  <?php endif; ?>

  <?php if ($successMsg): ?>
    <div class="alert alert-success py-2" style="font-size:0.875rem; border-radius:var(--radius-sm);">
        <?= htmlspecialchars($successMsg) ?>
    </div>
  <?php endif; ?>

  <form method="POST" autocomplete="off">
    <div class="mb-3">
      <label for="username" class="form-label" style="font-size:0.875rem;color:var(--text-600);font-weight:500;">Username</label>
      <input type="text" id="username" name="username" class="form-control" style="border-radius:var(--radius-sm);" required>
    </div>
    
    <div class="mb-3">
      <label for="password" class="form-label" style="font-size:0.875rem;color:var(--text-600);font-weight:500;">Password</label>
      <input type="password" id="password" name="password" class="form-control" style="border-radius:var(--radius-sm);" required>
    </div>
    
    <button type="submit" class="btn w-100 py-2 mb-3" style="background-color:var(--blue-600);color:#fff;border:none;border-radius:var(--radius-sm);font-weight:500;">
      Daftar Sekarang
    </button>
  </form>

  <div class="text-center" style="font-size:0.875rem;color:var(--text-600);">
    Sudah punya akun? <a href="login.php" style="color:var(--blue-600);text-decoration:none;font-weight:500;">Masuk di sini</a>
  </div>
</div>

</body>
</html>