<?php

// Kredensial Aiven
define('DB_HOST', 'db-spk-beasiswa-db-spk.l.aivencloud.com');
define('DB_USER', 'avnadmin');
define('DB_PASS', getenv('DB_PASS'));
define('DB_NAME', 'defaultdb');
define('DB_PORT', 27187); // Port khusus Aiven
define('DB_CHARSET', 'utf8mb4');
define('BASE_URL', '');

function getDB(): array {
    static $result = null;

    if ($result !== null) {
        return $result;
    }

    mysqli_report(MYSQLI_REPORT_OFF);

    // 1. Inisialisasi koneksi (menggantikan mysqli_connect biasa)
    $conn = mysqli_init();

    // 2. Lakukan koneksi menggunakan parameter lengkap beserta flag SSL
    $success = mysqli_real_connect(
        $conn, 
        DB_HOST, 
        DB_USER, 
        DB_PASS, 
        DB_NAME, 
        DB_PORT, 
        NULL, 
        MYSQLI_CLIENT_SSL
    );

    // Jika gagal terkoneksi
    if (!$success) {
        $result = [null, mysqli_connect_error()];
        return $result;
    }

    // Set charset
    mysqli_set_charset($conn, DB_CHARSET);

    $result = [$conn, null];
    return $result;
}

//DB LOKAL
// define('DB_HOST', 'localhost');
// define('DB_USER', 'root');
// define('DB_PASS', '');
// define('DB_NAME', 'db_spk_beasiswa');
// define('DB_CHARSET', 'utf8mb4');
// define('BASE_URL', '/skripsi_spk_beasiswa');



// function getDB(): array {

//     static $result = null;

//     if ($result !== null) {
//         return $result;
//     }

//     mysqli_report(MYSQLI_REPORT_OFF);

//     $conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

//     if (!$conn) {
//         $result = [null, mysqli_connect_error()];
//         return $result;
//     }

//     mysqli_set_charset($conn, DB_CHARSET);

//     $result = [$conn, null];

//     return $result;

// }