<?php
session_start();
if (!isset($_SESSION['admin_aktif'])) {
    header("Location: login.php");
    exit;
}
require '../koneksi.php';

// 1. Logika Tambah Acara Pemilihan
if (isset($_POST['tambah_acara'])) {
    $judul = $_POST['judul_pemilihan'];
    $mulai = $_POST['tanggal_mulai'];
    $selesai = $_POST['tanggal_selesai'];
    
    $stmt = $koneksi->prepare("INSERT INTO pemilihan (judul_pemilihan, tanggal_mulai, tanggal_selesai) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $judul, $mulai, $selesai);
    $stmt->execute();
    
    header("Location: pemilihan.php");
    exit;
}

// 2. Logika Hapus Acara (CASCADE: Menghapus acara = menghapus semua kandidat, voter, & suara)
if (isset($_GET['hapus'])) {
    $id_hapus = $_GET['hapus'];
    $stmt = $koneksi->prepare("DELETE FROM pemilihan WHERE id = ?");
    $stmt->bind_param("i", $id_hapus);
    $stmt->execute();
    
    header("Location: pemilihan.php");
    exit;
}

include 'komponen/header.php';
include 'komponen/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-dark mb-0">Kelola Acara Pemilihan</h3>
    <!-- Tombol untuk memunculkan Popup (Modal) Tambah -->
    <button class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modalTambah">
        + Buat Acara Baru
    </button>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light text-secondary">
                    <tr>
                        <th>No</th>
                        <th>Judul Pemilihan</th>
                        <th>Waktu Mulai</th>
                        <th>Waktu Selesai</th>
                        <th>Status Saat Ini</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;
                    $query = $koneksi->query("SELECT * FROM pemilihan ORDER BY id DESC");
                    
                    if ($query->num_rows == 0) {
                        echo '<tr><td colspan="6" class="text-center text-muted py-4">Belum ada acara pemilihan yang dibuat.</td></tr>';
                    }

                    while ($baris = $query->fetch_assoc()) {
                        // Menentukan status berdasarkan waktu saat ini (Real-time)
                        $waktu_sekarang = date('Y-m-d H:i:s');
                        $status_badge = "";
                        
                        if ($waktu_sekarang < $baris['tanggal_mulai']) {
                            $status_badge = '<span class="badge bg-warning text-dark">Menunggu Jadwal</span>';
                        } elseif ($waktu_sekarang >= $baris['tanggal_mulai'] && $waktu_sekarang <= $baris['tanggal_selesai']) {
                            $status_badge = '<span class="badge bg-success">Sedang Berlangsung</span>';
                        } else {
                            $status_badge = '<span class="badge bg-danger">Sudah Selesai</span>';
                        }
                    ?>
                    <tr>
                        <td class="fw-bold text-muted"><?= $no++ ?></td>
                        <td class="fw-bold"><?= htmlspecialchars($baris['judul_pemilihan']) ?></td>
                        <td><?= date('d M Y, H:i', strtotime($baris['tanggal_mulai'])) ?></td>
                        <td><?= date('d M Y, H:i', strtotime($baris['tanggal_selesai'])) ?></td>
                        <td><?= $status_badge ?></td>
                        <td class="text-center">
                            <!-- Tombol Hapus dengan konfirmasi -->
                            <a href="?hapus=<?= $baris['id'] ?>" 
                               class="btn btn-sm btn-outline-danger" 
                               onclick="return confirm('PERINGATAN KERAS!\n\nMenghapus acara ini akan ikut MENGHAPUS SELURUH data Kandidat, Voter, dan Hasil Suara yang terkait dengan acara ini secara permanen.\n\nApakah Anda yakin ingin menghapus acara ini?')">
                               Hapus Acara
                            </a>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Popup Modal Tambah Acara -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0 pt-4 px-4">
                <h5 class="modal-title fw-bold">Buat Acara Pemilihan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body px-4">
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Judul Acara / Pemilihan</label>
                        <input type="text" name="judul_pemilihan" class="form-control" placeholder="Contoh: Pemilu Ketua OSIS 2026/2027" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Tanggal & Waktu Mulai</label>
                        <input type="datetime-local" name="tanggal_mulai" class="form-control" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label text-muted small fw-bold">Tanggal & Waktu Selesai</label>
                        <input type="datetime-local" name="tanggal_selesai" class="form-control" required>
                        <div class="form-text small text-danger mt-2">
                            *Catatan: Sistem akan otomatis menutup akses voting ketika waktu selesai telah lewat.
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pb-4 px-4">
                    <button type="button" class="btn btn-light text-muted fw-bold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_acara" class="btn btn-primary fw-bold px-4">Simpan Acara</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'komponen/footer.php'; ?>
