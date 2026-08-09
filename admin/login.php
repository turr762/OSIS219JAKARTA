<?php
// Memulai sesi untuk menyimpan data login
session_start();

// Memanggil file koneksi (menggunakan ../ karena file koneksi ada di luar folder admin)
require '../koneksi.php';

// Jika admin sudah login sebelumnya, langsung arahkan ke halaman utama (index.php)
if (isset($_SESSION['admin_aktif'])) {
    header("Location: index.php");
    exit;
}

$pesan_error = "";

// Jika tombol login ditekan
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Mencari data admin di database
    $stmt = $koneksi->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $hasil = $stmt->get_result();

    // Jika username ditemukan
    if ($hasil->num_rows === 1) {
        $baris = $hasil->fetch_assoc();
        
        // Memeriksa kecocokan password yang dienkripsi (Hash)
        if (password_verify($password, $baris['password'])) {
            // Menyimpan tanda pengenal ke dalam sesi
            $_SESSION['admin_aktif'] = true;
            $_SESSION['id_admin'] = $baris['id'];
            $_SESSION['nama_admin'] = $baris['nama_admin'];
            
            // Pindahkan ke dashboard
            header("Location: index.php");
            exit;
        } else {
            $pesan_error = "Password yang dimasukkan salah!";
        }
    } else {
        $pesan_error = "Username tidak ditemukan di sistem!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Pemilu OSIS</title>
    <!-- Memanggil Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f8fb; /* Putih kebiruan */
        }
        .kotak-login {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.05);
        }
        .btn-utama {
            background-color: #0d6efd;
            color: white;
            border-radius: 8px;
        }
        .btn-utama:hover {
            background-color: #0b5ed7;
            color: white;
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center vh-100">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-11 col-md-6 col-lg-4">
                <div class="kotak-login p-4 p-md-5">
                    
                    <div class="text-center mb-4">
                        <h4 class="fw-bold text-primary mb-1">Panel Panitia</h4>
                        <p class="text-muted small">Pemilihan Ketua OSIS</p>
                    </div>

                    <!-- Menampilkan notifikasi jika ada error -->
                    <?php if ($pesan_error): ?>
                        <div class="alert alert-danger py-2 text-center small" role="alert">
                            <?= $pesan_error ?>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST">
                        <div class="mb-3">
                            <label class="form-label text-secondary small fw-bold">Username</label>
                            <input type="text" name="username" class="form-control" required autocomplete="off">
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-secondary small fw-bold">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" name="login" class="btn btn-utama w-100 fw-bold py-2">Masuk Sistem</button>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <!-- Script Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
