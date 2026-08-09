<?php
require 'koneksi.php';

// Menentukan acara pemilihan yang sedang aktif/ditampilkan
$id_acara_aktif = 0;
if (isset($_GET['acara'])) {
    $id_acara_aktif = $_GET['acara'];
} else {
    $cek_acara = $koneksi->query("SELECT id FROM pemilihan ORDER BY id DESC LIMIT 1");
    if ($cek_acara->num_rows > 0) {
        $id_acara_aktif = $cek_acara->fetch_assoc()['id'];
    }
}

// Ambil detail judul acara
$judul_acara = "Pemilihan OSIS";
if ($id_acara_aktif > 0) {
    $q_j = $koneksi->query("SELECT judul_pemilihan FROM pemilihan WHERE id = '$id_acara_aktif'");
    if ($q_j->num_rows > 0) {
        $judul_acara = $q_j->fetch_assoc()['judul_pemilihan'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Halaman akan otomatis melakukan refresh setiap 10 detik agar data selalu sinkron secara real-time -->
    <meta http-equiv="refresh" content="10">
    <title>Hasil Live Count - <?= htmlspecialchars($judul_acara) ?></title>
    <!-- Memanggil Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Menggunakan tema warna terang yang senada dengan panel admin dan siswa */
        body {
            background-color: #f0f2f5; 
            color: #333333;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .card-stat {
            background-color: #ffffff;
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .chart-container {
            background-color: #ffffff;
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            padding: 25px;
        }
    </style>
</head>
<body>
  
  <!-- Top Navbar Senada dengan Sidebar -->
<div class="topbar d-flex justify-content-between align-items-center" style="background-color: #1b2a47; box-shadow: 0 2px 4px rgba(0,0,0,.1);">
    
    <!-- Bagian Logo dengan Background Putih & Melengkung -->
    <div class="logo-brand d-flex align-items-center gap-3">
        <div style="background-color: #ffffff; padding: 6px 10px; border-radius: 8px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <img src="https://lh3.googleusercontent.com/d/1jknxYOn9Wbnf7EvZD94479VifDvKPqk1" alt="Logo OSIS" style="height: 32px; width: auto; display: block;">
        </div>
        <h5 class="mb-0 fw-bold text-white">OSIS SMPN 219 JAKARTA</h5>
    </div>

    <!-- Bagian Admin Aktif -->
    <div class="d-flex align-items-center">
    </div>
</div>


    <div class="container py-5">
        
        <!-- Header Publik -->
        <div class="text-center mb-5">
            <h6 class="text-uppercase text-primary fw-bold tracking-wider mb-2">Real-Time Live Count Pemilu</h6>
            <h1 class="fw-bold display-5 mb-2 text-dark"><?= htmlspecialchars($judul_acara) ?></h1>
            <p class="text-muted">SMPN 219 Jakarta &bull; Layar Pantau Perolehan Suara</p>
        </div>

        <?php if ($id_acara_aktif > 0): ?>
            
            <?php
            // Mengambil statistik total suara masuk
            $q_sudah = $koneksi->query("SELECT COUNT(*) as total FROM pemilih WHERE id_pemilihan = '$id_acara_aktif' AND status_voting = 'sudah'")->fetch_assoc()['total'];

            // Mengambil data perolehan suara kandidat
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
                $nama_kandidat[] = "No. " . $row['no_urut'] . " - " . $row['nama_kandidat'];
                $jumlah_suara[] = $row['total_suara'];
            }
            ?>

            <!-- Kartu Statistik (Hanya Total Suara Masuk) -->
            <div class="row g-4 mb-5 justify-content-center text-center">
                <div class="col-md-6">
                    <div class="card-stat p-4 border-start border-success border-4">
                        <span class="d-block text-success small fw-bold text-uppercase mb-1">Total Suara Masuk</span>
                        <h2 class="fw-bold text-success mb-0"><?= number_format($q_sudah) ?></h2>
                    </div>
                </div>
            </div>

            <!-- Grafik Batang Interaktif -->
            <div class="chart-container mb-4">
                <div style="position: relative; height: 400px; width: 100%;">
                    <canvas id="grafikLiveCount"></canvas>
                </div>
            </div>

            <div class="text-center text-muted small">
                Layar ini akan memperbarui data secara otomatis setiap 10 detik.
            </div>

            <!-- Script Chart.js -->
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
                const ctx = document.getElementById('grafikLiveCount').getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: <?= json_encode($nama_kandidat) ?>,
                        datasets: [{
                            label: 'Perolehan Suara',
                            data: <?= json_encode($jumlah_suara) ?>,
                            backgroundColor: '#0d6efd',
                            borderColor: '#0b5ed7',
                            borderWidth: 1,
                            borderRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1,
                                    color: '#6c757d'
                                },
                                grid: {
                                    color: '#e9ecef'
                                }
                            },
                            x: {
                                ticks: {
                                    color: '#333333',
                                    font: {
                                        weight: 'bold',
                                        size: 14
                                    }
                                },
                                grid: {
                                    display: false
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            </script>

        <?php else: ?>
            <div class="alert alert-warning text-center p-4">
                Belum ada acara pemilihan yang tersedia saat ini.
            </div>
        <?php endif; ?>

    </div>

</body>
</html>
