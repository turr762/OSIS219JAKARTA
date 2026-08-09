<?php
session_start();
if (!isset($_SESSION['admin_aktif'])) {
    header("Location: login.php");
    exit;
}
require '../koneksi.php';

// --- LOGIKA TAMBAH VOTER (SATUAN) ---
if (isset($_POST['tambah_voter'])) {
    $id_pemilihan = $_POST['id_pemilihan'];
    $nis          = $_POST['nis'];
    $nama         = $_POST['nama_siswa'];
    $role         = $_POST['role'];
    $kelas        = ($role == 'Guru' || $role == 'Tata Usaha') ? '-' : $_POST['kelas'];
    $absen        = ($role == 'Guru' || $role == 'Tata Usaha') ? '-' : $_POST['nomor_absen'];
    $password     = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    $stmt = $koneksi->prepare("INSERT INTO pemilih (id_pemilihan, nis, password, nama_siswa, kelas, nomor_absen, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssss", $id_pemilihan, $nis, $password, $nama, $kelas, $absen, $role);
    $stmt->execute();
    
    header("Location: voter.php?pesan=sukses_tambah");
    exit;
}

// --- LOGIKA EDIT VOTER & RESET SUARA ---
if (isset($_POST['edit_voter'])) {
    $id           = $_POST['id_voter'];
    $id_pemilihan = $_POST['id_pemilihan'];
    $nama         = $_POST['nama_siswa'];
    $role         = $_POST['role'];
    $kelas        = ($role == 'Guru' || $role == 'Tata Usaha') ? '-' : $_POST['kelas'];
    $absen        = ($role == 'Guru' || $role == 'Tata Usaha') ? '-' : $_POST['nomor_absen'];
    $status_aktif = $_POST['status_aktif'];

    // 1. Eksekusi Update Data Profil Pemilih ke Database Terlebih Dahulu
    if (!empty($_POST['password_baru'])) {
        $password = password_hash($_POST['password_baru'], PASSWORD_DEFAULT);
        $stmt = $koneksi->prepare("UPDATE pemilih SET id_pemilihan=?, nama_siswa=?, kelas=?, nomor_absen=?, role=?, status_aktif=?, password=? WHERE id=?");
        $stmt->bind_param("issssssi", $id_pemilihan, $nama, $kelas, $absen, $role, $status_aktif, $password, $id);
    } else {
        $stmt = $koneksi->prepare("UPDATE pemilih SET id_pemilihan=?, nama_siswa=?, kelas=?, nomor_absen=?, role=?, status_aktif=? WHERE id=?");
        $stmt->bind_param("isssssi", $id_pemilihan, $nama, $kelas, $absen, $role, $status_aktif, $id);
    }
    $stmt->execute();
    
    // 2. Logika Hapus Suara (HANYA dieksekusi jika status diubah kembali menjadi AKTIF)
    if ($status_aktif == 'aktif') {
        // Hapus suara dari database
        $koneksi->query("DELETE FROM suara WHERE id_pemilih = '$id'");
        // Reset status votingnya menjadi belum
        $koneksi->query("UPDATE pemilih SET status_voting = 'belum' WHERE id = '$id'");
    }

    header("Location: voter.php?pesan=sukses_edit");
    exit;
}

// --- LOGIKA HAPUS VOTER ---
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $koneksi->query("DELETE FROM pemilih WHERE id = '$id'");
    header("Location: voter.php?pesan=sukses_hapus");
    exit;
}

// --- LOGIKA IMPORT EXCEL (CSV) ---
if (isset($_POST['import_csv'])) {
    $id_pemilihan = $_POST['id_pemilihan'];
    $file_mimes = array('text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel', 'text/plain');
    
    if (isset($_FILES['file_csv']['name']) && in_array($_FILES['file_csv']['type'], $file_mimes)) {
        $file = fopen($_FILES['file_csv']['tmp_name'], 'r');
        fgetcsv($file); // Melewati baris pertama (Header/Judul Kolom di Excel)
        
        while (($row = fgetcsv($file, 1000, ",")) !== FALSE) {
            $nis      = $row[0];
            $nama     = $row[1];
            $kelas    = $row[2];
            $absen    = $row[3];
            $role     = $row[4];
            $password = password_hash($row[5], PASSWORD_DEFAULT);
            
            // Perbaikan nilai jika Guru/TU
            if ($role == 'Guru' || $role == 'Tata Usaha') {
                $kelas = '-';
                $absen = '-';
            }
            
            // Cek apakah NIS sudah ada agar tidak duplikat
            $cek = $koneksi->query("SELECT id FROM pemilih WHERE nis = '$nis'");
            if ($cek->num_rows == 0) {
                $stmt = $koneksi->prepare("INSERT INTO pemilih (id_pemilihan, nis, password, nama_siswa, kelas, nomor_absen, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issssss", $id_pemilihan, $nis, $password, $nama, $kelas, $absen, $role);
                $stmt->execute();
            }
        }
        fclose($file);
        header("Location: voter.php?pesan=sukses_import");
        exit;
    }
}

include 'komponen/header.php';
include 'komponen/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-dark mb-0">Kelola Akun Voter</h3>
    <div>
        <button class="btn btn-success fw-bold shadow-sm me-2" data-bs-toggle="modal" data-bs-target="#modalImport">
            Import Excel (CSV)
        </button>
        <button class="btn btn-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
            Tambah Voter
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <div class="table-responsive" style="max-height: 60vh; overflow-y: auto;">
            <table class="table table-hover align-middle">
                <thead class="table-light text-secondary sticky-top">
                    <tr>
                        <th>NIS</th>
                        <th>Nama Lengkap</th>
                        <th>Kelas</th>
                        <th>Role</th>
                        <th>Status Akun</th>
                        <th>Status Voting</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = $koneksi->query("SELECT * FROM pemilih ORDER BY id DESC LIMIT 500");
                    if ($query->num_rows == 0) {
                        echo '<tr><td colspan="7" class="text-center text-muted py-4">Belum ada data voter.</td></tr>';
                    }
                    while ($baris = $query->fetch_assoc()) {
                        $badge_aktif = ($baris['status_aktif'] == 'aktif') ? '<span class="badge bg-primary">Aktif</span>' : '<span class="badge bg-secondary">Nonaktif</span>';
                        $badge_voting = ($baris['status_voting'] == 'sudah') ? '<span class="badge bg-success">Sudah</span>' : '<span class="badge bg-danger">Belum</span>';
                    ?>
                    <tr>
                        <td class="fw-bold"><?= htmlspecialchars($baris['nis']) ?></td>
                        <td><?= htmlspecialchars($baris['nama_siswa']) ?></td>
                        <td><?= htmlspecialchars($baris['kelas']) ?></td>
                        <td><?= $baris['role'] ?></td>
                        <td><?= $badge_aktif ?></td>
                        <td><?= $badge_voting ?></td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $baris['id'] ?>">Edit</button>
                            <a href="?hapus=<?= $baris['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus voter ini secara permanen?')">Hapus</a>
                        </td>
                    </tr>

                    <!-- Modal Edit -->
                    <div class="modal fade" id="modalEdit<?= $baris['id'] ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content rounded-4 border-0 shadow">
                                <div class="modal-header border-bottom-0 pt-4 px-4">
                                    <h5 class="modal-title fw-bold">Edit Akun Voter</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form action="" method="POST">
                                    <input type="hidden" name="id_voter" value="<?= $baris['id'] ?>">
                                    <div class="modal-body px-4">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label text-muted small fw-bold">Acara Pemilihan</label>
                                                <select name="id_pemilihan" class="form-select" required>
                                                    <?php
                                                    $q_acara = $koneksi->query("SELECT id, judul_pemilihan FROM pemilihan");
                                                    while ($acara = $q_acara->fetch_assoc()) {
                                                        $terpilih = ($acara['id'] == $baris['id_pemilihan']) ? 'selected' : '';
                                                        echo "<option value='{$acara['id']}' $terpilih>{$acara['judul_pemilihan']}</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label text-muted small fw-bold">Status Akun</label>
                                                <select name="status_aktif" class="form-select">
                                                    <option value="aktif" <?= ($baris['status_aktif'] == 'aktif') ? 'selected' : '' ?>>Aktif (Reset Suara & Bisa Memilih)</option>
                                                    <option value="nonaktif" <?= ($baris['status_aktif'] == 'nonaktif') ? 'selected' : '' ?>>Nonaktifkan Akun</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-muted small fw-bold">Nama Lengkap</label>
                                            <input type="text" name="nama_siswa" class="form-control" value="<?= htmlspecialchars($baris['nama_siswa']) ?>" required>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label text-muted small fw-bold">Role</label>
                                                <select name="role" class="form-select role-selector" onchange="aturField(this)" required>
                                                    <option value="Siswa" <?= ($baris['role'] == 'Siswa') ? 'selected' : '' ?>>Siswa</option>
                                                    <option value="Guru" <?= ($baris['role'] == 'Guru') ? 'selected' : '' ?>>Guru</option>
                                                    <option value="Tata Usaha" <?= ($baris['role'] == 'Tata Usaha') ? 'selected' : '' ?>>Tata Usaha</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label text-muted small fw-bold">Kelas</label>
                                                <input type="text" name="kelas" class="form-control input-kelas" value="<?= htmlspecialchars($baris['kelas']) ?>">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label text-muted small fw-bold">No. Absen</label>
                                                <input type="number" name="nomor_absen" class="form-control input-absen" value="<?= htmlspecialchars($baris['nomor_absen']) ?>">
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-muted small fw-bold">Password Baru (Opsional)</label>
                                            <input type="text" name="password_baru" class="form-control" placeholder="Kosongkan jika tidak diubah">
                                        </div>
                                    </div>
                                    <div class="modal-footer border-top-0 pb-4 px-4">
                                        <button type="submit" name="edit_voter" class="btn btn-primary fw-bold px-4">Simpan Perubahan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah Voter -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0 pt-4 px-4">
                <h5 class="modal-title fw-bold">Tambah Akun Voter</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body px-4">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted small fw-bold">Acara Pemilihan</label>
                            <select name="id_pemilihan" class="form-select" required>
                                <option value="">-- Pilih Acara --</option>
                                <?php
                                $q_acara = $koneksi->query("SELECT id, judul_pemilihan FROM pemilihan");
                                while ($acara = $q_acara->fetch_assoc()) {
                                    echo "<option value='{$acara['id']}'>{$acara['judul_pemilihan']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted small fw-bold">NIS / NIP</label>
                            <input type="text" name="nis" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Nama Lengkap</label>
                        <input type="text" name="nama_siswa" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-muted small fw-bold">Role</label>
                            <select name="role" class="form-select role-selector" onchange="aturField(this)" required>
                                <option value="Siswa">Siswa</option>
                                <option value="Guru">Guru</option>
                                <option value="Tata Usaha">Tata Usaha</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-muted small fw-bold">Kelas</label>
                            <input type="text" name="kelas" class="form-control input-kelas" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-muted small fw-bold">No. Absen</label>
                            <input type="number" name="nomor_absen" class="form-control input-absen" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Password Login</label>
                        <input type="text" name="password" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pb-4 px-4">
                    <button type="submit" name="tambah_voter" class="btn btn-primary fw-bold px-4">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Import CSV -->
<div class="modal fade" id="modalImport" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0 pt-4 px-4">
                <h5 class="modal-title fw-bold">Import Data (Format CSV)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="modal-body px-4">
                    <div class="alert alert-info small">
    Pastikan Anda menyimpan file Excel dalam format <b>CSV (Comma delimited)</b>. Urutan kolom dari kiri ke kanan harus persis seperti ini:<br><br>
    <b>1. NIS | 2. Nama Lengkap | 3. Kelas | 4. No Absen | 5. Role | 6. Password</b><br><br>
    <span class="text-danger fw-bold">*Catatan Khusus:</span><br>
    - Penulisan Role harus persis: <b>Siswa</b>, <b>Guru</b>, atau <b>Tata Usaha</b>.<br>
    - Khusus untuk Guru dan Tata Usaha, kolom Kelas dan No Absen di Excel <b>boleh dikosongkan atau diisi tanda strip (-)</b>, sistem akan menyesuaikannya secara otomatis.
</div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Pilih Acara Pemilihan</label>
                        <select name="id_pemilihan" class="form-select" required>
                            <?php
                            $q_acara = $koneksi->query("SELECT id, judul_pemilihan FROM pemilihan");
                            while ($acara = $q_acara->fetch_assoc()) {
                                echo "<option value='{$acara['id']}'>{$acara['judul_pemilihan']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Upload File CSV</label>
                        <input type="file" name="file_csv" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pb-4 px-4">
                    <button type="submit" name="import_csv" class="btn btn-success fw-bold px-4">Proses Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Script Khusus untuk mengatur input Kelas & Absen berdasarkan Role -->
<script>
function aturField(elemen) {
    let form = elemen.closest('form');
    let role = elemen.value;
    let inputKelas = form.querySelector('.input-kelas');
    let inputAbsen = form.querySelector('.input-absen');

    if (role === 'Guru' || role === 'Tata Usaha') {
        inputKelas.value = '-';
        inputKelas.setAttribute('readonly', true);
        inputKelas.style.backgroundColor = '#e9ecef';
        inputKelas.removeAttribute('required');

        inputAbsen.value = '-';
        inputAbsen.setAttribute('readonly', true);
        inputAbsen.style.backgroundColor = '#e9ecef';
        inputAbsen.removeAttribute('required');
    } else {
        inputKelas.value = '';
        inputKelas.removeAttribute('readonly');
        inputKelas.style.backgroundColor = '#ffffff';
        inputKelas.setAttribute('required', true);

        inputAbsen.value = '';
        inputAbsen.removeAttribute('readonly');
        inputAbsen.style.backgroundColor = '#ffffff';
        inputAbsen.setAttribute('required', true);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    let selectors = document.querySelectorAll('.role-selector');
    selectors.forEach(function(selector) {
        aturField(selector);
    });
});
</script>

<?php include 'komponen/footer.php'; ?>
