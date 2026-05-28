<?php
session_start();

require_once __DIR__ . '/config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        [$conn, $dbError] = getDB();

        if ($dbError !== null) {
            $error = 'Koneksi database gagal.';
        } else {
            $stmt = $conn->prepare("SELECT id_user, username, password FROM users WHERE username = ?");
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user'] = [
                        'id_user'  => $user['id_user'],
                        'username' => $user['username'],
                    ];
                    header('Location: index.php');
                    exit;
                } else {
                    $error = 'Username atau password salah.';
                }
            } else {
                $error = 'Username atau password salah.';
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — SPK Beasiswa</title>
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
    .login-card {
      width: 100%;
      max-width: 400px;
      background: var(--bg-white);
      border-radius: var(--radius-lg);
      border: 1px solid var(--border-main);
      padding: 2.5rem;
    }
    .form-control:focus {
      border-color: var(--blue-600);
      box-shadow: 0 0 0 3px rgba(37,99,235,0.15);
    }
  </style>
</head>
<body>

<div class="login-card shadow-sm">
  <div class="text-center mb-4">
    <div class="mb-4">
      <img src="assets/images/logo_sby.png" alt="Logo Pemuda Tangguh Surabaya" style="height: 90px; width: auto; object-fit: contain;">
    </div>
    <h5 class="fw-bold mb-1" style="color:var(--text-800);">SPK Beasiswa</h5>
    <p class="text-muted mb-0" style="font-size:0.8rem;">Profile Matching</p>
  </div>

  <?php if ($error): ?>
  <div class="alert alert-danger py-2" style="font-size:0.875rem;"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" autocomplete="off">
    <div class="mb-3">
      <label for="username" class="form-label" style="font-size:0.875rem;color:var(--text-600);">Username</label>
      <input type="text" id="username" name="username" class="form-control"
             style="border-radius:var(--radius-sm);" required>
    </div>
    <div class="mb-3">
      <label for="password" class="form-label" style="font-size:0.875rem;color:var(--text-600);">Password</label>
      <input type="password" id="password" name="password" class="form-control"
             style="border-radius:var(--radius-sm);" required>
    </div>
    <button type="submit" class="btn w-100 py-2"
            style="background-color:var(--blue-600);color:#fff;border:none;border-radius:var(--radius-sm);">
      Masuk
    </button>
  </form>
</div>

</body>
</html>
