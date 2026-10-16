<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

require_once "../config/database.php";

// Vérification de la session Admin / Modérateur
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true 
    || !in_array($_SESSION['admin_role'] ?? '', ['ADMIN', 'MODERATOR'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Accès non autorisé."]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    // ==================================================
    // GET : Lister tous les types de transport
    // ==================================================
    if ($method === 'GET') {
        $stmt = $pdo->query("
            SELECT 
                id, name, code, description, 
                capacity_estimate, capacity_range, 
                image_url, is_active, created_at, updated_at
            FROM transport_types 
            ORDER BY id ASC
        ");
        echo json_encode(["success" => true, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    // ==================================================
    // POST : Créer, Mettre à jour ou Supprimer
    // ==================================================
    if ($method === 'POST') {

        // ---------- SUPPRESSION ----------
        if ($action === 'delete') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            if (!$id) {
                echo json_encode(["success" => false, "message" => "ID invalide."]);
                exit;
            }

            // Vérifier si le type est utilisé par des lignes
            $check = $pdo->prepare("SELECT COUNT(*) FROM transport_lines WHERE transport_type_id = :id");
            $check->execute([':id' => $id]);
            $count = (int)$check->fetchColumn();

            if ($count > 0) {
                echo json_encode([
                    "success" => false, 
                    "message" => "Impossible de supprimer : ce type est utilisé par $count ligne(s)."
                ]);
                exit;
            }

            // Récupérer l'image pour la supprimer du serveur
            $imgStmt = $pdo->prepare("SELECT image_url FROM transport_types WHERE id = :id");
            $imgStmt->execute([':id' => $id]);
            $imageUrl = $imgStmt->fetchColumn();
            
            if ($imageUrl && file_exists(__DIR__ . '/../' . $imageUrl)) {
                @unlink(__DIR__ . '/../' . $imageUrl);
            }

            $stmt = $pdo->prepare("DELETE FROM transport_types WHERE id = :id");
            $stmt->execute([':id' => $id]);

            echo json_encode(["success" => true, "message" => "Type de transport supprimé."]);
            exit;
        }

        // ---------- CRÉATION / MISE À JOUR ----------
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $name = trim($_POST['name'] ?? '');
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $description = trim($_POST['description'] ?? '');
        $capacityEstimate = filter_input(INPUT_POST, 'capacity_estimate', FILTER_VALIDATE_INT) ?: null;
        $capacityRange = trim($_POST['capacity_range'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        // Validation
        if (empty($name) || empty($code)) {
            echo json_encode(["success" => false, "message" => "Le nom et le code sont obligatoires."]);
            exit;
        }

        // Upload d'image
        $imageUrl = $_POST['existing_image_url'] ?? null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../assets/img/transport/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $allowedExt = ['png', 'jpg', 'jpeg', 'webp', 'svg', 'gif'];
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowedExt)) {
                echo json_encode(["success" => false, "message" => "Format d'image non autorisé (utilisez PNG, JPG, WEBP, SVG)."]);
                exit;
            }

            if ($_FILES['image']['size'] > 2 * 1024 * 1024) {
                echo json_encode(["success" => false, "message" => "Image trop lourde (max 2 Mo)."]);
                exit;
            }

            // Générer un nom unique
            $fileName = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $code)) . '_' . time() . '.' . $ext;
            $targetPath = $uploadDir . $fileName;

            if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                echo json_encode(["success" => false, "message" => "Erreur lors de l'upload de l'image."]);
                exit;
            }

            // Supprimer l'ancienne image si elle existe
            if ($imageUrl && file_exists(__DIR__ . '/../' . $imageUrl) && $imageUrl !== "assets/img/transport/$fileName") {
                @unlink(__DIR__ . '/../' . $imageUrl);
            }

            $imageUrl = 'assets/img/transport/' . $fileName;
        }

        // ---------- MISE À JOUR ----------
        if ($id) {
            $stmt = $pdo->prepare("
                UPDATE transport_types 
                SET name = :name, code = :code, description = :description,
                    capacity_estimate = :cap_est, capacity_range = :cap_range,
                    image_url = :image_url, is_active = :is_active
                WHERE id = :id
            ");
            $stmt->execute([
                ':name' => $name, ':code' => $code, ':description' => $description,
                ':cap_est' => $capacityEstimate, ':cap_range' => $capacityRange,
                ':image_url' => $imageUrl, ':is_active' => $isActive, ':id' => $id
            ]);

            echo json_encode(["success" => true, "message" => "Type « $name » mis à jour."]);
            exit;
        }

        // ---------- CRÉATION ----------
        $stmt = $pdo->prepare("
            INSERT INTO transport_types 
            (name, code, description, capacity_estimate, capacity_range, image_url, is_active) 
            VALUES (:name, :code, :description, :cap_est, :cap_range, :image_url, :is_active)
        ");
        $stmt->execute([
            ':name' => $name, ':code' => $code, ':description' => $description,
            ':cap_est' => $capacityEstimate, ':cap_range' => $capacityRange,
            ':image_url' => $imageUrl, ':is_active' => $isActive
        ]);

        echo json_encode([
            "success" => true, 
            "message" => "Type « $name » créé avec succès.",
            "id" => $pdo->lastInsertId()
        ]);
        exit;
    }

    echo json_encode(["success" => false, "message" => "Méthode non autorisée."]);

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Erreur BDD : " . $e->getMessage()]);
}
?>