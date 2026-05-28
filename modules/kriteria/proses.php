<?php
// modules/kriteria/proses.php
require_once '../../config/database.php';
[$conn, $dbError] = getDB();

if ($dbError) {
    die("Koneksi Database Gagal");
}

// Fungsi Inti: Kalkulasi Pembagian Bobot
function kalkulasiUlangBobot($conn) {
    // 1. Hitung total bobot dari kriteria yang di-lock (is_locked = 1)
    $resLock = mysqli_query($conn, "SELECT SUM(bobot_persen) as total_lock FROM kriteria WHERE is_locked = 1");
    $rowLock = mysqli_fetch_assoc($resLock);
    $totalLock = $rowLock['total_lock'] ? (float)$rowLock['total_lock'] : 0;

    // 2. Hitung jumlah kriteria yang TIDAK di-lock (is_locked = 0)
    $resUnlock = mysqli_query($conn, "SELECT COUNT(id_kriteria) as jml_unlock FROM kriteria WHERE is_locked = 0");
    $rowUnlock = mysqli_fetch_assoc($resUnlock);
    $jmlUnlock = $rowUnlock['jml_unlock'] ? (int)$rowUnlock['jml_unlock'] : 0;

    // 3. Kalkulasi Sisa Bobot
    $sisaBobot = 100 - $totalLock;
    if ($sisaBobot < 0) {
        $sisaBobot = 0; // Mencegah bobot minus jika admin salah input lock > 100
    }

    // 4. Bagi rata ke kriteria yang unlocked
    if ($jmlUnlock > 0) {
        $bobotBaru = $sisaBobot / $jmlUnlock;
        $stmt = mysqli_prepare($conn, "UPDATE kriteria SET bobot_persen = ? WHERE is_locked = 0");
        mysqli_stmt_bind_param($stmt, 'd', $bobotBaru);
        mysqli_stmt_execute($stmt);
    }
}

// Menangani Request POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // --- TAMBAH KRITERIA ---
    if (isset($_POST['add_kriteria'])) {
        $nama = trim($_POST['nama_kriteria']);
        $bobot = 0; // Set awal 0, akan diubah oleh fungsi
        $is_locked = 0; // Default tidak di-lock

        $stmt = mysqli_prepare($conn, "INSERT INTO kriteria (nama_kriteria, bobot_persen, is_locked) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'sdi', $nama, $bobot, $is_locked);
        if (mysqli_stmt_execute($stmt)) {
            kalkulasiUlangBobot($conn);
        }
        header("Location: index.php");
        exit;
    }

    // --- UPDATE KRITERIA ---
    if (isset($_POST['update_kriteria'])) {
        $id = (int)$_POST['id_kriteria'];
        $nama = trim($_POST['nama_kriteria']);
        $bobot = (float)$_POST['bobot'];
        $is_locked = isset($_POST['is_locked']) ? 1 : 0; // Dari checkbox

        $stmt = mysqli_prepare($conn, "UPDATE kriteria SET nama_kriteria = ?, bobot_persen = ?, is_locked = ? WHERE id_kriteria = ?");
        mysqli_stmt_bind_param($stmt, 'sdii', $nama, $bobot, $is_locked, $id);
        if (mysqli_stmt_execute($stmt)) {
            kalkulasiUlangBobot($conn);
        }
        header("Location: index.php?id_kriteria=" . $id);
        exit;
    }

    // --- HAPUS KRITERIA ---
    if (isset($_POST['delete_kriteria'])) {
        $id = (int)$_POST['id_kriteria'];

        $stmtDel = mysqli_prepare($conn, "DELETE FROM kriteria WHERE id_kriteria = ?");
        mysqli_stmt_bind_param($stmtDel, 'i', $id);
        if (mysqli_stmt_execute($stmtDel)) {
            kalkulasiUlangBobot($conn);
        }
        header("Location: index.php");
        exit;
    }
}

// Jika diakses langsung tanpa POST, tendang balik ke index
header("Location: index.php");
exit;
?>