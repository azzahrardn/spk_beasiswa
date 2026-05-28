<?php
session_start();
require_once '../../config/database.php';
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

[$conn, $dbError] = getDB();
if ($dbError) {
    die("Koneksi Database Gagal");
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Data Alternatif');

// 1. Definisikan Kolom Dasar
$headers = ['Kode Alternatif', 'Asal Instansi'];

// 2. Ambil Sub-Kriteria beserta Nilai Standar dari Database (Dinamis)
$subKriteriaResult = mysqli_query($conn, "SELECT nama_sub, nilai_standar FROM sub_kriteria ORDER BY id_sub ASC");
$jumlahSub = 0;
while ($sub = mysqli_fetch_assoc($subKriteriaResult)) {
    // Gabungkan nama sub-kriteria dengan nilai standarnya di baris baru
    $headers[] = $sub['nama_sub'] . "\n(Standar: " . $sub['nilai_standar'] . ")";
    $jumlahSub++;
}

// 3. Tulis Header ke Excel
$col = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($col . '1', $header);
    // Lebarkan sedikit kolomnya agar teks "Standar" muat
    $sheet->getColumnDimension($col)->setWidth(20);
    $col++;
}

// 4. Styling Header (Biru, Teks Putih, Tengah, dan Wrap Text)
$lastCol = chr(ord('A') + count($headers) - 1); // Cari huruf kolom terakhir (misal: J)
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER, 
        'vertical' => Alignment::VERTICAL_CENTER,
        'wrapText' => true // Mengizinkan baris baru (\n) di dalam sel
    ],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1D4ED8']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray($headerStyle);

// Tambahkan tinggi baris pertama agar teks header yang 2 baris tidak terpotong
$sheet->getRowDimension(1)->setRowHeight(40);

// 5. Validasi Angka Skala (1 - 5 BULAT) Dinamis untuk seluruh kolom sub-kriteria
if ($jumlahSub > 0) {
    $startColValid = 'C';
    $endColValid = $lastCol;
    
    $validation = $sheet->getCell($startColValid . '2')->getDataValidation();
    $validation->setType(DataValidation::TYPE_WHOLE); // Diganti menjadi angka bulat
    $validation->setErrorStyle(DataValidation::STYLE_STOP);
    $validation->setAllowBlank(true);
    $validation->setShowInputMessage(true);
    $validation->setShowErrorMessage(true);
    $validation->setErrorTitle('Input Tidak Valid');
    $validation->setError('Nilai harus berupa ANGKA SKALA BULAT (1, 2, 3, 4, atau 5). Tidak boleh desimal atau teks mentah.');
    $validation->setFormula1(1);
    $validation->setFormula2(5);
    $validation->setSqref($startColValid . '2:' . $endColValid . '100'); // Berlaku sampai baris 100
}

// Tambahkan Sheet Panduan (Instruction Sheet)
$sheetGuide = $spreadsheet->createSheet();
$sheetGuide->setTitle('Panduan Pengisian');

$guideContent = [
    ["PANDUAN PENGISIAN TEMPLATE DATA CALON PENERIMA"],
    [""],
    ["ATURAN KOLOM:"],
    ["Kolom A (Kode Alternatif)", "Wajib diisi dan harus unik. Contoh: A01, A02, A03."],
    ["Kolom B (Asal Instansi)", "Nama Perguruan Tinggi. Contoh: ITS Surabaya, UPN Veteran Jawa Timur."],
    ["Kolom C dan seterusnya", "Wajib diisi dengan ANGKA SKALA BULAT (1, 2, 3, 4, atau 5), bukan data mentah."],
    [""],
    ["CATATAN SANGAT PENTING (KONVERSI SKALA LIKERT):"],
    ["1. Sistem SPK Profile Matching hanya memproses angka skala bulat (1 sampai 5)."],
    ["2. Anda WAJIB mengonversi data asli pendaftar menjadi skala yang telah ditetapkan instansi."],
    ["   - Contoh Salah: Menuliskan 'Rp 3.000.000' pada kolom Gaji Ayah, atau '3.85' pada kolom IPK."],
    ["   - Contoh Benar: Menuliskan angka '3' (untuk Gaji Ayah) atau angka '5' (untuk IPK)."],
    ["3. DILARANG menggunakan angka desimal. Pastikan format sel di Excel adalah General/Number biasa."],
    ["4. JANGAN mengubah urutan judul kolom (Header) di sheet Data Alternatif karena akan membuat sistem salah membaca nilai."]
];

$rowGuide = 1;
foreach ($guideContent as $rowData) {
    $colGuide = 'A';
    foreach ($rowData as $cellData) {
        $sheetGuide->setCellValue($colGuide . $rowGuide, $cellData);
        $colGuide++;
    }
    $rowGuide++;
}

// Styling Sheet Panduan
$sheetGuide->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheetGuide->getStyle('A3')->getFont()->setBold(true);
$sheetGuide->getStyle('A8')->getFont()->setBold(true)->getColor()->setARGB('FF991B1B'); // Warna merah untuk peringatan
$sheetGuide->getColumnDimension('A')->setWidth(35);
$sheetGuide->getColumnDimension('B')->setWidth(80);

// 6. Set focus ke Sheet Panduan (Index 1) saat file pertama kali dibuka
$spreadsheet->setActiveSheetIndex(1);

// 7. Buat File dan Lempar ke Browser untuk di-download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="Template_SPK_Beasiswa.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>