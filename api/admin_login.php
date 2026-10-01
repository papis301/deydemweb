<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

require_once "../config/database.php";

$data = json_decode(file_get_contents("php://input"), true);

$username = trim($data['username'] ?? '');
$password = trim($data['password'] ?? '');

if (empty($username) || empty($password)) {
    echo json_encode(["success" => false, "message" => "Identifiants requis."]);
    exit;
}

try {
    // La requête comporte 2 marqueurs distincts : :nickname et :email
    $stmt = $pdo->prepare("
        SELECT id, nickname, password_hash, role, is_blocked 
        FROM users 
        WHERE (nickname = :nickname OR email = :email) 
        LIMIT 1
    ");
    
    // On passe les deux valeurs dans le tableau execute
    $stmt->execute([
        ':nickname' => $username,
        ':email'    => $username
    ]);
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        if (!empty($user['is_blocked']) && $user['is_blocked'] == 1) {
            echo json_encode(["success" => false, "message" => "Compte bloqué."]);
            exit;
        }

        if (in_array($user['role'], ['ADMIN', 'MODERATOR'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_user'] = $user['nickname'];
            $_SESSION['admin_role'] = $user['role'];
            
            echo json_encode(["success" => true, "message" => "Connexion réussie."]);
        } else {
            echo json_encode(["success" => false, "message" => "Accès refusé. Droits d'administration requis."]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Identifiants incorrects."]);
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Erreur BDD : " . $e->getMessage()]);
}
?>