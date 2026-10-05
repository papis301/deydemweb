<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../config/database.php";

session_start();

$data = json_decode(file_get_contents("php://input"), true);

$lineId = (int)($data['line_id'] ?? 0); // ligne précise choisie via un raccourci (évite de confondre 2 lignes de même nom)
$origin = trim($data['origin'] ?? '');
$destination = trim($data['destination'] ?? '');

if (empty($origin) && empty($destination) && $lineId <= 0) {
    echo json_encode(["success" => false, "message" => "Veuillez préciser au moins un lieu de recherche."]);
    exit;
}

$user_id =$_SESSION['user_id'] ?? null;
$session_token = session_id();

// Fonction de nettoyage pour harmoniser les libellés
function cleanSearchTerm($term) {
    $stopwords = ['gare de', 'gare', '(brt)', 'ter', 'station', '2 voies', 'deux voies', 'avenue', 'bld', 'boulevard'];
    foreach ($stopwords as $w) {
        // On ne retire que les mots entiers (sinon "ter" abîme "TERMINUS")
        $term = preg_replace('/(?<![\p{L}\p{N}])' . preg_quote($w, '/') . '(?![\p{L}\p{N}])/iu', ' ', $term);
    }
    return trim(preg_replace('/\s+/', ' ', $term));
}

$cleanOrigin = cleanSearchTerm($origin);
$cleanDestination = cleanSearchTerm($destination);

if (empty($cleanOrigin)) $cleanOrigin =$origin;
if (empty($cleanDestination)) $cleanDestination =$destination;

try {
    $results = [];

    // 1. Recherche dans les lignes officielles `transport_lines`
    // On essaie d'abord avec les termes tels que saisis, puis avec les termes nettoyés
    $candidates = [[$origin, $destination]];
    if ($cleanOrigin !== $origin || $cleanDestination !== $destination) {
        $candidates[] = [$cleanOrigin, $cleanDestination];
    }

    $baseSelect = "
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
    ";

    // Raccourci "ligne" : on renvoie exactement la ligne cliquée (identifiée par son id),
    // car plusieurs réseaux peuvent avoir une ligne du même nom (ex. LIGNE 1 DDD et LIGNE 1 Tata)
    if ($lineId > 0) {
        $stmt = $pdo->prepare($baseSelect . " WHERE tl.id = :line_id");
        $stmt->execute([':line_id' => $lineId]);
        echo json_encode(["success" => true, "results" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    foreach ($candidates as $pair) {
        $o = $pair[0];
        $d = $pair[1];

        if (!empty($o) && !empty($d)) {
            $stmt = $pdo->prepare($baseSelect . "
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
                ':o1' => '%' . $o . '%', ':o2' => '%' . $o . '%', ':o3' => '%' . $o . '%',
                ':d1' => '%' . $d . '%', ':d2' => '%' . $d . '%', ':d3' => '%' . $d . '%'
            ]);
        } else {
            $searchTerm = '%' . (!empty($o) ? $o : $d) . '%';
            $stmt = $pdo->prepare($baseSelect . "
                WHERE LOWER(tl.itinerary_details) LIKE LOWER(:term1)
                   OR LOWER(loc_orig.name) LIKE LOWER(:term2)
                   OR LOWER(loc_dest.name) LIKE LOWER(:term3)
            ");
            $stmt->execute([':term1' => $searchTerm, ':term2' => $searchTerm, ':term3' => $searchTerm]);
        }

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($results) > 0) break;
    }

    // 2. Si aucune ligne officielle n'est trouvée, recherche de secours dans `transport_routes`
    if (count($results) === 0) {
        if (!empty($origin) && !empty($destination)) {
            $fallbackStmt =$pdo->prepare("
                SELECT 
                    r.id,
                    'Trajet proposé' AS transport_name,
                    t.name AS transport_type_name,
                    r.origin_name,
                    r.destination_name,
                    r.estimated_price,
                    r.status,
                    t.description,
                    NULL AS terminals,
                    t.code AS transport_type_code
                FROM transport_routes r
                INNER JOIN transport_types t ON r.transport_type_id = t.id
                WHERE r.status <> 'REJECTED'
                  AND (
                       (LOWER(r.origin_name) LIKE LOWER(:o1) AND LOWER(r.destination_name) LIKE LOWER(:d1))
                    OR (LOWER(r.origin_name) LIKE LOWER(:d2) AND LOWER(r.destination_name) LIKE LOWER(:o2))
                  )
            ");
            $fallbackStmt->execute([
                ':o1' => '%' . $origin . '%',
                ':d1' => '%' . $destination . '%',
                ':o2' => '%' . $origin . '%',
                ':d2' => '%' . $destination . '%'
            ]);
        } else {
            $searchTerm = '%' . (!empty($origin) ? $origin :$destination) . '%';
            $fallbackStmt =$pdo->prepare("
                SELECT 
                    r.id,
                    'Trajet proposé' AS transport_name,
                    t.name AS transport_type_name,
                    r.origin_name,
                    r.destination_name,
                    r.estimated_price,
                    r.status,
                    t.description,
                    NULL AS terminals,
                    t.code AS transport_type_code
                FROM transport_routes r
                INNER JOIN transport_types t ON r.transport_type_id = t.id
                WHERE r.status <> 'REJECTED'
                  AND (LOWER(r.origin_name) LIKE LOWER(:term1) OR LOWER(r.destination_name) LIKE LOWER(:term2))
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