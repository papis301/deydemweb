<?php
/**
 * Configuration et connexion à la base de données MySQL
 */

// Identifiants de connexion (À adapter selon votre environnement local ou serveur)
define('DB_HOST', 'localhost');
define('DB_NAME', 'deydemweb'); // Nom de votre base de données
define('DB_USER', 'root');              // Utilisateur MySQL (ex: root)
define('DB_PASS', 'root');                  // Mot de passe MySQL
define('DB_CHARSET', 'utf8mb4');

$options = [
    // Lève des exceptions en cas d'erreur SQL
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    // Récupère les résultats sous forme de tableaux associés par défaut
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    // Désactive l'émulation des requêtes préparées pour une vraie sécurité anti-injection SQL
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // En production, il est recommandé de ne pas afficher $e->getMessage() directement
    header('Content-Type: application/json');
    echo json_encode([
        "success" => false,
        "message" => "Erreur de connexion à la base de données : " . $e->getMessage()
    ]);
    exit;
}