<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "rsc_db"; // Ganti dengan nama database kamu di phpMyAdmin jika berbeda

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Koneksi Database Gagal: " . $conn->connect_error);
}
// Set charset agar aman dari karakter khusus
$conn->set_charset("utf8mb4");
?>