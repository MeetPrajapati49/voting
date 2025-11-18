<?php
header("Content-Type: application/json");
require_once __DIR__ . "/config.php"; // provides $conn

$action = $_GET['action'] ?? '';

if ($action === 'completed') {
    $today = date("Y-m-d");
    $stmt = $conn->prepare("SELECT id, title, start_date, end_date 
                            FROM polls 
                            WHERE end_date < ?");
    $stmt->execute([$today]);
    $polls = [];

    while ($poll = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $poll_id = $poll['id'];

        // Count total votes
        $voteStmt = $conn->prepare("SELECT COUNT(*) as total FROM votes v
                                    JOIN participants p ON v.participant_id = p.id
                                    WHERE p.poll_id = ?");
        $voteStmt->execute([$poll_id]);
        $totalVotes = $voteStmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Get each participant and their vote counts
        $partStmt = $conn->prepare("SELECT p.id, p.name, COUNT(v.id) as votes
                                    FROM participants p
                                    LEFT JOIN votes v ON v.participant_id = p.id
                                    WHERE p.poll_id = ?
                                    GROUP BY p.id");
        $partStmt->execute([$poll_id]);
        $participants = $partStmt->fetchAll(PDO::FETCH_ASSOC);

        $polls[] = [
            "id" => $poll_id,
            "title" => $poll['title'],
            "start_date" => $poll['start_date'],
            "end_date" => $poll['end_date'],
            "total_votes" => $totalVotes,
            "options" => array_map(function($row) {
                return [
                    "option_text" => $row['name'],
                    "votes" => $row['votes']
                ];
            }, $participants)
        ];
    }

    echo json_encode($polls);
    exit;
}

echo json_encode(["error" => "Invalid action"]);
exit;
?>