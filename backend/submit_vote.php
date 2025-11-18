<?php
// Disable error display to prevent corrupting JSON output
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

ob_start();

require_once "config.php";
require_once "email_functions.php";
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
    error_log("submit_vote.php: Input received: " . json_encode($input));
    error_log("submit_vote.php: Session user: " . json_encode($_SESSION['user'] ?? null));

    $user_id = $input['user_id'] ?? null;
    $participant_id = $input['participant_id'] ?? null;
    $poll_id = $input['poll_id'] ?? null;

    if (!$user_id || !$participant_id || !$poll_id) {
        error_log("submit_vote.php: Missing required fields");
        echo json_encode(["success" => false, "message" => "Missing required fields"]);
        exit;
    }

    // Verify user is logged in
    if (!isset($_SESSION['user']) || $_SESSION['user']['id'] != $user_id) {
        error_log("submit_vote.php: User not authenticated");
        echo json_encode(["success" => false, "message" => "User not authenticated"]);
        exit;
    }

    // First, verify that the user exists
    $userStmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
    $userStmt->execute([$user_id]);
    if (!$userStmt->fetch()) {
        echo json_encode(["success" => false, "message" => "Invalid user ID"]);
        exit;
    }

    // Verify participant exists and belongs to the poll
    $participantStmt = $conn->prepare("SELECT id FROM participants WHERE id = ? AND poll_id = ?");
    $participantStmt->execute([$participant_id, $poll_id]);
    if (!$participantStmt->fetch()) {
        echo json_encode(["success" => false, "message" => "Invalid participant or poll"]);
        exit;
    }

    // Check if user already voted in this poll
    $checkStmt = $conn->prepare("
        SELECT COUNT(*)
        FROM votes v
        JOIN participants p ON v.participant_id = p.id
        WHERE v.user_id = ? AND p.poll_id = ?
    ");
    $checkStmt->execute([$user_id, $poll_id]);

    if ($checkStmt->fetchColumn() > 0) {
        echo json_encode(["success" => false, "message" => "You have already voted in this poll"]);
        exit;
    }

    // Insert vote - using participant_id (singular) to match the expected column name
    $voteStmt = $conn->prepare("INSERT INTO votes (user_id, participant_id, created_at) VALUES (?, ?, NOW())");
    if (!$voteStmt->execute([$user_id, $participant_id])) {
        throw new Exception("Failed to insert vote record");
    }

    $vote_id = $conn->lastInsertId();

    // Use email from session to ensure it's the logged-in user's email
    $userEmail = $_SESSION['user']['email'] ?? null;

    // Get participant and poll details for confirmation email
    $voteDetailsStmt = $conn->prepare("
        SELECT
            p.name as participant_name,
            polls.title as election_name,
            CONCAT(polls.start_date, ' to ', polls.end_date) as election_period,
            NOW() as vote_datetime
        FROM participants p
        JOIN polls ON p.poll_id = polls.id
        WHERE p.id = ?
    ");
    $voteDetailsStmt->execute([$participant_id]);
    $voteDetails = $voteDetailsStmt->fetch(PDO::FETCH_ASSOC);

    // Send voting confirmation email asynchronously if user has email
    if ($userEmail && $voteDetails) {
        $voteData = [
            'election_name' => $voteDetails['election_name'] ?? 'Unknown Election',
            'candidate_name' => $voteDetails['participant_name'] ?? 'Unknown Candidate',
            'election_period' => $voteDetails['election_period'] ?? 'N/A',
            'vote_datetime' => $voteDetails['vote_datetime'],
            'vote_id' => $vote_id
        ];

        error_log("Vote confirmation email will be sent to: " . $userEmail);

        // Send email asynchronously
        $voteDataJson = json_encode($voteData);
        // Windows compatible async command
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $command = "start /B php " . __DIR__ . "\\send_email_background.php \"$userEmail\" \"$voteDataJson\"";
        } else {
            $command = "php " . __DIR__ . "/send_email_background.php \"$userEmail\" \"$voteDataJson\" > /dev/null 2>&1 &";
        }
        exec($command);

        // Alternative: use fastcgi_finish_request if available
        // if (function_exists('fastcgi_finish_request')) {
        //     fastcgi_finish_request();
        //     send_vote_confirmation_email($userEmail, $voteData);
        //     exit;
        // }
    }

    echo json_encode(["success" => true, "message" => "Vote submitted successfully"]);

} catch (Exception $e) {
    error_log("Submit vote error: " . $e->getMessage());
    echo json_encode([
        "success" => false, 
        "message" => "Failed to submit vote: " . $e->getMessage()
    ]);
}
exit;
?>