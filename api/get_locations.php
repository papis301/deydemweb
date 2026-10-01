<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require_once "../config/database.php";

$query = trim($_GET['q'] ?? '');

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

try {
    $searchTerm = '%' . $query . '%';

    // Utilisation de :q1 et :q2 pour éviter l'erreur HY093
    $stmt = $pdo->prepare("
        SELECT id, name, category, district_neighborhood 
        FROM locations 
        WHERE is_active = TRUE 
          AND (LOWER(name) LIKE LOWER(:q1) OR LOWER(district_neighborhood) LIKE LOWER(:q2))
        ORDER BY name ASC 
        LIMIT 10
    ");
    
    $stmt->execute([
        ':q1' => $searchTerm,
        ':q2' => $searchTerm
    ]);
    
    $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($locations);
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>