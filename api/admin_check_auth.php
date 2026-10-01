<?php
session_start();
header("Content-Type: application/json");

$action = $_GET['action'] ?? 'check';

if ($action === 'logout') {
    unset($_SESSION['admin_logged_in']);
    unset($_SESSION['admin_user']);
    session_destroy();
    echo json_encode(["success" => true, "message" => "Déconnecté."]);
    exit;
}

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    echo json_encode(["authenticated" => true, "user" => $_SESSION['admin_user']]);
} else {
    echo json_encode(["authenticated" => false]);
}
?>