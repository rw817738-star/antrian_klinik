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

$nomor_tercetak = "";
$loket_terpilih = "";

// 2. PROSES KETIKA TOMBOL LAYAR SENTUH DI-KLIK
if (isset($_POST['ambil_antrian'])) {
    $jenis_layanan = $_POST['ambil_antrian']; // 'BPJS' or 'UMUM'
    $tanggal_hari_ini = date('Y-m-d');

    try {
        // Tentukan nomor loket
        $id_loket = ($jenis_layanan == 'BPJS') ? 1 : 2;

        // PENTING: Kita mencari nomor terakhir yang pernah dicetak HARI INI pada loket tersebut.
        // Jika tabel kosong karena dihapus petugas loket, kita berpatokan pada nomor urut komparasi internal.
        $sql_cek = "SELECT MAX(antrian) as terakhir FROM antriloket WHERE loket = :loket AND tgl_registrasi = :tgl";
        $stmt_cek = $pdo->prepare($sql_cek);
        $stmt_cek->execute([':loket' => $id_loket, ':tgl' => $tanggal_hari_ini]);
        $row = $stmt_cek->fetch(PDO::FETCH_ASSOC);

        $berikutnya = ($row['terakhir']) ? intval($row['terakhir']) + 1 : 1;

        // B. Masukkan data ke tabel antriloket lengkap dengan tanggal hari ini
        $sql_insert = "INSERT INTO antriloket (loket, antrian, tgl_registrasi) VALUES (:loket, :antrian, :tgl)";
        $stmt_insert = $pdo->prepare($sql_insert);
        $stmt_insert->execute([
            ':loket'   => $id_loket,
            ':antrian' => $berikutnya,
            ':tgl'     => $tanggal_hari_ini
        ]);

        // C. Siapkan data untuk dicetak di struk fisik
        $nomor_tercetak = str_pad($berikutnya, 3, "0", STR_PAD_LEFT);
        $loket_terpilih = "LOKET " . $id_loket . " (" . $jenis_layanan . ")";

    } catch (PDOException $e) {
        echo "Gagal menyimpan antrian loket: " . $e->getMessage();
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mesin Kios Antrian Loket</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { background-color: #fff7ed; padding: 20px; }
        .kios-container { max-width: 800px; margin: 50px auto; text-align: center; }
        .kios-title { color: #9a3412; font-size: 36px; font-weight: 800; margin-bottom: 15px; }
        .grid-kios { display: grid; grid-template-columns: repeat(2, 1fr); gap: 30px; margin-top: 50px; }
        .btn-kios {
            background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%);
            color: white; border: none; padding: 60px 20px;
            font-size: 28px; font-weight: 800; border-radius: 25px;
            cursor: pointer; box-shadow: 0 10px 15px -3px rgba(234, 88, 12, 0.3);
            text-transform: uppercase; transition: transform 0.1s;
        }
        .btn-kios:active { transform: scale(0.95); }
        .modal-struk {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.7); display: flex; align-items: center; justify-content: center;
        }
        .struk-box { background: white; padding: 40px; border-radius: 15px; width: 350px; text-align: center; color: #333;}
        .nomor-struk { font-size: 80px; font-weight: 900; color: #ea580c; margin: 20px 0; border-top: 2px dashed #333; border-bottom: 2px dashed #333; padding: 10px 0; }
    </style>
</head>
<body>

    <div class="kios-container">
        <div class="kios-title">MESIN AMBIL TIKET PENDAFTARAN</div>
        <p style="color: #c2410c; font-size: 20px; font-weight: 600;">Silakan pilih jenis layanan pendaftaran Anda:</p>

        <form action="kios.php" method="POST" class="grid-kios">
            <button type="submit" name="ambil_antrian" value="BPJS" class="btn-kios">💳 PASIEN BPJS</button>
            <button type="submit" name="ambil_antrian" value="UMUM" class="btn-kios">💵 PASIEN UMUM</button>
        </form>
    </div>

    <?php if (!empty($nomor_tercetak)): ?>
        <div class="modal-struk" id="popupStruk">
            <div class="struk-box">
                <h3 style="font-weight: 800;">KLINIK PRATAMA ASISI</h3>
                <hr style="margin: 15px 0; border-top: 1px dashed #333;">
                <p style="font-weight: bold; font-size: 14px;"><?php echo $loket_terpilih; ?></p>
                <div class="nomor-struk"><?php echo $nomor_tercetak; ?></div>
                <p>Silakan tunggu nomor Anda dipanggil di Loket Pendaftaran.</p>
                <p style="font-size: 11px; color:#888; margin-top: 15px;"><?php echo date('d-m-Y H:i:s'); ?></p>
                <hr style="margin: 15px 0; border-top: 1px dashed #333;">
                <p style="font-weight: bold; color: #ea580c;">TERIMA KASIH</p>
            </div>
        </div>
        <script>
            window.print();
            setTimeout(function() { window.location.href = 'kios.php'; }, 3000);
        </script>
    <?php endif; ?>

</body>
</html>
