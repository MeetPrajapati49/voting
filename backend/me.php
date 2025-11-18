<?php
// backend/me.php
require_once "config.php";

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header("Content-Type: application/json");
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if (isset($_SESSION["user"]) && is_array($_SESSION["user"])) {
    echo json_encode([
        "success"  => true,
        "id"       => $_SESSION["user"]["id"],
        "username" => $_SESSION["user"]["username"],
        "role"     => $_SESSION["user"]["role"]
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Not logged in"
    ]);
}
?>