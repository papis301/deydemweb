<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../config/database.php";

session_start();

$data = json_decode(file_get_contents("php://input"), true);

$origin = trim($data['origin'] ?? '');
$destination = trim($data['destination'] ?? '');

if (empty($origin) && empty($destination)) {
    echo json_encode(["success" => false, "message" => "Veuillez préciser au moins un lieu de recherche."]);
    exit;
}

$user_id =$_SESSION['user_id'] ?? null;
$session_token = session_id();

// Fonction de nettoyage pour harmoniser les libellés
function cleanSearchTerm($term) {
    $stopwords = ['gare de', 'gare', '(brt)', 'ter', 'station', '2 voies', 'deux voies', 'avenue', 'bld', 'boulevard'];$cleaned = str_ireplace($stopwords, '',$term);
    return trim($cleaned);
}

$cleanOrigin = cleanSearchTerm($origin);
$cleanDestination = cleanSearchTerm($destination);

if (empty($cleanOrigin)) $cleanOrigin =$origin;
if (empty($cleanDestination)) $cleanDestination =$destination;

try {
    $results = [];

    // 1. Recherche élargie dans les lignes officielles `transport_lines`
    if (!empty($cleanOrigin) && !empty($cleanDestination)) {
        $stmt =$pdo->prepare("
            SELECT 
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
            WHERE (
                    LOWER(tl.itinerary_details) LIKE LOWER(:o1) 
                    OR LOWER(loc_orig.name) LIKE LOWER(:o2) 
                    OR LOWER(loc_dest.name) LIKE LOWER(:o3)
                  )
              AND (
                    LOWER(tl.itinerary_details) LIKE LOWER(:d1) 
                    OR LOWER(loc_orig.name) LIKE LOWER(:d2) 
                    OR LOWER(loc_dest.name) LIKE LOWER(:d3)
                  )
        ");
        $stmt->execute([
            ':o1' => '%' . $cleanOrigin . '%',
            ':o2' => '%' . $cleanOrigin . '%',
            ':o3' => '%' . $cleanOrigin . '%',
            ':d1' => '%' . $cleanDestination . '%',
            ':d2' => '%' . $cleanDestination . '%',
            ':d3' => '%' . $cleanDestination . '%'
        ]);
    } else {
        $searchTerm = '\%' . (!empty($cleanOrigin) ? $cleanOrigin :$cleanDestination) . '%';
        $stmt =$pdo->prepare("
            SELECT 
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
            WHERE LOWER(tl.itinerary_details) LIKE LOWER(:term1)
               OR LOWER(loc_orig.name) LIKE LOWER(:term2)
               OR LOWER(loc_dest.name) LIKE LOWER(:term3)
        ");
        $stmt->execute([
            ':term1' => $searchTerm,
            ':term2' => $searchTerm,
            ':term3' => $searchTerm
        ]);
    }

    $results =$stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Si aucune ligne officielle n'est trouvée, recherche de secours dans `transport_routes`
    if (count($results) === 0) {
        if (!empty($origin) && !empty($destination)) {
            $fallbackStmt =$pdo->prepare("
                SELECT 
                    r.id,
                    t.name AS transport_name,
                    r.origin_name,
                    r.destination_name,
                    r.estimated_price,
                    r.status,
                    t.description,
                    NULL AS terminals,
                    t.code AS transport_type_code
                FROM transport_routes r
                INNER JOIN transport_types t ON r.transport_type_id = t.id
                WHERE (LOWER(r.origin_name) LIKE LOWER(:o1) AND LOWER(r.destination_name) LIKE LOWER(:d1))
                   OR (LOWER(r.origin_name) LIKE LOWER(:d2) AND LOWER(r.destination_name) LIKE LOWER(:o2))
            ");
            $fallbackStmt->execute([
                ':o1' => '%' . $origin . '%',
                ':d1' => '%' . $destination . '%',
                ':o2' => '%' . $origin . '%',
                ':d2' => '%' . $destination . '%'
            ]);
        } else {
            $searchTerm = '\%' . (!empty($origin) ? $origin :$destination) . '%';
            $fallbackStmt =$pdo->prepare("
                SELECT 
                    r.id,
                    t.name AS transport_name,
                    r.origin_name,
                    r.destination_name,
                    r.estimated_price,
                    r.status,
                    t.description,
                    NULL AS terminals,
                    t.code AS transport_type_code
                FROM transport_routes r
                INNER JOIN transport_types t ON r.transport_type_id = t.id
                WHERE LOWER(r.origin_name) LIKE LOWER(:term1) OR LOWER(r.destination_name) LIKE LOWER(:term2)
            ");
            $fallbackStmt->execute([
                ':term1' => $searchTerm,
                ':term2' => $searchTerm
            ]);
        }
        $results =$fallbackStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 3. Si toujours rien et qu'on a les deux champs, enregistrement de la recherche en PENDING
    if (count($results) === 0 && !empty($origin) && !empty($destination)) {
        $insertStmt =$pdo->prepare("
            INSERT INTO transport_routes 
            (user_id, session_token, transport_type_id, origin_name, destination_name, status) 
            VALUES (:user_id, :session_token, 1, :origin, :destination, 'PENDING')
        ");
        $insertStmt->execute([
            ':user_id' => $user_id,
            ':session_token' => $session_token,
            ':origin' => $origin,
            ':destination' => $destination
        ]);
    }

    echo json_encode([
        "success" => true,
        "results" => $results
    ]);

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Erreur : " . $e->getMessage()]);
}
?>