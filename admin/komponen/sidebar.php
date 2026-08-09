<!-- Wrapper Pembungkus Layout -->
<div class="d-flex">
    
    <!-- Sidebar Menu Gelap -->
    <div class="sidebar d-flex flex-column" style="width: 250px; flex-shrink: 0;">
        <div class="sidebar-brand p-4 text-center">
            <h5 class="mb-0">PANITIA OSIS</h5>
        </div>
        
        <ul class="nav flex-column mb-auto mt-3">
            <li class="nav-item">
                <a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'aktif' : '' ?>">Dashboard</a>
            </li>
            <li class="nav-item">
                <a href="kandidat.php" class="<?= basename($_SERVER['PHP_SELF']) == 'kandidat.php' ? 'aktif' : '' ?>">1. Kandidat</a>
            </li>
            <li class="nav-item">
                <a href="voter.php" class="<?= basename($_SERVER['PHP_SELF']) == 'voter.php' ? 'aktif' : '' ?>">2. Voter</a>
            </li>
            <li class="nav-item">
                <a href="pemilihan.php" class="<?= basename($_SERVER['PHP_SELF']) == 'pemilihan.php' ? 'aktif' : '' ?>">3. Pemilihan</a>
            </li>
            <li class="nav-item">
                <a href="hasil_suara.php" class="<?= basename($_SERVER['PHP_SELF']) == 'hasil_suara.php' ? 'aktif' : '' ?>">4. Hasil Suara</a>
            </li>
            <li class="nav-item">
                <a href="laporan.php" class="<?= basename($_SERVER['PHP_SELF']) == 'laporan.php' ? 'aktif' : '' ?>">5. Laporan</a>
            </li>
        </ul>
        
        <div class="p-3 mt-auto">
            <a href="logout.php" class="btn btn-danger w-100 fw-bold shadow-sm">Keluar</a>
        </div>
    </div>

    <!-- Area Kanan (Topbar + Konten) -->
    <div class="flex-grow-1 d-flex flex-column" style="height: 100vh; overflow-y: auto;">
        
        <!-- Top Navbar Putih -->
        <div class="topbar d-flex justify-content-between align-items-center sticky-top">
            <h5 class="mb-0 text-secondary fw-bold"></h5>
            <div class="d-flex align-items-center">
                <span class="text-muted small me-2"><bold>Halo,</bold></span>
                <span class="fw-bold text-dark bg-light px-3 py-1 rounded-pill border">
                    <?= htmlspecialchars($_SESSION['nama_admin']) ?>
                </span>
            </div>
        </div>

        <!-- Padding untuk Konten Utama -->
        <div class="p-4 flex-grow-1">
