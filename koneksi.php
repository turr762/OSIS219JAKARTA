<?php
$host = "127.0.0.1";
$user = "root";
$pass = "root"; // Sesuaikan jika password database kamu bukan 12345
$db   = "pemilihan_ketua_osis";
$port = 3306;

// Membuat koneksi ke database
$koneksi = new mysqli($host, $user, $pass, $db, $port);

// Memeriksa apakah koneksi berhasil
if ($koneksi->connect_error) {
    die("Koneksi database gagal: " . $koneksi->connect_error);
}
?>
