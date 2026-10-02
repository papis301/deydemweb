<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../config/database.php";

$data = json_decode(file_get_contents("php://input"), true);
$stop = trim($data['stop'] ?? '');

if (empty($stop)) {
    echo json_encode(["success" => false, "message" => "Veuillez renseigner un arrêt ou un terminus."]);
    exit;
}

// Nettoyage optionnel pour enlever les mots superflus si l'utilisateur tape "Terminus Liberté 6"
$cleanStop = str_ireplace(['terminus de', 'terminus', 'arret', 'station'], '', $stop);
$cleanStop = trim($cleanStop);
if (empty($cleanStop)) $cleanStop = $stop;

try {
    // Requête élargie joignant aussi la table locations pour être sûr de trouver l'arrêt
    $stmt = $pdo->prepare("
        SELECT DISTINCT
            tl.id,
            tl.line_name AS transport_name,
            tt.name AS transport_type_name,
            loc_orig.name AS origin_name,
            loc_dest.name AS destination_name,
            tl.itinerary_details AS description,
            'APPROVED' AS status,
            NULL AS estimated_price,
            tl.itinerary_details AS terminals,
            tt.code AS transport_type_code
        FROM transport_lines tl
        INNER JOIN transport_types tt ON tl.transport_type_id = tt.id
        LEFT JOIN locations loc_orig ON tl.origin_location_id = loc_orig.id
        LEFT JOIN locations loc_dest ON tl.destination_location_id = loc_dest.id
        WHERE LOWER(tl.itinerary_details) LIKE LOWER(:stop1)
           OR LOWER(loc_orig.name) LIKE LOWER(:stop2)
           OR LOWER(loc_dest.name) LIKE LOWER(:stop3)
    ");
    
    $searchTerm = '%' . $cleanStop . '%';
    $stmt->execute([
        ':stop1' => $searchTerm,
        ':stop2' => $searchTerm,
        ':stop3' => $searchTerm
    ]);
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "results" => $results
    ]);

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Erreur : " . $e->getMessage()]);
}
?>