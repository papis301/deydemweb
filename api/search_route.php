<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../config/database.php";

session_start();

$data = json_decode(file_get_contents("php://input"), true);

$origin = trim($data['origin'] ?? '');
$destination = trim($data['destination'] ?? '');

if (empty($origin) || empty($destination)) {
    echo json_encode(["success" => false, "message" => "Veuillez préciser le départ et l'arrivée."]);
    exit;
}

$user_id =$_SESSION['user_id'] ?? null;
$session_token = session_id();

try {
    // 1. Recherche dans la table officielle `transport_lines` avec des paramètres nommés uniques (:o1, :d1, :o2, :d2)
    $stmt =$pdo->prepare("
        SELECT 
            tl.id,
            tl.line_name AS transport_name,
            tt.name AS transport_type_name,
            loc_orig.name AS origin_name,
            loc_dest.name AS destination_name,
            COALESCE(tl.itinerary_details, CONCAT('Ligne officielle ', tt.name, ' reliant ', loc_orig.name, ' à ', loc_dest.name)) AS description,
            'APPROVED' AS status,
            NULL AS estimated_price,
            CONCAT(loc_orig.name, ' / ', loc_dest.name) AS terminals
        FROM transport_lines tl
        INNER JOIN transport_types tt ON tl.transport_type_id = tt.id
        LEFT JOIN locations loc_orig ON tl.origin_location_id = loc_orig.id
        LEFT JOIN locations loc_dest ON tl.destination_location_id = loc_dest.id
        WHERE (
            (LOWER(loc_orig.name) LIKE LOWER(:o1) AND LOWER(loc_dest.name) LIKE LOWER(:d1))
            OR
            (LOWER(loc_orig.name) LIKE LOWER(:d2) AND LOWER(loc_dest.name) LIKE LOWER(:o2))
        )
    ");

    $stmt->execute([
        ':o1' => '%' . $origin . '%',
        ':d1' => '%' . $destination . '%',
        ':o2' => '%' . $origin . '%',
        ':d2' => '%' . $destination . '%'
    ]);

    $results =$stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Si aucune ligne officielle n'est trouvée, on vérifie dans `transport_routes` (routes soumises par les utilisateurs)
    if (count($results) === 0) {
        $fallbackStmt =$pdo->prepare("
            SELECT 
                r.id,
                t.name AS transport_name,
                r.origin_name,
                r.destination_name,
                r.estimated_price,
                r.status,
                t.description,
                NULL AS terminals
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
        $results =$fallbackStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 3. Si toujours rien, on enregistre la recherche comme proposition PENDING
    if (count($results) === 0) {
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