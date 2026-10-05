<?php
header('Content-Type: application/json; charset=utf-8');
require_once "../config/database.php";

try {
    $stmt = $pdo->prepare("
        SELECT tl.id, tl.line_name, tt.code as transport_code, tt.name as transport_type_name, 
               loc_orig.name as origin_name, loc_dest.name as destination_name
        FROM transport_lines tl
        JOIN transport_types tt ON tl.transport_type_id = tt.id
        LEFT JOIN locations loc_orig ON tl.origin_location_id = loc_orig.id
        LEFT JOIN locations loc_dest ON tl.destination_location_id = loc_dest.id
        ORDER BY LENGTH(tl.line_name), tl.line_name ASC, tt.id ASC
    ");
    $stmt->execute();
    $lines = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'lines' => $lines]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>