<?php
require_once "../config/database.php";

$pass = 'Passer@2026';
$hash = password_hash($pass, PASSWORD_BCRYPT);

try {
    $stmt = $pdo->prepare("
        UPDATE users 
        SET password_hash = :hash, role = 'ADMIN', is_blocked = 0 
        WHERE nickname = 'admin'
    ");
    $stmt->execute([':hash' => $hash]);

    if ($stmt->rowCount() > 0) {
        echo "Le mot de passe de 'admin' a été réinitialisé avec succès à : " . $pass;
    } else {
        // Si l'utilisateur admin n'existe pas encore
        $stmtInsert = $pdo->prepare("
            INSERT INTO users (nickname, email, password_hash, role, is_blocked) 
            VALUES ('admin', 'admin@deydem.pro', :hash, 'ADMIN', 0)
        ");
        $stmtInsert->execute([':hash' => $hash]);
        echo "Le compte 'admin' a été créé avec succès avec le mot de passe : " . $pass;
    }
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
?>