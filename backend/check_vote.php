<?php
// Disable error display to prevent corrupting JSON output
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

ob_start();

require_once "config.php";
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    $input = json_decode(file_get_contents("php://input"), true);
    $user_id = $input['user_id'] ?? null;
    $poll_id = $input['poll_id'] ?? null;

    if (!$user_id || !$poll_id) {
        echo json_encode(["success" => false, "message" => "Missing user_id or poll_id"]);
        exit;
    }

    // Check if user has already voted in this poll
    $stmt = $conn->prepare("
        SELECT v.*, p.name as candidate_name 
        FROM votes v 
        JOIN participants p ON v.participant_id = p.id 
        WHERE v.user_id = ? AND p.poll_id = ?
    ");
    $stmt->execute([$user_id, $poll_id]);
    $vote = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($vote) {
        echo json_encode([
            "success" => true,
            "hasVoted" => true,
            "votedCandidate" => $vote['candidate_name']
        ]);
    } else {
        echo json_encode([
            "success" => true,
            "hasVoted" => false
        ]);
    }

} catch (Exception $e) {
    error_log("Check vote error: " . $e->getMessage());
    echo json_encode([
        "success" => false, 
        "message" => "Server error: " . $e->getMessage()
    ]);
}
?>