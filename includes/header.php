<?php
// Wajib diletakkan di baris paling atas, sebelum tag HTML apa pun!
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="<?= BASE_URL ?>/assets/css/app.css" rel="stylesheet">
</head>
<body>
<?php if (isset($dbError)): ?>
<div class="modal fade" id="dbErrorModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content text-center p-4">
      <div class="mb-3">
        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#dc2626" viewBox="0 0 16 16">
          <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
        </svg>
      </div>
      <h5 class="modal-title text-danger mb-2">Koneksi Database Gagal</h5>
      <p class="text-muted mb-4">Pastikan MySQL berjalan dan konfigurasi di <code>config/database.php</code> sudah benar.</p>
      <div class="d-flex gap-2 justify-content-center">
        <a href="index.php" class="btn btn-secondary">Beranda</a>
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Coba Lagi</button>
      </div>
    </div>
  </div>
</div>
<script>
  const dbErrorModal = new bootstrap.Modal(document.getElementById('dbErrorModal'));
  dbErrorModal.show();
</script>
<?php endif; ?>