<?php
session_start();
// Cek apakah pemilih sudah login
if (!isset($_SESSION['voter_aktif'])) {
    header("Location: index.php");
    exit;
}
require 'koneksi.php';

$id_voter     = $_SESSION['id_voter'];
$id_pemilihan = $_SESSION['id_pemilihan'];

// --- LOGIKA PENCOBLOSAN (PROSES SUARA) ---
if (isset($_POST['coblos_kandidat'])) {
    // Pastikan status di sesi masih belum, untuk keamanan ganda
    if ($_SESSION['status_voting'] == 'belum') {
        $id_kandidat = $_POST['id_kandidat'];
        $waktu = date('Y-m-d H:i:s');
        
        // 1. Masukkan data ke tabel suara
        $stmt1 = $koneksi->prepare("INSERT INTO suara (id_pemilih, id_kandidat, id_pemilihan, waktu_pilih) VALUES (?, ?, ?, ?)");
        $stmt1->bind_param("iiis", $id_voter, $id_kandidat, $id_pemilihan, $waktu);
        $stmt1->execute();
        
         // 2. Ubah status pemilih menjadi sudah voting dan otomatis NONAKTIFKAN akunnya
        $stmt2 = $koneksi->prepare("UPDATE pemilih SET status_voting = 'sudah', status_aktif = 'nonaktif' WHERE id = ?");

        $stmt2->bind_param("i", $id_voter);
        $stmt2->execute();
        
        // 3. Perbarui sesi agar layar langsung berubah
        $_SESSION['status_voting'] = 'sudah';
        
        header("Location: dashboard.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bilik Suara - SMPN 219 Jakarta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8fafc; }
        .navbar-custom { background-color: #ffffff; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .kandidat-card { transition: transform 0.2s ease, box-shadow 0.2s ease; border: none; border-radius: 12px; }
        .kandidat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important; }
        .foto-kandidat { height: 250px; width: 100%; object-fit: contain; background-color: transparent; border-top-left-radius: 12px; border-top-right-radius: 12px; margin-top: 15px; }
        .nomor-badge { position: absolute; top: 15px; left: 15px; background: #0d6efd; color: white; width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: bold; border-radius: 50%; box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
    </style>
</head>
<body>

    <!-- Navbar Atas -->
    <nav class="navbar navbar-expand-lg navbar-custom sticky-top py-3">
        <div class="container">
            <span class="navbar-brand fw-bold text-primary">E-Voting OSIS</span>
            <div class="d-flex align-items-center">
                <span class="me-3 text-muted small d-none d-md-block">Halo, <b><?= htmlspecialchars($_SESSION['nama_voter']) ?></b></span>
                <a href="logout.php" class="btn btn-outline-danger btn-sm fw-bold">Keluar</a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        
        <?php if ($_SESSION['status_voting'] == 'sudah'): ?>
            <!-- TAMPILAN JIKA SUDAH MEMILIH -->
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6 text-center">
                    <div class="card border-0 shadow rounded-4 p-5">
                        <div class="mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" fill="#198754" class="bi bi-check-circle-fill" viewBox="0 0 16 16">
                              <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
                            </svg>
                        </div>
                        <h3 class="fw-bold text-dark">Terima Kasih!</h3>
                        <p class="text-muted">Hak suara Anda telah berhasil direkam ke dalam sistem secara rahasia. Partisipasi Anda sangat berarti bagi SMPN 219 Jakarta.</p>
                        <a href="logout.php" class="btn btn-secondary mt-3 px-4">Keluar dari Sistem</a>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- TAMPILAN JIKA BELUM MEMILIH (BILIK SUARA) -->
            <div class="text-center mb-5">
                <h2 class="fw-bold text-dark mb-2">Bilik Suara Digital</h2>
                <p class="text-muted">Silakan pelajari visi dan misi dari masing-masing kandidat, lalu tekan tombol Pilih pada kandidat pilihan Anda.</p>
            </div>

            <div class="row g-4 justify-content-center">
                <?php
                // Mengambil daftar kandidat khusus untuk acara ini
                $q_kandidat = $koneksi->query("SELECT * FROM kandidat WHERE id_pemilihan = '$id_pemilihan' ORDER BY no_urut ASC");
                
                if ($q_kandidat->num_rows == 0) {
                    echo '<div class="col-12 text-center text-muted">Belum ada kandidat yang terdaftar untuk pemilihan ini.</div>';
                }

                while ($kandidat = $q_kandidat->fetch_assoc()) {
                ?>
                
                <div class="col-12 col-md-4">
                    <div class="card kandidat-card shadow-sm h-100 position-relative">
                        <div class="nomor-badge"><?= $kandidat['no_urut'] ?></div>
                        <img src="assets/img/<?= $kandidat['foto'] ?>" class="card-img-top foto-kandidat" alt="Foto Kandidat">
                        
                        <div class="card-body d-flex flex-column p-4">
                            <h4 class="card-title fw-bold text-center mb-4"><?= htmlspecialchars($kandidat['nama_kandidat']) ?></h4>
                            
                            <div class="mb-3">
                                <h6 class="fw-bold text-primary small text-uppercase">Visi</h6>
                                <p class="text-muted small" style="text-align: justify;"><?= nl2br(htmlspecialchars($kandidat['visi'])) ?></p>
                            </div>
                            
                            <div class="mb-4 flex-grow-1">
                                <h6 class="fw-bold text-primary small text-uppercase">Misi</h6>
                                <p class="text-muted small" style="text-align: justify;"><?= nl2br(htmlspecialchars($kandidat['misi'])) ?></p>
                            </div>
                            
                            <!-- Tombol ini memicu Modal Konfirmasi -->
                            <button class="btn btn-primary w-100 py-2 fw-bold" data-bs-toggle="modal" data-bs-target="#konfirmasiModal<?= $kandidat['id'] ?>">
                                PILIH KANDIDAT NO. <?= $kandidat['no_urut'] ?>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modal Konfirmasi Pencoblosan -->
                <div class="modal fade" id="konfirmasiModal<?= $kandidat['id'] ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content rounded-4 border-0 shadow">
                            <div class="modal-header border-bottom-0 p-4">
                                <h5 class="modal-title fw-bold">Konfirmasi Pilihan</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body text-center px-4 pb-4">
                                <p class="text-muted mb-4">Apakah Anda yakin ingin memberikan suara untuk:</p>
                                <h4 class="fw-bold text-primary mb-1">Kandidat No. <?= $kandidat['no_urut'] ?></h4>
                                <h5 class="fw-bold text-dark mb-4"><?= htmlspecialchars($kandidat['nama_kandidat']) ?></h5>
                                <div class="alert alert-warning small py-2 mb-0 text-start">
                                    <b>Perhatian:</b> Pilihan yang sudah dikirim tidak dapat diubah atau dibatalkan.
                                </div>
                            </div>
                            <div class="modal-footer border-top-0 p-4 pt-0 justify-content-center">
                                <form action="" method="POST" class="w-100 d-flex gap-2">
                                    <input type="hidden" name="id_kandidat" value="<?= $kandidat['id'] ?>">
                                    <button type="button" class="btn btn-light w-50 fw-bold" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" name="coblos_kandidat" class="btn btn-success w-50 fw-bold">Ya, Saya Yakin</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <?php } ?>
            </div>
        <?php endif; ?>

    </div>

    <!-- Script Logout & Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
