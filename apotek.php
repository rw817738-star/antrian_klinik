<?php
// 1. KONEKSI KE DATABASE KHANZA
$host = "localhost";
$user = "root";  
$pass = "root";      
$db   = "sik";   

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi Database Gagal: " . $e->getMessage());
}

$pesan_notif = "";

// 2. LOGIKA TOMBOL AKSI APOTEKER (UBAH STATUS MENJADI SELESAI, BUKAN DIHAPUS)
if (isset($_POST['aksi'])) {
    $aksi = $_POST['aksi'];
    $resep_target = $_POST['no_resep'];

    if ($aksi == 'selesai' && !empty($resep_target)) {
        // PERBAIKAN: Mengubah status di tabel pendukung antri_apotek menjadi 'Selesai'
        $sql_update = "UPDATE antri_apotek SET status_ambil = 'Selesai' 
                       WHERE no_resep = :no_resep AND tgl_panggilan = CURDATE()";
        $stmt_update = $pdo->prepare($sql_update);
        $stmt_update->execute([':no_resep' => $resep_target]);
        $pesan_notif = "Resep Nomor " . $resep_target . " sukses diserahkan ke pasien.";
    }
}

// 3. AMBIL DATA NOMOR RESEP TERDEPAN YANG STATUSNYA 'Belum' DIAMBIL HARI INI
$sql_sekarang = "SELECT no_resep FROM antri_apotek 
                 WHERE tgl_panggilan = CURDATE() AND status_ambil = 'Belum' 
                 ORDER BY no_resep ASC LIMIT 1";
$stmt_sekarang = $pdo->query($sql_sekarang);
$row_sekarang = $stmt_sekarang->fetch(PDO::FETCH_ASSOC);

$resep_aktif = $row_sekarang ? $row_sekarang['no_resep'] : null;
$resep_aktif_format = $row_sekarang ? $row_sekarang['no_resep'] : "-";

// 4. HITUNG SISA OBAT RESEP YANG MASIH ANTRI UNTUK DIAMBIL
$sql_sisa = "SELECT COUNT(*) as total FROM antri_apotek 
             WHERE tgl_panggilan = CURDATE() AND status_ambil = 'Belum'";
$stmt_sisa = $pdo->query($sql_sisa);
$total_sisa = $stmt_sisa->fetch(PDO::FETCH_ASSOC)['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dasbor Panggilan Obat - Farmasi Apotek</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { background-color: #f0fdf4; padding: 30px; }
        .dasbor-box-farmasi { max-width: 600px; margin: 50px auto; background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 25px rgba(5, 150, 105, 0.15); text-align: center; border: 1px solid #a7f3d0; }
        .farmasi-title { font-size: 24px; font-weight: 800; color: #065f46; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 1px; }
        .nomor-display-farmasi { font-size: 80px; font-weight: 900; color: #059669; margin: 20px 0; font-family: monospace; }
        .btn-panggil-farmasi { background: #059669; color: white; border: none; padding: 20px; font-size: 22px; font-weight: 700; width: 100%; border-radius: 12px; cursor: pointer; box-shadow: 0 5px 15px rgba(5, 150, 105, 0.3); transition: background 0.2s; }
        .btn-panggil-farmasi:hover { background: #047857; }
        .btn-selesai-farmasi { background: #22c55e; color: white; border: none; padding: 15px; font-size: 18px; font-weight: 700; width: 100%; border-radius: 12px; cursor: pointer; margin-top: 15px; box-shadow: 0 5px 15px rgba(34, 197, 94, 0.2); }
        .btn-selesai-farmasi:hover { background: #16a34a; }
        .badge-sisa-farmasi { display: inline-block; background-color: #d1fae5; color: #065f46; padding: 8px 15px; border-radius: 20px; font-weight: 700; margin-top: 25px; border: 1px solid #a7f3d0; }
        .notif-farmasi { background-color: #dcfce7; color: #166534; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; font-size: 14px; }
    </style>
</head>
<body>

    <div class="dasbor-box-farmasi">
        <div class="farmasi-title">Dasbor Apoteker - Penyerahan Obat</div>
        
        <?php if (!empty($pesan_notif)): ?>
            <div class="notif-farmasi"><?php echo $pesan_notif; ?></div>
        <?php endif; ?>

        <p style="color: #666; font-weight: 600; letter-spacing: 1px; text-transform: uppercase;">Nomor Resep Terdepan</p>
        
        <div class="nomor-display-farmasi"><?php echo $resep_aktif_format; ?></div>

        <button type="button" class="btn-panggil-farmasi" onclick="panggilSuaraApotek('<?php echo $resep_aktif; ?>')">
            🔊 PANGGIL PASIEN
        </button>

        <form action="apotek.php" method="POST">
            <input type="hidden" name="no_resep" value="<?php echo $resep_aktif; ?>">
            <button type="submit" name="aksi" value="selesai" class="btn-selesai-farmasi" <?php echo !$resep_aktif ? 'disabled' : ''; ?>>
                ✓ SELESAI & OBAT DISERAHKAN
            </button>
        </form>

        <div class="badge-sisa-farmasi">
            Sisa resep antri diambil: <?php echo $total_sisa; ?> resep
        </div>
    </div>

    <script>
        function panggilSuaraApotek(noResep) {
            if (!noResep || noResep === "") {
                alert("Tidak ada nomor resep yang mengantre untuk dipanggil!");
                return;
            }

            const teksPanggilan = 'Nomor resep ' + noResep + ', silakan mengambil obat di Loket Penyerahan Obat';
            window.speechSynthesis.cancel();

            const audioBel = new Audio('audio/bel.mp3');
            audioBel.play().then(() => {
                audioBel.onended = function() {
                    const mesinSuara = new SpeechSynthesisUtterance(teksPanggilan);
                    mesinSuara.lang = 'id-ID';
                    mesinSuara.rate = 0.9;
                    window.speechSynthesis.speak(mesinSuara);
                };
            }).catch(error => {
                const mesinSuara = new SpeechSynthesisUtterance(teksPanggilan);
                mesinSuara.lang = 'id-ID';
                window.speechSynthesis.speak(mesinSuara);
            });
        }
    </script>

</body>
</html>
