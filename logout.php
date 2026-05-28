<?php
// 1. Mulai sesi untuk mengakses data sesi yang sedang aktif
session_start();

// 2. Kosongkan semua variabel di dalam sesi saat ini
$_SESSION = [];

// 3. (Opsional tapi direkomendasikan) Hapus cookie sesi di browser pengguna
// Ini memastikan sesi benar-benar terhapus hingga ke akarnya
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Hancurkan sesi di server
session_destroy();

// 5. Arahkan pengguna kembali ke halaman login
header("Location: login.php");
exit;
?>