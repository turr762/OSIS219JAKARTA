<?php
session_start();
// Cek apakah admin sudah login
if (!isset($_SESSION['admin_aktif'])) {
    header("Location: login.php");
    exit;
}

// Memanggil file koneksi untuk mengambil data statistik
require '../koneksi.php';

// Menghitung statistik dari database
$query_voter    = $koneksi->query("SELECT COUNT(*) as total FROM pemilih");
$total_voter    = $query_voter->fetch_assoc()['total'];

$query_sudah    = $koneksi->query("SELECT COUNT(*) as total FROM pemilih WHERE status_voting = 'sudah'");
$total_sudah    = $query_sudah->fetch_assoc()['total'];

$query_belum    = $koneksi->query("SELECT COUNT(*) as total FROM pemilih WHERE status_voting = 'belum'");
$total_belum    = $query_belum->fetch_assoc()['total'];

$query_kandidat = $koneksi->query("SELECT COUNT(*) as total FROM kandidat");
$total_kandidat = $query_kandidat->fetch_assoc()['total'];

// Memanggil template atas dan menu samping
include 'komponen/header.php';
include 'komponen/sidebar.php';
?>

<!-- Baris Kartu Statistik -->
<div class="row g-3 mb-4">
    
    <!-- Kartu Total Voter -->
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 bg-white h-100">
            <div class="card-body p-3 text-center">
                <h2 class="text-utama fw-bold mb-0"><?= $total_voter ?></h2>
                <p class="text-muted small mb-0 mt-1">Total Akun Voter</p>
            </div>
        </div>
    </div>
    
    <!-- Kartu Sudah Voting -->
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 bg-white h-100">
            <div class="card-body p-3 text-center">
                <h2 class="text-success fw-bold mb-0"><?= $total_sudah ?></h2>
                <p class="text-muted small mb-0 mt-1">Sudah Voting</p>
            </div>
        </div>
    </div>
    
    <!-- Kartu Belum Voting -->
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 bg-white h-100">
            <div class="card-body p-3 text-center">
                <h2 class="text-danger fw-bold mb-0"><?= $total_belum ?></h2>
                <p class="text-muted small mb-0 mt-1">Belum Voting</p>
            </div>
        </div>
    </div>
    
    <!-- Kartu Total Kandidat -->
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-4 bg-white h-100">
            <div class="card-body p-3 text-center">
                <h2 class="text-warning fw-bold mb-0"><?= $total_kandidat ?></h2>
                <p class="text-muted small mb-0 mt-1">Total Kandidat</p>
            </div>
        </div>
    </div>

</div>

<!-- Pesan Sambutan -->
<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 text-center py-5">
            <div class="card-body">
                <h3 class="fw-bold text-dark mb-3">SMPN 219 JAKARTA PEMILIHAN KETUA OSIS</h3>
            </div>
        </div>
    </div>
</div>

<?php 
// Memanggil template bawah
include 'komponen/footer.php'; 
?>
