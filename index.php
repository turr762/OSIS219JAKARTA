<?php
session_start();
require 'koneksi.php';

// Atur zona waktu ke WIB (Jakarta) agar validasi waktu acara sangat akurat
date_default_timezone_set('Asia/Jakarta');
$waktu_sekarang = date('Y-m-d H:i:s');

// Jika voter sudah login sebelumnya, langsung lempar ke halaman pencoblosan
if (isset($_SESSION['voter_aktif'])) {
    header("Location: dashboard.php");
    exit;
}

$pesan_error = "";

// Jika tombol masuk ditekan
if (isset($_POST['login'])) {
    $nis = $_POST['nis'];
    $password = $_POST['password'];

    // Cari data pemilih berdasarkan NIS / NIP
    $stmt = $koneksi->prepare("SELECT * FROM pemilih WHERE nis = ?");
    $stmt->bind_param("s", $nis);
    $stmt->execute();
    $hasil = $stmt->get_result();

    if ($hasil->num_rows === 1) {
        $baris = $hasil->fetch_assoc();
        
        // Cek kecocokan password
        if (password_verify($password, $baris['password'])) {
            
            // Cek apakah akunnya tidak diblokir/nonaktif
            if ($baris['status_aktif'] == 'nonaktif') {
                $pesan_error = "Akun Anda telah dinonaktifkan oleh panitia.";
            } else {
                
                // Ambil data acara pemilihan milik voter ini
                $id_pemilihan = $baris['id_pemilihan'];
                $q_acara = $koneksi->query("SELECT * FROM pemilihan WHERE id = '$id_pemilihan'");
                
                if ($q_acara->num_rows > 0) {
                    $acara = $q_acara->fetch_assoc();
                    
                    // Validasi Waktu Acara
                    if ($waktu_sekarang < $acara['tanggal_mulai']) {
                        $pesan_error = "Pemilihan belum dimulai. Jadwal: " . date('d M Y, H:i', strtotime($acara['tanggal_mulai']));
                    } elseif ($waktu_sekarang > $acara['tanggal_selesai']) {
                        $pesan_error = "Waktu pemilihan telah berakhir. Akses ditutup.";
                    } else {
                        // Lolos semua ujian, izinkan masuk (Buat Sesi)
                        $_SESSION['voter_aktif']   = true;
                        $_SESSION['id_voter']      = $baris['id'];
                        $_SESSION['nama_voter']    = $baris['nama_siswa'];
                        $_SESSION['role_voter']    = $baris['role'];
                        $_SESSION['id_pemilihan']  = $id_pemilihan;
                        $_SESSION['status_voting'] = $baris['status_voting'];
                        
                        header("Location: dashboard.php");
                        exit;
                    }
                } else {
                    $pesan_error = "Data acara pemilihan tidak ditemukan di sistem.";
                }
            }
        } else {
            $pesan_error = "Password yang Anda masukkan salah.";
        }
    } else {
        $pesan_error = "NIS / NIP tidak terdaftar di sistem kami.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Voting - SMPN 219 Jakarta</title>
    <!-- Memanggil Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f0f2f5;
        }
        .login-box {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
        .btn-primary {
            background-color: #0d6efd;
            border: none;
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center vh-100">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-11 col-md-6 col-lg-4">
                <div class="login-box p-4 p-md-5">
                    
                    <div class="text-center mb-4">
                        <h4 class="fw-bold text-primary mb-1">E-Voting OSIS</h4>
                        <p class="text-muted small">SMPN 219 Jakarta</p>
                    </div>

                    <!-- Tempat Notifikasi Error -->
                    <?php if ($pesan_error): ?>
                        <div class="alert alert-danger py-2 text-center small" role="alert">
                            <?= $pesan_error ?>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST">
                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-bold">NIS / NIP</label>
                            <input type="text" name="nis" class="form-control" placeholder="Masukkan NIS / NIP" required autocomplete="off">
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-secondary small fw-bold">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Masukkan Password" required>
                        </div>
                        <button type="submit" name="login" class="btn btn-primary w-100 fw-bold py-2">Masuk untuk Memilih</button>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <!-- Script Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
