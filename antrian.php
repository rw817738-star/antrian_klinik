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

// 2. LOGIKA API INTERNAL (JIKA DIMINTA OLEH AJAX)
if (isset($_GET['get_data'])) {
    header('Content-Type: application/json');
    $response = [];

    // A. ANTRIAN LOKET PENDAFTARAN (Tabel antriloket)
    $sql_l1 = "SELECT antrian FROM antriloket WHERE loket = 1 AND tgl_registrasi = CURDATE() AND status_panggil = 'Belum' ORDER BY antrian ASC LIMIT 1";
    $stmt_l1 = $pdo->query($sql_l1);
    $row_l1 = $stmt_l1->fetch(PDO::FETCH_ASSOC);
    $response['LOKET_1'] = $row_l1 ? str_pad($row_l1['antrian'], 3, "0", STR_PAD_LEFT) : "-";

    $sql_l2 = "SELECT antrian FROM antriloket WHERE loket = 2 AND tgl_registrasi = CURDATE() AND status_panggil = 'Belum' ORDER BY antrian ASC LIMIT 1";
    $stmt_l2 = $pdo->query($sql_l2);
    $row_l2 = $stmt_l2->fetch(PDO::FETCH_ASSOC);
    $response['LOKET_2'] = $row_l2 ? str_pad($row_l2['antrian'], 3, "0", STR_PAD_LEFT) : "-";

    // B. ANTRIAN POLI KLINIK DOKTER (Tabel reg_periksa)
    $daftar_poli = [
        'U0001', // Poli Umum
        'G0001', // Poli Gigi Umum
        'L0001', // Laboratorium
        'K0002'  // Poli Kecantikan
    ];
    
    foreach ($daftar_poli as $kd_poli) {
        $sql_poli = "SELECT no_reg FROM reg_periksa 
                     WHERE tgl_registrasi = CURDATE() 
                       AND kd_poli = :kd_poli 
                       AND stts = 'Belum' 
                     ORDER BY no_reg ASC LIMIT 1";
        
        $stmt_poli = $pdo->prepare($sql_poli);
        $stmt_poli->execute([':kd_poli' => $kd_poli]);
        $row_poli = $stmt_poli->fetch(PDO::FETCH_ASSOC);
        
        $response[$kd_poli] = $row_poli ? $row_poli['no_reg'] : "-";
    }

    // C. ANTRIAN AMBIL OBAT APOTEK (Tabel pendukung antri_apotek)
    $sql_apotek = "SELECT no_resep FROM antri_apotek 
                   WHERE tgl_panggilan = CURDATE() AND status_ambil = 'Belum' 
                   ORDER BY no_resep ASC LIMIT 1";
    $stmt_apotek = $pdo->query($sql_apotek);
    $row_apotek = $stmt_apotek->fetch(PDO::FETCH_ASSOC);
    $response['APOTEK'] = $row_apotek ? $row_apotek['no_resep'] : "-";
    
    echo json_encode($response);
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Layar Monitor Antrian Utama - Klinik Asisi</title>
    <!-- Memanggil CSS terpisah -->
    <link rel="stylesheet" href="css/style.css?v=5">
</head>
<body>

    <!-- HEADER UTAMA -->
    <header class="main-header">
        <div class="brand">
            <h1>KLINIK ASISI <span>AI</span></h1>
            <p>KLINIK PRATAMA ASISI</p>
        </div>
        <div class="live-clock">
            <div class="time" id="jam-digital">00:00:00</div>
            <div class="date"><?php echo date('l, d F Y'); ?></div>
        </div>
    </header>

    <!-- RUNNING TEXT -->
    <div class="info-bar">
        <div class="marquee-text">
            Selamat Datang di KLINIK PRATAMA ASISI • Silakan perhatikan nomor pendaftaran, antrean dokter, dan loket penyerahan obat Anda.
        </div>
    </div>

    <!-- MAIN CONTAINER (STRUKTUR BARU ATAS DAN BAWAH) -->
    <div class="main-monitor-layout">
        
        <!-- ================= BAGIAN ATAS ================= -->
        <div class="area-top">
            
            <!-- SUB-KOLOM KIRI: LOKET 1 & APOTEK -->
            <aside class="sub-column-left">
                <div class="section-title">Pendaftaran Loket</div>
                <div class="card-antrian bg-orange-dark">
                    <div class="card-header-antrian">Panggilan Utama</div>
                    <div class="card-number" data-id="LOKET_1">-</div>
                    <div class="card-footer-antrian">Loket 1 (BPJS)</div>
                </div>
              <!-- Loket 2 Umum dipindah ke bawah berjejer dengan poli -->
                <div class="card-antrian bg-orange-dark">
                    <div class="card-header-antrian">Panggilan Loket</div>
                    <div class="card-number" data-id="LOKET_2">-</div>
                    <div class="card-footer-antrian">Loket 2 (UMUM)</div>
                </div>
                
            </aside>

            <!-- SUB-KOLOM KANAN: FULL VIDEO LOKAL 16:9 -->
            <section class="sub-column-video">
                <div class="section-title">Informasi & Edukasi Kesehatan</div>
                <div class="video-container">
                    <video autoplay loop muted playsinline>
                        <source src="video/klinik.mp4" type="video/mp4">
                        Browser Anda tidak mendukung pemutar video lokal.
                    </video>
                </div>
            </section>

        </div>

        <!-- ================= BAGIAN BAWAH ================= -->
        <div class="area-bottom">
            <div class="section-title">Ruang Tunggu Pemeriksaan Dokter & Loket Umum</div>
            
            <!-- 5 KARTU BERJEJER HORIZONTAL SAMPAI UJUNG -->
            <div class="grid-horizontal-poli">
                
                
                <div class="card-antrian bg-emerald">
                    <div class="card-header-antrian">Resep Siap Diambil</div>
                    <div class="card-number" data-id="APOTEK">-</div>
                    <div class="card-footer-antrian">Loket Apotek</div>
                </div>

                <div class="card-antrian">
                    <div class="card-header-antrian">Nomor Antrian</div>
                    <div class="card-number" data-id="U0001">-</div>
                    <div class="card-footer-antrian">Poli Umum</div>
                </div>

                <div class="card-antrian bg-amber">
                    <div class="card-header-antrian">Nomor Antrian</div>
                    <div class="card-number" data-id="G0001">-</div>
                    <div class="card-footer-antrian">Poli Gigi</div>
                </div>

                <div class="card-antrian bg-amber">
                    <div class="card-header-antrian">Nomor Antrian</div>
                    <div class="card-number" data-id="L0001">-</div>
                    <div class="card-footer-antrian">Poli KIA</div>
                </div>

                <div class="card-antrian">
                    <div class="card-header-antrian">Nomor Antrian</div>
                    <div class="card-number" data-id="K0002">-</div>
                    <div class="card-footer-antrian">Laboratorium</div>
                </div>

            </div>
        </div>

    </div>

    <!-- FOOTER -->
    <footer class="main-footer">
        &copy; 2026 Smart Queue System • Terintegrasi Database SIMKES Khanza
    </footer>

    <!-- Memanggil JS terpisah Anda -->
    <script src="js/antrian.js?v=5"></script>
</body>
</html>
