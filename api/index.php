<?php
// Penjaga Gerbang (Router) khusus untuk Vercel
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Jika pengguna mengakses halaman utama (root)
if ($uri === '/' || $uri === '') {
    require __DIR__ . '/../index.php';
    exit;
}

// Jika pengguna mengakses file/halaman lain
$file = __DIR__ . '/..' . $uri;

if (file_exists($file)) {
    require $file;
} else {
    http_response_code(404);
    echo "404 - Halaman tidak ditemukan";
}