<?php
header("Content-Type: application/json; charset=UTF-8");
require_once "../config/database.php";

session_start();

$data = json_decode(file_get_contents("php://input"), true);
$content = trim($data['content'] ?? '');

if (empty($content)) {
    echo json_encode(["success" => false, "message" => "Veuillez écrire votre suggestion."]);
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
$session_token = session_id();

try {
    $stmt = $pdo->prepare("
        INSERT INTO suggestions (user_id, session_token, content) 
        VALUES (:user_id, :session_token, :content)
    ");
    
    $stmt->execute([
        ':user_id' => $user_id,
        ':session_token' => $session_token,
        ':content' => $content
    ]);

    echo json_encode([
        "success" => true, 
        "message" => "Merci ! Votre suggestion a bien été transmise à l'équipe."
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "success" => false, 
        "message" => "Erreur technique : " . $e->getMessage()
    ]);
}
?>