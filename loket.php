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

// AMBIL NOMOR LOKET BERDASARKAN URL (Contoh: loket.php?id=1 atau loket.php?id=2)
// Jika tidak ada di URL, otomatis default ke Loket 1 (BPJS)
$loket_aktif = isset($_GET['id']) ? intval($_GET['id']) : 1; 
$nama_loket_layanan = ($loket_aktif == 1) ? "BPJS" : "UMUM";

// 2. LOGIKA TOMBOL AKSI PETUGAS
$pesan_notif = "";

if (isset($_POST['aksi'])) {
    $aksi = $_POST['aksi'];
    $antrian_target = $_POST['nomor_antrian'];

    if ($aksi == 'selesai' && !empty($antrian_target)) {
        // Hapus antrian dari tabel antriloket jika pelayanan pendaftaran sudah selesai
        $sql_delete = "DELETE FROM antriloket WHERE loket = :loket AND antrian = :antrian";
        $stmt_delete = $pdo->prepare($sql_delete);
        $stmt_delete->execute([
            ':loket'   => $loket_aktif,
            ':antrian' => $antrian_target
        ]);
        $pesan_notif = "Antrian " . str_pad($antrian_target, 3, "0", STR_PAD_LEFT) . " selesai dilayani.";
    }
}

// 3. AMBIL DATA ANTRIAN AKTIF SAAT INI UNTUK LOKET YANG TERPILIH
$sql_sekarang = "SELECT antrian FROM antriloket WHERE loket = :loket ORDER BY antrian ASC LIMIT 1";
$stmt_sekarang = $pdo->prepare($sql_sekarang);
$stmt_sekarang->execute([':loket' => $loket_aktif]);
$row_sekarang = $stmt_sekarang->fetch(PDO::FETCH_ASSOC);

$nomor_aktif = $row_sekarang ? $row_sekarang['antrian'] : null;
$nomor_aktif_format = $row_sekarang ? str_pad($row_sekarang['antrian'], 3, "0", STR_PAD_LEFT) : "-";

// 4. HITUNG TOTAL SISA ANTRIAN YANG MASIH MENUNGGU DI LOKET INI
$sql_sisa = "SELECT COUNT(*) as total FROM antriloket WHERE loket = :loket";
$stmt_sisa = $pdo->prepare($sql_sisa);
$stmt_sisa->execute([':loket' => $loket_aktif]);
$total_sisa = $stmt_sisa->fetch(PDO::FETCH_ASSOC)['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dasbor Panggil Antrian Loket</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { background-color: #fff7ed; padding: 30px; }
        .nav-loket { max-width: 600px; margin: 0 auto 20px auto; display: flex; gap: 10px; justify-content: center; }
        .nav-link { padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: bold; background-color: #fed7aa; color: #7c2d12; border: 1px solid #f97316; }
        .nav-link.active { background-color: #ea580c; color: white; }
        .dasbor-box { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 25px rgba(234, 88, 12, 0.15); text-align: center; border: 1px solid #fed7aa; }
        .loket-title { font-size: 24px; font-weight: 800; color: #9a3412; margin-bottom: 20px; text-transform: uppercase; }
        .nomor-display { font-size: 90px; font-weight: 900; color: #ea580c; margin: 20px 0; font-family: monospace; }
        .btn-panggil { background: #ea580c; color: white; border: none; padding: 20px; font-size: 22px; font-weight: 700; width: 100%; border-radius: 12px; cursor: pointer; box-shadow: 0 5px 15px rgba(234, 88, 12, 0.3); transition: background 0.2s; }
        .btn-panggil:hover { background: #c2410c; }
        .btn-selesai { background: #22c55e; color: white; border: none; padding: 15px; font-size: 18px; font-weight: 700; width: 100%; border-radius: 12px; cursor: pointer; margin-top: 15px; box-shadow: 0 5px 15px rgba(34, 197, 94, 0.2); }
        .btn-selesai:hover { background: #16a34a; }
        .badge-sisa { display: inline-block; background-color: #ffedd5; color: #9a3412; padding: 8px 15px; border-radius: 20px; font-weight: 700; margin-top: 25px; border: 1px solid #fed7aa; }
        .notif { background-color: #dcfce7; color: #166534; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; font-size: 14px; }
    </style>
</head>
<body>

    <!-- NAVIGASI PILIHAN MEJA LOKET -->
    <div class="nav-loket">
        <a href="loket.php?id=1" class="nav-link <?php echo ($loket_aktif == 1) ? 'active' : ''; ?>">Buka Loket 1 (BPJS)</a>
        <a href="loket.php?id=2" class="nav-link <?php echo ($loket_aktif == 2) ? 'active' : ''; ?>">Buka Loket 2 (UMUM)</a>
    </div>

    <!-- BOX OPERASIONAL PANGGILAN -->
    <div class="dasbor-box">
        <div class="loket-title">Dasbor Pemanggilan - Loket <?php echo $loket_aktif; ?> (<?php echo $nama_loket_layanan; ?>)</div>
        
        <?php if (!empty($pesan_notif)): ?>
            <div class="notif"><?php echo $pesan_notif; ?></div>
        <?php endif; ?>

        <p style="color: #666; font-weight: 600; letter-spacing: 1px; text-transform: uppercase;">Antrian Terdepan</p>
        
        <div class="nomor-display"><?php echo $nomor_aktif_format; ?></div>

        <button type="button" class="btn-panggil" onclick="panggilSuaraLoket('<?php echo $nomor_aktif; ?>', '<?php echo $loket_aktif; ?> (<?php echo $nama_loket_layanan; ?>)')">
            🔊 PANGGIL NOMOR
        </button>

        <form action="loket.php?id=<?php echo $loket_aktif; ?>" method="POST">
            <input type="hidden" name="nomor_antrian" value="<?php echo $nomor_aktif; ?>">
            <button type="submit" name="aksi" value="selesai" class="btn-selesai" <?php echo !$nomor_aktif ? 'disabled' : ''; ?>>
                ✓ SELESAI & LANJUT
            </button>
        </form>

        <div class="badge-sisa">
            Sisa antrian di loket ini: <?php echo $total_sisa; ?> orang
        </div>
    </div>

    <script>
        function panggilSuaraLoket(nomor, loketText) {
            if (!nomor || nomor === "") {
                alert("Tidak ada antrian yang bisa dipanggil!");
                return;
            }

            const nomorBersih = parseInt(nomor, 10);
            const teksPanggilan = 'Nomor antrian ' + nomorBersih + ', silakan menuju loket ' + loketText;
            
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
