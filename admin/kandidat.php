<?php
session_start();
if (!isset($_SESSION['admin_aktif'])) {
    header("Location: login.php");
    exit;
}
require '../koneksi.php';

// --- LOGIKA TAMBAH KANDIDAT ---
if (isset($_POST['tambah_kandidat'])) {
    $id_pemilihan = $_POST['id_pemilihan'];
    $no_urut      = $_POST['no_urut'];
    $nama         = $_POST['nama_kandidat'];
    $visi         = $_POST['visi'];
    $misi         = $_POST['misi'];
    
    // Proses Upload Foto
    $foto      = $_FILES['foto']['name'];
    $tmp       = $_FILES['foto']['tmp_name'];
    $nama_foto = time() . "_" . $foto; // Tambah timestamp agar nama file unik
    $path      = "../assets/img/" . $nama_foto;
    
    if (move_uploaded_file($tmp, $path)) {
        $stmt = $koneksi->prepare("INSERT INTO kandidat (id_pemilihan, no_urut, nama_kandidat, visi, misi, foto) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissss", $id_pemilihan, $no_urut, $nama, $visi, $misi, $nama_foto);
        $stmt->execute();
    }
    header("Location: kandidat.php");
    exit;
}

// --- LOGIKA HAPUS KANDIDAT ---
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    
    // Cari nama file foto di database untuk dihapus dari folder
    $query_foto = $koneksi->query("SELECT foto FROM kandidat WHERE id = '$id'");
    $data_foto = $query_foto->fetch_assoc();
    if (file_exists("../assets/img/" . $data_foto['foto'])) {
        unlink("../assets/img/" . $data_foto['foto']);
    }
    
    $koneksi->query("DELETE FROM kandidat WHERE id = '$id'");
    header("Location: kandidat.php");
    exit;
}

// --- LOGIKA EDIT KANDIDAT ---
if (isset($_POST['edit_kandidat'])) {
    $id           = $_POST['id_kandidat'];
    $id_pemilihan = $_POST['id_pemilihan'];
    $no_urut      = $_POST['no_urut'];
    $nama         = $_POST['nama_kandidat'];
    $visi         = $_POST['visi'];
    $misi         = $_POST['misi'];
    
    // Jika admin mengunggah foto baru
    if ($_FILES['foto']['name'] != "") {
        $foto      = $_FILES['foto']['name'];
        $tmp       = $_FILES['foto']['tmp_name'];
        $nama_foto = time() . "_" . $foto;
        
        if (move_uploaded_file($tmp, "../assets/img/" . $nama_foto)) {
            // Hapus foto lama
            $q = $koneksi->query("SELECT foto FROM kandidat WHERE id = '$id'");
            $dt = $q->fetch_assoc();
            if (file_exists("../assets/img/" . $dt['foto'])) {
                unlink("../assets/img/" . $dt['foto']);
            }
            
            $stmt = $koneksi->prepare("UPDATE kandidat SET id_pemilihan=?, no_urut=?, nama_kandidat=?, visi=?, misi=?, foto=? WHERE id=?");
            $stmt->bind_param("iissssi", $id_pemilihan, $no_urut, $nama, $visi, $misi, $nama_foto, $id);
            $stmt->execute();
        }
    } else {
        // Jika foto tidak diganti
        $stmt = $koneksi->prepare("UPDATE kandidat SET id_pemilihan=?, no_urut=?, nama_kandidat=?, visi=?, misi=? WHERE id=?");
        $stmt->bind_param("iisssi", $id_pemilihan, $no_urut, $nama, $visi, $misi, $id);
        $stmt->execute();
    }
    header("Location: kandidat.php");
    exit;
}

include 'komponen/header.php';
include 'komponen/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-dark mb-0">Kelola Kandidat</h3>
    <button class="btn btn-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambah">
        Tambah Kandidat
    </button>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light text-secondary">
                    <tr>
                        <th class="text-center">No Urut</th>
                        <th>Foto</th>
                        <th>Nama Kandidat</th>
                        <th>Acara Pemilihan</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Mengambil data kandidat beserta judul pemilihannya
                    $query = $koneksi->query("SELECT k.*, p.judul_pemilihan FROM kandidat k JOIN pemilihan p ON k.id_pemilihan = p.id ORDER BY p.id DESC, k.no_urut ASC");
                    
                    if ($query->num_rows == 0) {
                        echo '<tr><td colspan="5" class="text-center text-muted py-4">Belum ada data kandidat.</td></tr>';
                    }

                    while ($baris = $query->fetch_assoc()) {
                    ?>
                    <tr>
                        <td class="text-center fw-bold fs-5"><?= $baris['no_urut'] ?></td>
                        <td>
                            <img src="../assets/img/<?= $baris['foto'] ?>" alt="Foto" class="rounded border" style="width: 60px; height: 80px; object-fit: cover;">
                        </td>
                        <td class="fw-bold"><?= htmlspecialchars($baris['nama_kandidat']) ?></td>
                        <td class="text-muted small"><?= htmlspecialchars($baris['judul_pemilihan']) ?></td>
                        <td class="text-center">
                            <!-- Tombol Edit memanggil modal unik berdasarkan ID -->
                            <button class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $baris['id'] ?>">Edit</button>
                            <a href="?hapus=<?= $baris['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus kandidat ini beserta fotonya secara permanen?')">Hapus</a>
                        </td>
                    </tr>

                    <!-- Popup Modal Edit (Dibuat berulang untuk setiap kandidat) -->
                    <div class="modal fade" id="modalEdit<?= $baris['id'] ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content rounded-4 border-0 shadow">
                                <div class="modal-header border-bottom-0 pt-4 px-4">
                                    <h5 class="modal-title fw-bold">Edit Data Kandidat</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form action="" method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="id_kandidat" value="<?= $baris['id'] ?>">
                                    <div class="modal-body px-4">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label text-muted small fw-bold">Pilih Acara Pemilihan</label>
                                                <select name="id_pemilihan" class="form-select" required>
                                                    <?php
                                                    $q_acara_edit = $koneksi->query("SELECT id, judul_pemilihan FROM pemilihan ORDER BY id DESC");
                                                    while ($acara_edit = $q_acara_edit->fetch_assoc()) {
                                                        $terpilih = ($acara_edit['id'] == $baris['id_pemilihan']) ? 'selected' : '';
                                                        echo "<option value='{$acara_edit['id']}' $terpilih>{$acara_edit['judul_pemilihan']}</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label text-muted small fw-bold">Nomor Urut</label>
                                                <input type="number" name="no_urut" class="form-control" value="<?= $baris['no_urut'] ?>" required>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-muted small fw-bold">Nama Kandidat</label>
                                            <input type="text" name="nama_kandidat" class="form-control" value="<?= htmlspecialchars($baris['nama_kandidat']) ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-muted small fw-bold">Visi</label>
                                            <textarea name="visi" class="form-control" rows="3" required><?= htmlspecialchars($baris['visi']) ?></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-muted small fw-bold">Misi</label>
                                            <textarea name="misi" class="form-control" rows="4" required><?= htmlspecialchars($baris['misi']) ?></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label text-muted small fw-bold">Ganti Foto (Opsional)</label>
                                            <input type="file" name="foto" class="form-control" accept="image/*">
                                            <div class="form-text small">Kosongkan jika tidak ingin mengganti foto.</div>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-top-0 pb-4 px-4">
                                        <button type="button" class="btn btn-light text-muted fw-bold" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" name="edit_kandidat" class="btn btn-primary fw-bold px-4">Simpan Perubahan</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <!-- Akhir Modal Edit -->

                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Popup Modal Tambah Kandidat -->
<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0 pt-4 px-4">
                <h5 class="modal-title fw-bold">Tambah Kandidat Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="modal-body px-4">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted small fw-bold">Pilih Acara Pemilihan</label>
                            <select name="id_pemilihan" class="form-select" required>
                                <option value="">-- Pilih Acara --</option>
                                <?php
                                $q_acara = $koneksi->query("SELECT id, judul_pemilihan FROM pemilihan ORDER BY id DESC");
                                while ($acara = $q_acara->fetch_assoc()) {
                                    echo "<option value='{$acara['id']}'>{$acara['judul_pemilihan']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label text-muted small fw-bold">Nomor Urut</label>
                            <input type="number" name="no_urut" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Nama Kandidat</label>
                        <input type="text" name="nama_kandidat" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Visi</label>
                        <textarea name="visi" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Misi</label>
                        <textarea name="misi" class="form-control" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Unggah Foto Kandidat</label>
                        <input type="file" name="foto" class="form-control" accept="image/*" required>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pb-4 px-4">
                    <button type="button" class="btn btn-light text-muted fw-bold" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_kandidat" class="btn btn-primary fw-bold px-4">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'komponen/footer.php'; ?>
