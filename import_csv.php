<?php
// Pastikan file koneksi database (db.php) sudah ada
require_once 'db.php';

$csvFile = 'List_IOC_Hash_Threat_Actor - Ops - WinRAR ZeroDay.csv';

if (!file_exists($csvFile)) {
    die("File CSV tidak ditemukan! Pastikan file berada di folder yang sama.");
}

// Buka file CSV
if (($handle = fopen($csvFile, "r")) !== FALSE) {
    // Lewati baris pertama (Header: SHA1, SHA256, Unnamed: 2)
    $header = fgetcsv($handle, 1000, ",");
    
    // Buat Threat Actor Default Berdasarkan Kampanye WinRAR ZeroDay
    $stmtActor = $conn->prepare("INSERT INTO threat_actors (name, category) VALUES (?, 'EXISTING')");
    $actorName = "WinRAR ZeroDay Campaign";
    $stmtActor->bind_param("s", $actorName);
    $stmtActor->execute();
    $actorId = $stmtActor->insert_id;
    $stmtActor->close();

    $countInserted = 0;

    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $sha1 = isset($data[0]) ? trim($data[0]) : '';
        $sha256 = isset($data[1]) ? trim($data[1]) : '';
        $campaign = isset($data[2]) ? trim($data[2]) : '';

        // Jika ada kolom nama kampanye baru di baris tertentu
        if (!empty($campaign)) {
            $stmtActor = $conn->prepare("INSERT INTO threat_actors (name, category) VALUES (?, 'EXISTING')");
            $stmtActor->bind_param("s", $campaign);
            $stmtActor->execute();
            $actorId = $stmtActor->insert_id;
            $stmtActor->close();
        }

        // Masukkan SHA1 jika valid
        if (!empty($sha1) && preg_match('/^[a-fA-F0-9]{40}$/', $sha1)) {
            $stmtHash = $conn->prepare("INSERT INTO threat_hashes (actor_id, hash_value, hash_type) VALUES (?, ?, 'SHA1')");
            $stmtHash->bind_param("is", $actorId, $sha1);
            $stmtHash->execute();
            $stmtHash->close();
            $countInserted++;
        }

        // Masukkan SHA256 jika valid
        if (!empty($sha256) && preg_match('/^[a-fA-F0-9]{64}$/', $sha256)) {
            $stmtHash = $conn->prepare("INSERT INTO threat_hashes (actor_id, hash_value, hash_type) VALUES (?, ?, 'SHA256')");
            $stmtHash->bind_param("is", $actorId, $sha256);
            $stmtHash->execute();
            $stmtHash->close();
            $countInserted++;
        }
    }
    fclose($handle);

    echo "<h3 style='font-family:sans-serif; color:emerald;'>Berhasil! Sebanyak <b>$countInserted hash</b> dari file CSV telah otomatis dimasukkan ke database MySQL XAMPP.</h3>";
    echo "<a href='index.php'>Kembali ke Dashboard Threat Hunt</a>";
} else {
    echo "Gagal membuka file CSV.";
}
?>