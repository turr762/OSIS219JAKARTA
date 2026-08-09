<?php
session_start();
if (!isset($_SESSION['admin_aktif'])) {
    header("Location: login.php");
    exit;
}
require '../koneksi.php';

// Menentukan acara pemilihan yang sedang dilihat (Default: acara terbaru)
$id_acara_aktif = 0;
if (isset($_GET['acara'])) {
    $id_acara_aktif = $_GET['acara'];
} else {
    $cek_acara = $koneksi->query("SELECT id FROM pemilihan ORDER BY id DESC LIMIT 1");
    if ($cek_acara->num_rows > 0) {
        $id_acara_aktif = $cek_acara->fetch_assoc()['id'];
    }
}

include 'komponen/header.php';
include 'komponen/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-dark mb-0">Hasil Perolehan Suara</h3>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <!-- Form Filter Acara -->
        <form action="" method="GET" class="row align-items-center mb-4">
            <div class="col-md-8 mb-2 mb-md-0">
                <label class="form-label fw-bold text-muted small">Pilih Acara Pemilihan:</label>
                <select name="acara" class="form-select border-primary" onchange="this.form.submit()">
                    <?php
                    $q_acara = $koneksi->query("SELECT * FROM pemilihan ORDER BY id DESC");
                    if ($q_acara->num_rows == 0) {
                        echo "<option value=''>Belum ada acara dibuat</option>";
                    }
                    while ($acara = $q_acara->fetch_assoc()) {
                        $terpilih = ($acara['id'] == $id_acara_aktif) ? 'selected' : '';
                        echo "<option value='{$acara['id']}' $terpilih>{$acara['judul_pemilihan']}</option>";
                    }
                    ?>
                </select>
            </div>
        </form>

        <?php if ($id_acara_aktif > 0): ?>
            
            <?php
            // Mengambil statistik partisipasi khusus untuk acara yang dipilih
            $q_total_voter = $koneksi->query("SELECT COUNT(*) as total FROM pemilih WHERE id_pemilihan = '$id_acara_aktif'")->fetch_assoc()['total'];
            $q_sudah = $koneksi->query("SELECT COUNT(*) as total FROM pemilih WHERE id_pemilihan = '$id_acara_aktif' AND status_voting = 'sudah'")->fetch_assoc()['total'];
            $q_belum = $koneksi->query("SELECT COUNT(*) as total FROM pemilih WHERE id_pemilihan = '$id_acara_aktif' AND status_voting = 'belum'")->fetch_assoc()['total'];
            
            // Mengambil data kandidat dan menghitung jumlah suaranya
            $nama_kandidat = [];
            $jumlah_suara = [];
            
            $q_kandidat = $koneksi->query("
                SELECT k.nama_kandidat, k.no_urut, COUNT(s.id) as total_suara 
                FROM kandidat k 
                LEFT JOIN suara s ON k.id = s.id_kandidat 
                WHERE k.id_pemilihan = '$id_acara_aktif' 
                GROUP BY k.id 
                ORDER BY k.no_urut ASC
            ");

            while ($row = $q_kandidat->fetch_assoc()) {
                $nama_kandidat[] = "No." . $row['no_urut'] . " - " . $row['nama_kandidat'];
                $jumlah_suara[] = $row['total_suara'];
            }
            ?>

            <!-- Baris Info Partisipasi -->
            <div class="row g-3 mb-4 text-center">
                <div class="col-md-4">
                    <div class="p-3 bg-light rounded border">
                        <span class="d-block text-muted small fw-bold">Total pemilih</span>
                        <h4 class="fw-bold text-dark mb-0"><?= $q_total_voter ?></h4>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 bg-light rounded border border-success">
                        <span class="d-block text-success small fw-bold">Suara Masuk</span>
                        <h4 class="fw-bold text-success mb-0"><?= $q_sudah ?></h4>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-3 bg-light rounded border border-danger">
                        <span class="d-block text-danger small fw-bold">Belum Memilih</span>
                        <h4 class="fw-bold text-danger mb-0"><?= $q_belum ?></h4>
                    </div>
                </div>
            </div>

            <!-- Area Diagram Batang -->
            <div class="border rounded p-3 mb-4" style="background-color: #f8fafc;">
                <canvas id="grafikSuara" height="100"></canvas>
            </div>

            <!-- Script untuk Menggambar Grafik dengan Chart.js -->
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
                const ctx = document.getElementById('grafikSuara');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: <?= json_encode($nama_kandidat) ?>,
                        datasets: [{
                            label: 'Jumlah Suara',
                            data: <?= json_encode($jumlah_suara) ?>,
                            backgroundColor: '#3b82f6', // Warna biru elegan
                            borderColor: '#2563eb',
                            borderWidth: 1,
                            borderRadius: 6 // Ujung diagram sedikit membulat
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1 // Angkanya bilangan bulat
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false // Sembunyikan legenda agar lebih bersih
                            }
                        }
                    }
                });
            </script>

        <?php else: ?>
            <div class="alert alert-warning text-center">
                Belum ada acara pemilihan yang aktif atau dipilih.
            </div>
        <?php endif; ?>

    </div>
</div>

<?php include 'komponen/footer.php'; ?>
