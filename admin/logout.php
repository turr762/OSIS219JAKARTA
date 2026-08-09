<?php
session_start();
// Menghancurkan semua data sesi login
session_destroy();
// Arahkan kembali ke form login
header("Location: login.php");
exit;
?>
