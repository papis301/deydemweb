<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

require_once "../config/database.php";

// Vérification de la session Admin / Modérateur
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || !in_array($_SESSION['admin_role'] ?? '', ['ADMIN', 'MODERATOR'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Accès non autorisé."]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $statusFilter = $_GET['status'] ?? 'ALL';

    try {
        // Requête adaptée avec les colonnes origin_location_id et destination_location_id
        // COALESCE permet de privilégier le nom de la table locations, sinon prendre origin_name/destination_name
        $sql = "
            SELECT 
                tr.id,
                tr.status,
                tr.estimated_price,
                tr.created_at,
                COALESCE(loc_orig.name, tr.origin_name) AS origin_name,
                COALESCE(loc_dest.name, tr.destination_name) AS destination_name,
                tt.name AS transport_name
            FROM transport_routes tr
            LEFT JOIN locations loc_orig ON tr.origin_location_id = loc_orig.id
            LEFT JOIN locations loc_dest ON tr.destination_location_id = loc_dest.id
            LEFT JOIN transport_types tt ON tr.transport_type_id = tt.id
        ";

        if (in_array($statusFilter, ['PENDING', 'APPROVED', 'REJECTED', 'EXPIRED'])) {
            $sql .= " WHERE tr.status = :status";
        }

        $sql .= " ORDER BY tr.created_at DESC";

        $stmt = $pdo->prepare($sql);

        if (in_array($statusFilter, ['PENDING', 'APPROVED', 'REJECTED', 'EXPIRED'])) {
            $stmt->bindValue(':status', $statusFilter, PDO::PARAM_STR);
        }

        $stmt->execute();
        $routes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["success" => true, "data" => $routes]);

    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Erreur BDD : " . $e->getMessage()]);
    }

} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    $route_id = filter_var($data['route_id'] ?? null, FILTER_VALIDATE_INT);
    $status = $data['status'] ?? '';
    $estimated_price = $data['estimated_price'] ?? null;

    if (!$route_id || !in_array($status, ['PENDING', 'APPROVED', 'REJECTED', 'EXPIRED'])) {
        echo json_encode(["success" => false, "message" => "Données invalides pour la mise à jour."]);
        exit;
    }

    try {
        $sql = "
            UPDATE transport_routes 
            SET status = :status,
                estimated_price = :price,
                validated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':status' => $status,
            ':price'  => ($estimated_price !== '' && $estimated_price !== null) ? $estimated_price : null,
            ':id'     => $route_id
        ]);

        echo json_encode(["success" => true, "message" => "Trajet #" . $route_id . " mis à jour avec succès."]);

    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Erreur BDD : " . $e->getMessage()]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Méthode non autorisée."]);
}
?>