<?php
session_start();
require_once '../../config/database.php';

[$conn, $dbError] = getDB();
if ($dbError) {
    die("Koneksi Database Gagal");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;

    // --- TAMBAH MANUAL ---
    if (isset($_POST['add_alternatif'])) {
        $instansi = trim($_POST['asal_instansi']);
        
        // Auto-generate Kode Alternatif (A01, A02, dst)
        $kodeResult = mysqli_query($conn, "SELECT kode_alternatif FROM alternatif ORDER BY id_alternatif DESC LIMIT 1");
        $lastKode = mysqli_fetch_assoc($kodeResult);
        $num = $lastKode ? (int)substr($lastKode['kode_alternatif'], 1) + 1 : 1;
        $kode = 'A' . str_pad($num, 2, '0', STR_PAD_LEFT);
        
        // Hanya insert kode dan instansi (nama_pendaftar sudah di-drop dari DB)
        $stmt = mysqli_prepare($conn, "INSERT INTO alternatif (kode_alternatif, asal_instansi) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, 'ss', $kode, $instansi);
        mysqli_stmt_execute($stmt);
        
        header("Location: index.php?page=" . $page);
        exit;
    }

    // --- UPDATE MANUAL ---
    if (isset($_POST['update_alternatif'])) {
        $id = (int)$_POST['id_alternatif'];
        $instansi = trim($_POST['asal_instansi']);
        
        $stmt = mysqli_prepare($conn, "UPDATE alternatif SET asal_instansi = ? WHERE id_alternatif = ?");
        mysqli_stmt_bind_param($stmt, 'si', $instansi, $id);
        mysqli_stmt_execute($stmt);
        
        header("Location: index.php?page=" . $page);
        exit;
    }

    // --- HAPUS MANUAL ---
    if (isset($_POST['delete_alternatif'])) {
        $id = (int)$_POST['id_alternatif'];
        
        $stmt = mysqli_prepare($conn, "DELETE FROM alternatif WHERE id_alternatif = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        
        header("Location: index.php?page=" . $page);
        exit;
    }

    // --- HAPUS SELURUH DATA ---
    if (isset($_POST['delete_all_alternatif'])) {
        // Hapus semua baris di tabel alternatif (tabel penilaian otomatis terhapus karena CASCADE)
        $stmt = mysqli_prepare($conn, "DELETE FROM alternatif");
        mysqli_stmt_execute($stmt);
        
        // Reset Auto-Increment agar saat import baru, ID dan kode dimulai dari urutan awal lagi
        mysqli_query($conn, "ALTER TABLE alternatif AUTO_INCREMENT = 1");
        
        header("Location: index.php?page=1");
        exit;
    }
}

header("Location: index.php");
exit;
?>