<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../config/database.php";

try {
    $stmt = $pdo->query("
        SELECT id, name, code, description, image_url, capacity_estimate 
        FROM transport_types 
        WHERE is_active = 1 
        ORDER BY id ASC
    ");
    echo json_encode([
        'success' => true, 
        'types' => $stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>