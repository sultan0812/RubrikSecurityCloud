<?php
require_once 'db.php';

header('Content-Type: application/json');

if (isset($_GET['actor_id'])) {
    $actorId = intval($_GET['actor_id']);
    
    $stmt = $conn->prepare("SELECT hash_value FROM threat_hashes WHERE actor_id = ?");
    $stmt->bind_param("i", $actorId);
    $stmt->execute();
    $result = $stmt->get_result();

    $hashes = [];
    while ($row = $result->fetch_assoc()) {
        $hashes[] = $row['hash_value'];
    }

    echo json_encode([
        'status' => 'success',
        'hashes' => $hashes,
        'hash_text' => implode("\n", $hashes)
    ]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid Actor ID']);
?>