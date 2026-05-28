<?php
session_start();
require_once '../../config/database.php';
require_once '../../vendor/autoload.php'; // Load PhpSpreadsheet

[$conn, $dbError] = getDB();
if ($dbError) {
    die("Koneksi Database Gagal");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_excel'])) {
    $file = $_FILES['file_excel'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, ['xlsx', 'xls'])) {
        $_SESSION['import_error'] = 'Ekstensi file tidak valid. Gunakan format Excel (.xlsx / .xls).';
        header("Location: index.php");
        exit;
    }
    
    if ($ext === 'xlsx') { 
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx(); 
    } else { 
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls(); 
    }
    
    $reader->setReadDataOnly(true);
    $spreadsheet = $reader->load($file['tmp_name']);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();
    
    // Ambil daftar ID sub kriteria yang ada di sistem
    $subResult = mysqli_query($conn, "SELECT id_sub FROM sub_kriteria ORDER BY id_sub ASC");
    $subKriteriaList = [];
    while ($row = mysqli_fetch_assoc($subResult)) { 
        $subKriteriaList[] = $row['id_sub']; 
    }
    $jumlahSub = count($subKriteriaList);

    mysqli_begin_transaction($conn);
    try {
        // Ambil urutan terakhir untuk auto-generate kode jika di excel kosong
        $kodeResult = mysqli_query($conn, "SELECT kode_alternatif FROM alternatif ORDER BY id_alternatif DESC LIMIT 1");
        $lastKode = mysqli_fetch_assoc($kodeResult);
        $num = $lastKode ? (int)substr($lastKode['kode_alternatif'], 1) + 1 : 1;
        
        // Loop mulai dari index 1 (karena index 0 adalah Header)
        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            
            // Berdasarkan Template: A = Kode, B = Instansi, C+ = Nilai Skala
            $kode_excel = trim($row[0] ?? '');
            $instansi = trim($row[1] ?? '');
            
            // Jika baris benar-benar kosong, lewati
            if (empty($kode_excel) && empty($instansi) && empty($row[2])) {
                continue; 
            }
            
            // Gunakan kode dari excel, jika kosong auto-generate
            $kode = !empty($kode_excel) ? $kode_excel : 'A' . str_pad($num++, 2, '0', STR_PAD_LEFT);
            
            // Insert data pendaftar (tanpa nama_pendaftar)
            $stmt = mysqli_prepare($conn, "INSERT INTO alternatif (kode_alternatif, asal_instansi) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmt, 'ss', $kode, $instansi);
            mysqli_stmt_execute($stmt);
            $idAlt = mysqli_insert_id($conn);
            
            // Insert nilai sub-kriteria (Mulai dari kolom C / index 2)
            for ($j = 0; $j < $jumlahSub; $j++) {
                $colIdx = $j + 2;
                $nilaiInput = isset($row[$colIdx]) ? (int)$row[$colIdx] : 0;
                
                // Pastikan nilai hanya skala 1-5 (Jika kosong/salah, default ke 1)
                if ($nilaiInput < 1 || $nilaiInput > 5) {
                    $nilaiInput = 1; 
                }
                
                $idSub = $subKriteriaList[$j];
                $stmtPen = mysqli_prepare($conn, "INSERT INTO penilaian (id_alternatif, id_sub, nilai_input) VALUES (?, ?, ?)");
                mysqli_stmt_bind_param($stmtPen, 'iii', $idAlt, $idSub, $nilaiInput);
                mysqli_stmt_execute($stmtPen);
            }
        }
        
        mysqli_commit($conn);
        $_SESSION['import_success'] = 'Import data berhasil diproses.';
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['import_error'] = 'Import gagal: Pastikan format file sesuai template. (' . $e->getMessage() . ')';
    }
    
    header("Location: index.php");
    exit;
} else {
    header("Location: index.php");
    exit;
}
?>