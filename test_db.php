<?php
require_once 'config/database.php';
[$conn, $dbError] = getDB();

if ($dbError) {
    echo "GAGAL KONEKSI: " . $dbError;
} else {
    echo "<h3>BERHASIL terhubung ke database.</h3>";

    // 1. KODE UNTUK CEK KONEKSI ASLI/TIDAK
    // Jika beneran pakai Aiven, ini akan memunculkan domain Aiven, bukan 'localhost' atau '127.0.0.1'
    echo "<strong>Server yang digunakan saat ini:</strong> " . mysqli_get_host_info($conn) . "<br><br>";

    // 2. JALANKAN QUERY DENGAN ENHANCED ERROR HANDLING
    $result = mysqli_query($conn, "SELECT id_user, username FROM users");
    
    // Cek apakah query gagal dieksekusi
    if (!$result) {
        // Ini akan memunculkan alasan detail kenapa tabel users gagal dibaca
        echo "<b style='color:red;'>Query Gagal! Alasan:</b> " . mysqli_error($conn) . "<br>";
        echo "<i>Catatan: Pastikan kamu sudah sukses melakukan 'Execute SQL script' di DBeaver untuk memindahkan tabel ke Aiven.</i>";
        exit;
    }

    $users = mysqli_fetch_all($result, MYSQLI_ASSOC);
    echo "<pre>";
    print_r($users);
    echo "</pre>";
}