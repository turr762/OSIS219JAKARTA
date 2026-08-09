<?php
session_start();
if (!isset($_SESSION['admin_aktif'])) {
    header("Location: login.php");
    exit;
}
require '../koneksi.php';

// --- LOGIKA EXCEL / CSV DOWNLOAD ---
if (isset($_GET['export']) && isset($_GET['acara'])) {
    $id_acara = $_GET['acara'];
    
    $q_judul = $koneksi->query("SELECT judul_pemilihan FROM pemilihan WHERE id = '$id_acara'");
    if ($q_judul->num_rows > 0) {
        $judul = $q_judul->fetch_assoc()['judul_pemilihan'];
        $nama_file = "Daftar_Hadir_Pemilu_" . preg_replace('/[^a-zA-Z0-9]/', '_', $judul) . "_" . date('Ymd') . ".csv";
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nama_file . '"');
        
        $output = fopen('php://output', 'w');
        
        // Judul Kolom CSV (Tanpa Kandidat Pilihan)
        fputcsv($output, array('No', 'Waktu Pencoblosan', 'NIS / NIP', 'Nama Lengkap', 'Kelas', 'Status / Role'));
        
        // Query Laporan Rahasia
        $query_export = $koneksi->query("
            SELECT su.waktu_pilih, p.nis, p.nama_siswa, p.kelas, p.role
            FROM suara su 
            JOIN pemilih p ON su.id_pemilih = p.id 
            WHERE su.id_pemilihan = '$id_acara' 
            ORDER BY su.waktu_pilih ASC
        ");
        
        $no = 1;
        while ($row = $query_export->fetch_assoc()) {
            fputcsv($output, array(
                $no++, 
                $row['waktu_pilih'], 
                $row['nis'], 
                $row['nama_siswa'], 
                $row['kelas'], 
                $row['role']
            ));
        }
        
        fclose($output);
        exit;
    }
}

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
    <h3 class="fw-bold text-dark mb-0">Laporan Kehadiran Pemilih</h3>
    
    <?php if ($id_acara_aktif > 0): ?>
        <a href="laporan.php?export=true&acara=<?= $id_acara_aktif ?>" class="btn btn-success fw-bold shadow-sm">
            Unduh Laporan (CSV)
        </a>
    <?php endif; ?>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <form action="" method="GET" class="row align-items-center mb-4">
            <div class="col-md-8">
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
            <!-- Menambahkan overflow-x agar tabel bisa digeser jika layar kecil -->
            <div class="table-responsive">
                <table class="table table-hover align-middle table-sm border">
                    <thead class="table-light text-secondary">
                        <tr>
                            <th>No</th>
                            <th style="white-space: nowrap;">Waktu Pencoblosan</th>
                            <th style="white-space: nowrap;">NIS / NIP</th>
                            <!-- Memaksa kolom nama memanjang dan tidak turun ke bawah -->
                            <th style="white-space: nowrap; min-width: 300px;">Nama Pemilih</th>
                            <th>Kelas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        $query_log = $koneksi->query("
                            SELECT su.waktu_pilih, p.nis, p.nama_siswa, p.kelas, p.role
                            FROM suara su 
                            JOIN pemilih p ON su.id_pemilih = p.id 
                            WHERE su.id_pemilihan = '$id_acara_aktif' 
                            ORDER BY su.waktu_pilih DESC
                        ");

                        if ($query_log->num_rows == 0) {
                            echo '<tr><td colspan="5" class="text-center text-muted py-4">Belum ada suara yang masuk pada acara ini.</td></tr>';
                        }

                        while ($baris = $query_log->fetch_assoc()) {
                        ?>
                        <tr>
                            <td class="text-muted"><?= $no++ ?></td>
                            <td class="small text-muted" style="white-space: nowrap;"><?= date('d-m-Y H:i:s', strtotime($baris['waktu_pilih'])) ?></td>
                            <td class="fw-bold" style="white-space: nowrap;"><?= htmlspecialchars($baris['nis']) ?></td>
                            <td style="white-space: nowrap;">
                                <?= htmlspecialchars($baris['nama_siswa']) ?>
                                <!-- Badge role sekarang ditaruh di samping nama, bukan di bawahnya -->
                                <span class="badge bg-secondary ms-2 fw-normal" style="font-size: 0.75em;"><?= $baris['role'] ?></span>
                            </td>
                            <td style="white-space: nowrap;"><?= htmlspecialchars($baris['kelas']) ?></td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-warning text-center">
                Silakan buat acara pemilihan terlebih dahulu.
            </div>
        <?php endif; ?>

    </div>
</div>

<?php include 'komponen/footer.php'; ?>
